<?php
require_once __DIR__ . '/backend/bootstrap.php';
require_once __DIR__ . '/backend/check_login.php';
require_once __DIR__ . '/backend/prompt.php';
require_once __DIR__ . '/backend/api.php';

if ($role === 'guest' || !isset($_SESSION['USER'])) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

define('REPLICATE_MODEL', 'sdxl-based/realvisxl-v3-multi-controlnet-lora:90a4a3604cd637cb9f1a2bdae1cfa9ed869362ca028814cdce310a78e27daade');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $prompt      = $_POST['prompt'];
        $mime        = validate_image($_FILES['character_image']);
        $image_b64   = file_to_base64($_FILES['character_image']['tmp_name'], $mime);
        // here the build_params function in prompt.php is being called 
        $params = build_params($prompt, $image_b64);
        $prediction = post_json('https://api.replicate.com/v1/predictions', $params, $REPLICATE_API_KEY);
        $prediction_id = $prediction['id'];
        $poll_url      = 'https://api.replicate.com/v1/predictions/' . $prediction_id;
        $max_attempts  = 30;
        $output_url    = null;

        for ($i = 0; $i < $max_attempts; $i++) {
            sleep(3);
            $result = get_json($poll_url, $REPLICATE_API_KEY);
            $status = $result['status'];

            if ($status === 'succeeded') {
                $output_url = $result['output'][2] ?? null;
                break;
            }
            if ($status === 'failed' || $status === 'canceled') {
                throw new Exception("Replicate: " . ($result['error'] ?? 'Generation failed.'));
            }
        }

        if ($output_url === null) {
            throw new Exception("Generation timed out. Try again in a moment.");
        }

        echo json_encode(['success' => true, 'image_url' => $output_url]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    exit;
}

require_once __DIR__ . '/templates/header.php';
?>

<div class="card">
    <form id="storyboard-form" enctype="multipart/form-data">

        <div class="field">
            <label for="prompt">Scene Description</label>
            <textarea
                id="prompt"
                name="prompt"
                placeholder="e.g. Hero enters a dark warehouse, low angle, dramatic shadows, tense mood"
                required></textarea>
        </div>

        <div class="field">
            <label for="character-image">Character Reference Image</label>
            <div class="upload-zone" id="upload-zone">
                <input
                    type="file"
                    name="character_image"
                    id="character-image"
                    accept="image/jpeg, image/png, image/webp"
                    required>
                <div class="upload-text">Click or drag an image here</div>
            </div>
            <img id="image-preview" alt="Uploaded character reference">
        </div>

        <button type="submit" id="submit-btn">Generate Storyboard Panel</button>

    </form>

    <div id="status"></div>
</div>

<div id="output">
    <h2>Generated Panel</h2>
    <img id="result-img" alt="Generated storyboard panel">
</div>

<script type="module" src="<?=BASE_URL?>/frontend/app.js"></script>
</body>
</html>