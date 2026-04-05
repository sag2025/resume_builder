<?php
// controllers/ProjectController.php
// Handles all project-related API requests.
// Reads input, calls ProjectService, returns JSON response.

require_once __DIR__ . '/../services/ProjectService.php';

class ProjectController {
    private ProjectService $svc;

    public function __construct() {
        $this->svc = new ProjectService();
    }

    /**
     * Create a new project.
     * Expects POST body: {"project_name": "..."}
     */
    public function create(): array {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['project_name'] ?? '');

        if (!$name) {
            return ['success' => false, 'error' => 'Project name is required'];
        }
        if (strlen($name) > 200) {
            return ['success' => false, 'error' => 'Name too long (max 200 chars)'];
        }

        $id = $this->svc->create($_SESSION['user_id'], $name);
        return ['success' => true, 'project_id' => $id];
    }

    /**
     * List all projects for the current user.
     */
    public function list(): array {
        return [
            'success' => true,
            'projects' => $this->svc->listByUser($_SESSION['user_id'])
        ];
    }

    /**
     * Delete a project.
     * Expects POST body: {"project_id": 123}
     */
    public function delete(): array {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($input['project_id'] ?? 0);

        if ($id <= 0) {
            return ['success' => false, 'error' => 'Invalid project ID'];
        }

        // Ownership check — prevent deleting other users' projects
        $project = $this->svc->getById($id);
        if (!$project || $project['user_id'] !== $_SESSION['user_id']) {
            return ['success' => false, 'error' => 'Project not found'];
        }

        $this->svc->delete($id);
        return ['success' => true];
    }

    /**
     * Load a project's saved data.
     * Expects GET param: ?project_id=123
     * Returns resume_json (decoded to array) and resume_html if they exist.
     */
    public function load(): array {
        $id = (int) ($_GET['project_id'] ?? 0);

        if ($id <= 0) {
            return ['success' => false, 'error' => 'Invalid project ID'];
        }

        $project = $this->svc->getById($id);
        if (!$project || $project['user_id'] !== $_SESSION['user_id']) {
            return ['success' => false, 'error' => 'Project not found'];
        }

        // IMPORTANT: resume_json comes from MySQL as a string.
        // Decode it to a PHP array so JavaScript receives a proper object.
        $decodedJson = null;
        if ($project['resume_json']) {
            $decodedJson = json_decode($project['resume_json'], true);
        }

        return [
            'success' => true,
            'project_name' => $project['project_name'],
            'resume_json' => $decodedJson,   // array or null
            'resume_html' => $project['resume_html'],  // string or null
            'is_accepted' => (bool) $project['is_accepted']
        ];
    }

    /**
     * Save the final resume to MySQL.
     * Expects POST body: {"project_id": 123, "resume_json": {...}, "resume_html": "...", "is_accepted": true}
     */
    public function saveResume(): array {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($input['project_id'] ?? 0);
        $jsonData = $input['resume_json'] ?? null;
        $html = $input['resume_html'] ?? null;
        $accepted = (bool) ($input['is_accepted'] ?? false);

        if ($id <= 0) {
            return ['success' => false, 'error' => 'Invalid project ID'];
        }

        // Ownership check
        $project = $this->svc->getById($id);
        if (!$project || $project['user_id'] !== $_SESSION['user_id']) {
            return ['success' => false, 'error' => 'Project not found'];
        }

        // Convert the resume data object back to JSON string for MySQL
        $jsonString = json_encode($jsonData, JSON_UNESCAPED_UNICODE);
        $htmlString = $html ?? '';

        $this->svc->saveResume($id, $jsonString, $htmlString, $accepted);
        return ['success' => true];
    }
}