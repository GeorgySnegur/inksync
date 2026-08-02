<?php
require_once __DIR__ . '/bootstrap.php';
header('Content-Type: application/json');

// Reject requests with a missing or wrong CSRF token (sent as X-CSRF-Token header by fetch())
csrf_verify_header();

if (!isset($_SESSION['USER'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit;
}

$data       = json_decode(file_get_contents('php://input'), true);
$project_id = (int)($data['project_id'] ?? 0);
$panels     = $data['panels'] ?? null;
$user_id    = (int)$_SESSION['USER']['user_id'];

if (!is_array($panels)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No panels provided.']);
    exit;
}

try {
    // --- IDOR check: confirm this project belongs to the logged-in user ---
    // Without this, any logged-in user could overwrite another user's panels
    // by sending a different project_id from the client.
    $stmt = $dbh->prepare("SELECT user_id FROM projects WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();

    if (!$project || (int)$project->user_id !== (int)$user_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied.']);
        exit;
    }

    // --- Save panels ---
    // image_url is a relative path (e.g. /storage/panels/2/abc.jpg) from data-db-url.
    // notes is a free-text annotation added by the user (Phase 6).
    $stmt = $dbh->prepare(
        "INSERT INTO storyboard_panels
            (project_id, user_id, shot_number, prompt, image_url, notes)
         VALUES (?, ?, ?, ?, ?, ?)
         ON CONFLICT (project_id, shot_number)
         DO UPDATE SET
             prompt    = EXCLUDED.prompt,
             image_url = EXCLUDED.image_url,
             notes     = EXCLUDED.notes"
    );

    foreach ($panels as $panel) {
        $stmt->execute([
            $project_id,
            $user_id,
            $panel['shot_number'],
            $panel['prompt']    ?? '',
            $panel['image_url'] ?? null,
            $panel['notes']     ?? null,
        ]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not save panels. Please try again.']);
}