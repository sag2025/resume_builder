<?php
session_start();

require_once __DIR__ . '/../services/ProjectService.php';
 $_SESSION['user_id'] = (new ProjectService())->ensureUser(session_id());

require_once __DIR__ . '/../controllers/ProjectController.php';

header('Content-Type: application/json');
echo json_encode((new ProjectController())->saveResume());