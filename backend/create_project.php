<?php

// create_project.php — create a new, empty project for the logged-in user.
// Used by the storyboard page when the user clicks "Save" without an
// active project: we create the project first, then save_panels.php is
// called against the new project_id.

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

if (!isset($_SESSION['USER'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit;
}
csrf_verify_header();

$data     = json_decode(file_get_contents('php://input'), true);
$name     = trim($data['name'] ?? '');
$user_id  = (int)$_SESSION['USER']['user_id'];

if (strlen($name) === 0 || strlen($name) > 50) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Name must be 1–50 characters.']);
    exit;
}

try {
    $stmt = $dbh->prepare("INSERT INTO projects (user_id, name) VALUES (?, ?) RETURNING project_id");
    $stmt->execute([$user_id, $name]);
    $project_id = (int)$stmt->fetchColumn();
    echo json_encode(['success' => true, 'project_id' => $project_id]);
} catch (PDOException $e) {
    // Unique constraint violation: this user already has a project with that name
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => 'You already have a project named "' . htmlspecialchars($name) . '".']);
}
