<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../backend/api.php';

/**
 * validate_image() checks the PHP upload-error code, a 5MB size cap, the
 * REAL mime type via finfo (not the client-supplied, spoofable
 * $_FILES['type']), and a decompression-bomb guard on declared dimensions.
 *
 * Fixture images are generated on the fly with GD instead of committing
 * binary files to the repo -- keeps the test self-contained and the repo
 * clean.
 */
final class ValidateImageTest extends TestCase
{
    /** @var string[] */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->tempFiles = [];
    }

    private function makeJpegFile(int $width = 10, int $height = 10): string
    {
        $path = tempnam(sys_get_temp_dir(), 'inksync_test_');
        $img  = imagecreatetruecolor($width, $height);
        imagejpeg($img, $path);
        imagedestroy($img);
        $this->tempFiles[] = $path;
        return $path;
    }

    private function fakeUpload(string $tmpPath): array
    {
        return [
            'name'     => 'upload.jpg',
            'type'     => 'image/jpeg', // deliberately untrusted -- see testRejectsASpoofedFileType
            'tmp_name' => $tmpPath,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($tmpPath),
        ];
    }

    public function testAcceptsARealSmallJpeg(): void
    {
        $mime = validate_image($this->fakeUpload($this->makeJpegFile()));
        $this->assertSame('image/jpeg', $mime);
    }

    public function testRejectsASpoofedFileType(): void
    {
        // Client-supplied $_FILES['type'] says "image/jpeg", but the actual
        // bytes are plain text -- finfo reads real content, not the label.
        $path = tempnam(sys_get_temp_dir(), 'inksync_test_');
        file_put_contents($path, '<?php echo "not an image"; ?>');
        $this->tempFiles[] = $path;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('JPEG, PNG, or WebP');

        validate_image($this->fakeUpload($path));
    }

    public function testRejectsOversizedDimensions(): void
    {
        // A tiny file can still declare huge pixel dimensions and blow up
        // memory when GD decodes it -- must be rejected before that happens.
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('resolution is too large');

        validate_image($this->fakeUpload($this->makeJpegFile(8001, 10)));
    }
}
