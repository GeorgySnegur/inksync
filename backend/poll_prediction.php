<?php
// poll_prediction.php — called by the JS every 3 s to check on a running Replicate prediction.
//
// Why POST (not GET)?
//   On success this endpoint has side effects: it downloads the image and records the generation.
//   POST + CSRF header keeps that consistent with every other state-changing endpoint.
//
// Response shape:
//   { status: 'starting' | 'processing' }   — still running, JS should keep polling
//   { status: 'succeeded', image_url: '/storage/panels/...' }
//   { status: 'failed' | 'canceled', error: '...' }
//   { success: false, error: '...' }         — request-level error (bad token, not logged in, etc.)

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/api.php';
require_once __DIR__ . '/storage.php';

header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION['USER'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit;
}

// CSRF check
csrf_verify_header();

$data = json_decode(file_get_contents('php://input'), true);
$prediction_id = $data['prediction_id'] ?? '';

// Validate the ID format — Replicate uses lowercase alphanumeric, ~25 chars
if (!preg_match('/^[a-z0-9]{10,40}$/', $prediction_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid prediction ID.']);
    exit;
}

// Guard against counting / downloading the same prediction twice
// (e.g. if a slow network causes two poll responses to arrive out of order)
if (!isset($_SESSION['processed_predictions'])) {
    $_SESSION['processed_predictions'] = [];
}
if (isset($_SESSION['processed_predictions'][$prediction_id])) {
    echo json_encode(['status' => 'succeeded', 'image_url' => $_SESSION['processed_predictions'][$prediction_id]]);
    exit;
}

// Everything below can throw on a transient hiccup — a flaky outbound call to
// Replicate, a failed image download, or a DB error. Nothing here is marked
// as "processed" until it all succeeds, so on any failure it's always safe to
// just tell the client "still processing" and let the next 3 s poll retry,
// rather than letting an uncaught exception crash into a raw 500.
try {
    $result = get_json('https://api.replicate.com/v1/predictions/' . $prediction_id, $REPLICATE_API_KEY);
    $status = $result['status'] ?? 'unknown';

    if ($status === 'succeeded') {

        $output_url = $result['output'][2] ?? null;
        if (!$output_url) {
            echo json_encode(['status' => 'failed', 'error' => 'Replicate returned no output URL.']);
            exit;
        }

        $user_id = (int)$_SESSION['USER']['user_id'];

        // Download from Replicate and save locally as a compressed JPEG
        $relative_path = download_and_store_image($output_url, $user_id);

        // Record the generation for the daily rate-limit counter
        $dbh->prepare("INSERT INTO image_generations (user_id) VALUES (?)")->execute([$user_id]);

        // Remember we processed this prediction so a late duplicate poll doesn't re-download
        $_SESSION['processed_predictions'][$prediction_id] = $relative_path;

        echo json_encode(['status' => 'succeeded', 'image_url' => $relative_path]);

    } elseif ($status === 'failed' || $status === 'canceled') {

        echo json_encode(['status' => $status, 'error' => $result['error'] ?? 'Generation failed.']);

    } else {

        // still running: 'starting' or 'processing'
        echo json_encode(['status' => $status]);

    }
} catch (\Throwable $e) {
    error_log('poll_prediction.php: ' . $e->getMessage());
    // TEMP DEBUG: surfacing the real error in the response so it's visible in
    // DevTools without needing server log access. Remove the 'debug' field
    // once the uni-server issue is diagnosed.
    echo json_encode(['status' => 'processing', 'notice' => 'retrying', 'debug' => $e->getMessage()]);
}
