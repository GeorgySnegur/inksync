<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../backend/api.php';

/**
 * file_to_base64() is a trivial pure function -- read bytes, base64-encode,
 * wrap in a data: URI. Good "does the harness even work" sanity test.
 */
final class FileToBase64Test extends TestCase
{
    public function testEncodesFileContentsAsADataUri(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'inksync_test_');
        file_put_contents($path, 'hello');

        try {
            $result = file_to_base64($path, 'image/png');
            $this->assertSame('data:image/png;base64,' . base64_encode('hello'), $result);
        } finally {
            unlink($path);
        }
    }
}
