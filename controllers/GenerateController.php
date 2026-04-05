<?php
// controllers/GenerateController.php
// THIS IS THE BRIDGE BETWEEN PHP AND PYTHON.
//
// How it works:
//   1. Frontend sends nested JSON to this PHP file
//   2. This PHP file uses cURL to forward it to Python FastAPI (port 8000)
//   3. Python calls Gemini AI, returns HTML
//   4. This PHP file passes the HTML back to the frontend
//
// MySQL is NOT touched here. Only Python stores conversation history.

require_once __DIR__ . '/../services/ProjectService.php';

class GenerateController {

    // Where Python FastAPI is running
    private const FASTAPI_URL = 'http://127.0.0.1:8000';

    /**
     * Generate a resume.
     * Expects POST body: {"project_id": 123, "resume_data": {...}, "revision": "..."}
     * Returns: {"success": true, "html": "..."} or {"success": false, "error": "..."}
     */
    public function generate(): array {
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = (int) ($input['project_id'] ?? 0);
        $resumeData = $input['resume_data'] ?? null;
        $revision = $input['revision'] ?? '';

        // Validate input
        if ($projectId <= 0) {
            return ['success' => false, 'error' => 'Invalid project ID'];
        }
        if (!$resumeData) {
            return ['success' => false, 'error' => 'No resume data provided'];
        }

        // Verify this project belongs to the current user
        $svc = new ProjectService();
        $project = $svc->getById($projectId);
        if (!$project || $project['user_id'] !== $_SESSION['user_id']) {
            return ['success' => false, 'error' => 'Project not found'];
        }

        // Build the payload to send to Python
        $payload = json_encode([
            'user_id'     => $_SESSION['user_id'],
            'project_id'  => $projectId,
            'resume_data' => $resumeData,
            'revision'    => $revision
        ], JSON_UNESCAPED_UNICODE);

        // ─── THIS IS HOW PHP TALKS TO PYTHON ───
        // cURL makes a regular HTTP POST to the Python server.
        // It's exactly like your browser making a request, but done in PHP code.
        $ch = curl_init(self::FASTAPI_URL . '/generate-resume');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,   // return response as string
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 120,    // AI can take up to 2 minutes
            CURLOPT_CONNECTTIMEOUT => 10,     // fail fast if Python isn't running
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Handle connection errors (Python not running?)
        if ($curlError) {
            return [
                'success' => false,
                'error' => "Cannot reach AI server. Make sure Python is running on port 8000. Error: " . $curlError
            ];
        }

        // Handle non-200 responses from Python
        if ($httpCode !== 200) {
            $errorBody = json_decode($response, true);
            return [
                'success' => false,
                'error' => $errorBody['detail'] ?? "AI server returned HTTP $httpCode"
            ];
        }

        // Success — extract HTML from Python's response
        $result = json_decode($response, true);
        return [
            'success' => true,
            'html' => $result['html'] ?? ''
        ];
    }
}