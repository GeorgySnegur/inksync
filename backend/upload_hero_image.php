<?php

// upload_hero_image.php — let a user manually set/override the hero image
// (thumbnail) shown for a project on the Projects page.
// Multipart POST: project_id, hero_image (file).

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/storage.php';

header('Content-Type: application/json');

if (!isset($_SESSION['USER'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit;
}
csrf_verify_header();

$project_id = (int)($_POST['project_id'] ?? 0);
$user_id    = (int)$_SESSION['USER']['user_id'];

// --- IDOR check: project must belong to the logged-in user ---
$stmt = $dbh->prepare("SELECT project_id FROM projects WHERE project_id = ? AND user_id = ?");
$stmt->execute([$project_id, $user_id]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Project not found or access denied.']);
    exit;
}

if (!isset($_FILES['hero_image']) || $_FILES['hero_image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No image uploaded or upload error.']);
    exit;
}

$file = $_FILES['hero_image'];

// Reject anything that isn't a genuine HTTP upload (defends against
// path-injection tricks that bypass the normal $_FILES mechanism)
if (!is_uploaded_file($file['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid upload.']);
    exit;
}

// 5 MB limit
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Image must be smaller than 5 MB.']);
    exit;
}

try {
    $relative_path = store_uploaded_image($file['tmp_name'], $user_id);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Could not process image: invalid or corrupt file.']);
    exit;
}

$dbh->prepare("UPDATE projects SET hero_image_url = ? WHERE project_id = ? AND user_id = ?")
    ->execute([$relative_path, $project_id, $user_id]);

echo json_encode(['success' => true, 'image_url' => $relative_path]);
