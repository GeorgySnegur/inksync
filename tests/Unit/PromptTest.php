<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../backend/prompt.php';

/**
 * ── PHPUnit crash course (read this once, applies to every file in tests/Unit/) ──
 *
 * - A test class extends TestCase. Each public test*() method is one
 *   independent test case, run in its own isolated instance of the class.
 * - assert*() calls (assertSame, assertStringContainsString, ...) are how a
 *   test states "this must be true" -- the test fails the instant one of
 *   them doesn't hold, and PHPUnit reports exactly which one and why.
 * - setUp()/tearDown() run before/after *every* test method in the class --
 *   used here (see ValidateImageTest, StorageTest) to create/clean up temp
 *   files so tests don't leave junk behind or depend on leftover state.
 * - Run the whole suite: `vendor/bin/phpunit`
 *   Run one file:        `vendor/bin/phpunit tests/Unit/PromptTest.php`
 *   Run one test:        `vendor/bin/phpunit --filter testStyleToggles...`
 *
 * build_params() is a pure function -- no DB, no session, no network. Same
 * input always produces the same output, so nothing here needs a mock.
 */
final class PromptTest extends TestCase
{
    public function testStyleTogglesChangeThePromptAndSlidersPassThrough(): void
    {
        // Default: black & white sketch, sksfer LoRA trigger word present.
        $default = build_params('a knight in a courtyard', 'data:image/png;base64,FAKE');
        $this->assertStringContainsString('bw illustration', $default['input']['prompt']);
        $this->assertStringContainsString('in the style of sksfer', $default['input']['prompt']);
        $this->assertStringContainsString('a knight in a courtyard', $default['input']['prompt']);

        // colorful=true drops the "bw" wording.
        $colorful = build_params('a dragon', 'data:image/png;base64,FAKE', colorful: true);
        $this->assertStringNotContainsString('bw illustration', $colorful['input']['prompt']);
        $this->assertStringContainsString('with color', $colorful['input']['prompt']);

        // realistic=true drops the sketch LoRA trigger word entirely.
        $realistic = build_params('a castle', 'data:image/png;base64,FAKE', realistic: true);
        $this->assertStringNotContainsString('sksfer', $realistic['input']['prompt']);

        // Slider values pass straight through to the Replicate params, and
        // controlnet_scale fans out to both controlnet_1 and controlnet_2.
        $sliders = build_params(
            'a forest',
            'data:image/png;base64,FAKE',
            prompt_strength: 0.42,
            controlnet_scale: 0.13,
            lora_scale: 0.77
        );
        $this->assertSame(0.42, $sliders['input']['prompt_strength']);
        $this->assertSame(0.13, $sliders['input']['controlnet_1_conditioning_scale']);
        $this->assertSame(0.13, $sliders['input']['controlnet_2_conditioning_scale']);
        $this->assertSame(0.77, $sliders['input']['lora_scale']);

        // The Replicate model version constant is attached as-is.
        $this->assertSame(REPLICATE_MODEL, $default['version']);
    }
}
