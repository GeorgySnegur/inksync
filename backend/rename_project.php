<?php

// rename_project.php — rename a project for the logged-in user.

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

if (!isset($_SESSION['USER'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit;
}
csrf_verify_header();

$data       = json_decode(file_get_contents('php://input'), true);
$project_id = (int)($data['project_id'] ?? 0);
$new_name   = trim($data['name'] ?? '');
$user_id    = (int)$_SESSION['USER']['user_id'];

if (strlen($new_name) === 0 || strlen($new_name) > 50) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Name must be 1–50 characters.']);
    exit;
}

// IDOR check
$stmt = $dbh->prepare("SELECT project_id FROM projects WHERE project_id = ? AND user_id = ?");
$stmt->execute([$project_id, $user_id]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Project not found or access denied.']);
    exit;
}

try {
    $dbh->prepare("UPDATE projects SET name = ? WHERE project_id = ? AND user_id = ?")
        ->execute([$new_name, $project_id, $user_id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Unique constraint violation: this user already has a project with that name
    echo json_encode(['success' => false, 'error' => 'You already have a project named "' . htmlspecialchars($new_name) . '".']);
}
