<?php
require_once __DIR__ . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

// Reject requests with a missing or wrong CSRF token (sent as X-CSRF-Token header by fetch())
csrf_verify_header();

if ($role === 'guest' || !isset($_SESSION['USER'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit;
}

$data       = json_decode(file_get_contents('php://input'), true);
$project_id = (int)($data['project_id'] ?? 0);
$user_id    = (int)$_SESSION['USER']['user_id'];

// --- IDOR check: project must belong to the logged-in user ---
// Used by the PDF exporter, which needs every panel in the project
// (not just the page currently shown on screen).
$stmt = $dbh->prepare("SELECT project_id FROM projects WHERE project_id = ? AND user_id = ?");
$stmt->execute([$project_id, $user_id]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied.']);
    exit;
}

$stmt = $dbh->prepare(
    "SELECT shot_number, image_url, prompt, notes
     FROM storyboard_panels
     WHERE project_id = ?
     ORDER BY shot_number"
);
$stmt->execute([$project_id]);
$panels = $stmt->fetchAll();

echo json_encode(['success' => true, 'panels' => $panels]);
