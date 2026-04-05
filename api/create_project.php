<?php
// api/create_project.php
// POST endpoint: create a new resume project.
// Every api/ file follows the same pattern:
//   1. session_start()
//   2. Ensure user exists in DB
//   3. Call controller
//   4. Return JSON

session_start();

require_once __DIR__ . '/../services/ProjectService.php';

// Make sure we have a user_id in the session
 $_SESSION['user_id'] = (new ProjectService())->ensureUser(session_id());

require_once __DIR__ . '/../controllers/ProjectController.php';

header('Content-Type: application/json');
echo json_encode((new ProjectController())->create());