<?php
// delete_project.php — delete a project, all its panels, and all their image files.
// The DB cascade (ON DELETE CASCADE) removes panel rows automatically;
// we handle the image files manually here.

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/storage.php';

header('Content-Type: application/json');

if (!isset($_SESSION['USER'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit;
}
csrf_verify_header();

$data       = json_decode(file_get_contents('php://input'), true);
$project_id = (int)($data['project_id'] ?? 0);
$user_id    = (int)$_SESSION['USER']['user_id'];

// IDOR check
$stmt = $dbh->prepare("SELECT project_id FROM projects WHERE project_id = ? AND user_id = ?");
$stmt->execute([$project_id, $user_id]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Project not found or access denied.']);
    exit;
}

// Delete each panel's image file from disk
$panels = $dbh->prepare("SELECT image_url FROM storyboard_panels WHERE project_id = ?");
$panels->execute([$project_id]);
foreach ($panels->fetchAll() as $p) {
    delete_panel_image($p->image_url);
}

// Delete the project — cascades to storyboard_panels in the DB
$dbh->prepare("DELETE FROM projects WHERE project_id = ?")->execute([$project_id]);

echo json_encode(['success' => true]);
