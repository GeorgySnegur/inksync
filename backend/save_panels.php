<?php
require_once __DIR__ . '/backend/bootstrap.php';

$project_id = $_POST['project_id'];
$panels = json_decode($_POST['panels'], true);

try {
    $stmt = $dbh->prepare(
        "INSERT INTO storyboard_panels 
        (project_id, user_id, shot_number, prompt, image_url)
        VALUES (?, ?, ?, ?, ?, ?)
        ON CONFLICT (project_id, shot_id)
        DO UPDATE SET prompt = EXCLUDED.prompt, image_url = EXCLUDED.image_url" //if conflict happens (because this shot was already saved), overwrite old image and prompt
    );

    foreach ($panels as $panel) {
        $stmt->execute([ $project_id, $_SERVER['USER']['id'], $panel['shot_number'], $panel['prompt'], $panel['image_url'], $project_id);
    }

    echojson_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

