<?php
require_once __DIR__ . '/backend/bootstrap.php';
require_once __DIR__ . '/backend/check_login.php';
require_once __DIR__ . '/backend/prompt.php';
require_once __DIR__ . '/backend/api.php';

define('REPLICATE_MODEL', 'sdxl-based/realvisxl-v3-multi-controlnet-lora:90a4a3604cd637cb9f1a2bdae1cfa9ed869362ca028814cdce310a78e27daade');

    
    try {
        header('Content-Type: application/json; charset=utf-8');
        
        $prompt = 'test warm up run for replicate';
        $image_b64 = "data:image/png;base64,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";
        $test_params = build_params($prompt, $image_b64);
        $prediction = post_json('https://api.replicate.com/v1/predictions', $test_params, $REPLICATE_API_KEY);

        // instantly cancel request https://replicate.com/docs/reference/http#predictions.cancel
        $prediction_id = $prediction['id'];
        post_json('https://api.replicate.com/v1/predictions/' . $prediction_id . '/cancel',[],$REPLICATE_API_KEY);

    } catch (Exception $e) {
        echo 'ERROR WARMUP';
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
