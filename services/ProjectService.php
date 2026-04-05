<?php
// services/ProjectService.php
// All database operations for projects.
// This is the ONLY file that writes SQL queries.

require_once __DIR__ . '/../config/database.php';

class ProjectService {

    /**
     * Find or create anonymous user for this PHP session.
     * Returns the user's numeric ID.
     */
    public function ensureUser(string $sessionId): int {
        $db = Database::getConnection();

        // Try to find existing user
        $stmt = $db->prepare("SELECT id FROM anonymous_users WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch();

        if ($row) {
            return (int) $row['id'];
        }

        // Create new user
        $stmt = $db->prepare("INSERT INTO anonymous_users (session_id) VALUES (?)");
        $stmt->execute([$sessionId]);
        return (int) $db->lastInsertId();
    }

    /**
     * Create a new project. Returns the new project ID.
     */
    public function create(int $userId, string $name): int {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO user_projects (user_id, project_name) VALUES (?, ?)"
        );
        $stmt->execute([$userId, trim($name)]);
        return (int) $db->lastInsertId();
    }

    /**
     * List all projects for a user, newest first.
     */
    public function listByUser(int $userId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT id, project_name, is_accepted, created_at, updated_at
             FROM user_projects
             WHERE user_id = ?
             ORDER BY updated_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get a single project by ID.
     * Returns the full row or null if not found.
     */
    public function getById(int $projectId): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM user_projects WHERE id = ?");
        $stmt->execute([$projectId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Delete a project by ID.
     */
    public function delete(int $projectId): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM user_projects WHERE id = ?");
        $stmt->execute([$projectId]);
    }

    /**
     * Save the final resume JSON + HTML to MySQL.
     * ONLY called when user clicks Accept or closes the window.
     * $json is a JSON string (the nested resume data).
     * $html is the generated HTML string.
     */
    public function saveResume(int $projectId, string $json, string $html, bool $accepted): void {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "UPDATE user_projects
             SET resume_json = ?, resume_html = ?, is_accepted = ?
             WHERE id = ?"
        );
        $stmt->execute([$json, $html, $accepted ? 1 : 0, $projectId]);
    }
}