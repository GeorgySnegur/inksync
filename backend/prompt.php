<?php
require_once __DIR__ . '/bootstrap.php';

// define('REPLICATE_MODEL', 'sdxl-based/realvisxl-v3-multi-controlnet-lora:90a4a3604cd637cb9f1a2bdae1cfa9ed869362ca028814cdce310a78e27daade');

function build_params(string $prompt, string $image_b64): array
{
    $full_prompt = "professional black/white sketch in the style of sksfer, clean lineart, " . $prompt;
    $negative_prompt = "ai artifacts";
    // naked, missing eyes, bad fingers, bad anatomy, colorful, worst quality, realistic, low quality, photo

    return [
        'version' => REPLICATE_MODEL,
        'input'   => [
            "seed" =>   4771,
            "width" => 800,
            "height" => 600,
            "image" => $image_b64,
            "controlnet_1_image" => $image_b64,
            "controlnet_2_image" => $image_b64,
            "prompt" => $full_prompt,
            "refine" => "base_image_refiner",
            "scheduler" => "DPMSolverMultistep",
            "lora_scale" => 0.6,
            "num_outputs" => 1,
            "controlnet_1" => "edge_canny",
            "controlnet_2" => "lineart",
            "controlnet_3" => "none",
            "lora_weights" => "https://pbxt.replicate.delivery/3wwmvGfvB4weYkJMAR2JJNMXu7RPtd8Hc5ONP3IP23fioXfGB/trained_model.tar",
            "refine_steps" => 10,
            "guidance_scale" => 3,
            "apply_watermark" => false,
            "negative_prompt" => $negative_prompt,
            "prompt_strength" => 0.9,
            "sizing_strategy" => "controlnet_1_image",
            "controlnet_1_end" => 1,
            "controlnet_2_end" => 1,
            "controlnet_3_end" => 1,
            "controlnet_1_start" => 0,
            "controlnet_2_start" => 0,
            "controlnet_3_start" => 0,
            "num_inference_steps" => 20,
            "controlnet_1_conditioning_scale" => 0.1,
            "controlnet_2_conditioning_scale" => 0.1,
            "controlnet_3_conditioning_scale" => 0
        ]
    ];
}
