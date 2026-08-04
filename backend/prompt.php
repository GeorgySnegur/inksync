<?php

// Note: bootstrap.php isn't required here on purpose -- every real call site
// (index.php, warmup.php) already loads it before this file, and build_params()
// itself needs nothing from it (no $dbh, no session). Keeping it out lets this
// file load standalone in unit tests without a DB connection. See tests/Unit/PromptTest.php.

// define('REPLICATE_MODEL', 'sdxl-based/realvisxl-v3-multi-controlnet-lora:90a4a3604cd637cb9f1a2bdae1cfa9ed869362ca028814cdce310a78e27daade');

// Phase 5: three tunable parameters exposed via the UI sliders.
// Defaults match the values that were previously hardcoded.
function build_params(
    string $prompt,
    string $image_b64,
    float $prompt_strength = 0.9, // how much the prompt overrides the reference image
    float $controlnet_scale = 0.2, // applied to both controlnet_1 and controlnet_2
    float $lora_scale = 0.9, // strength of the sketch LoRA
    bool $colorful = false, // unchecked = black & white sketch, checked = colorful
    bool $realistic = false  // unchecked = sketch (sksfer LoRA trigger), checked = realistic
): array {
    // System prompt prefix: "colorful" and "realistic" are independent,
    // modular toggles from the Advanced Options checkboxes.
    //   - $colorful switches "bw" vs. "with color" wording.
    //   - $realistic drops the "in the style of sksfer" LoRA trigger word
    //     (the sketch LoRA shouldn't be invoked for realistic output) and
    //     inserts "realistic" immediately after "illustration".
    $style_prefix = $colorful
        ? "professional illustration" . ($realistic ? " realistic" : "") . " with color "
        : "professional bw illustration" . ($realistic ? " realistic " : " ");
    $style_prefix .= $realistic ? "" : "in the style of sksfer ";
    $full_prompt     = $style_prefix . $prompt;
    $negative_prompt = "bad fingers, dismembered, lazy eye, bad anatomy, plain white background";
    return [
        'version' => REPLICATE_MODEL,
        'input'   => [
            "seed"             => 4771,
            "width"            => 800,
            "height"           => 600,
            "image"            => $image_b64,
            "controlnet_1_image" => $image_b64,
            "controlnet_2_image" => $image_b64,
            "prompt"           => $full_prompt,
            "refine"           => "base_image_refiner",
            "scheduler"        => "DPMSolverMultistep",
            "lora_scale"       => $lora_scale,
            "num_outputs"      => 1,
            "controlnet_1"     => "edge_canny",
            "controlnet_2"     => "lineart",
            "controlnet_3"     => "none",
            "lora_weights"     => "https://pbxt.replicate.delivery/3wwmvGfvB4weYkJMAR2JJNMXu7RPtd8Hc5ONP3IP23fioXfGB/trained_model.tar",
            "refine_steps"     => 10,
            "guidance_scale"   => 3,
            "apply_watermark"  => false,
            "negative_prompt"  => $negative_prompt,
            "prompt_strength"  => $prompt_strength,
            "sizing_strategy"  => "controlnet_1_image",
            "controlnet_1_end" => 1,
            "controlnet_2_end" => 1,
            "controlnet_3_end" => 1,
            "controlnet_1_start" => 0,
            "controlnet_2_start" => 0,
            "controlnet_3_start" => 0,
            "num_inference_steps" => 20,
            "controlnet_1_conditioning_scale" => $controlnet_scale,
            "controlnet_2_conditioning_scale" => $controlnet_scale,
            "controlnet_3_conditioning_scale" => 0,
            "disable_safety_checker" => true
        ]
    ];
}
