<?php
require_once BASE_URL . '/backend/bootstrap.php';
header('Content-Type: application/json'); //when i send back an echo json_encode nack to index, i need this header type

// $project_id = $_POST['project_id'];
// $panels = json_decode($_POST['panels'], true);


$data = json_decode(file_get_contents('php://input'), true);
$project_id = $data['project_id'];
$panels = $data['panels'];

$user_id = $_SESSION['USER']['user_id'];

try {
    $stmt = $dbh->prepare(
        "INSERT INTO storyboard_panels 
        (project_id, user_id, shot_number, prompt, image_url)
        VALUES (?, ?, ?, ?, ?)
        ON CONFLICT (project_id, shot_number)
        DO UPDATE SET prompt = EXCLUDED.prompt, image_url = EXCLUDED.image_url"
    );

    foreach ($panels as $panel) {
        $stmt->execute([$project_id, $user_id, $panel['shot_number'], 
            $panel['prompt'], $panel['image_url']]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}