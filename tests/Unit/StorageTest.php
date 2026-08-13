<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../backend/storage.php';

/**
 * delete_panel_image() deletes a stored panel image by its DB-stored
 * relative path, but only if it resolves inside STORAGE_ROOT -- this is a
 * path-traversal guard, since the "relative path" ultimately comes from
 * data a user could have influenced. Worth testing directly: an attacker
 * feeding a ../../ path must not be able to delete arbitrary files.
 */
final class StorageTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        $this->testDir = STORAGE_ROOT . '/__phpunit_test__';
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0775, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            array_map('unlink', glob($this->testDir . '/*'));
            rmdir($this->testDir);
        }
    }

    public function testDeletesAFileInsideStorageRootButRefusesPathTraversal(): void
    {
        $file = $this->testDir . '/sample.jpg';
        file_put_contents($file, 'fake image bytes');
        $this->assertFileExists($file);

        delete_panel_image('/storage/panels/__phpunit_test__/sample.jpg');
        $this->assertFileDoesNotExist($file, 'a legitimate in-root path should be deleted');

        // Attempt to escape STORAGE_ROOT and delete a real project file.
        // composer.json (not backend/config.php -- that's gitignored and
        // wouldn't exist on a fresh CI checkout) is guaranteed to be present.
        $target = __DIR__ . '/../../composer.json';
        $this->assertFileExists($target, 'sanity check: the file we are trying to protect must exist');

        delete_panel_image('/storage/panels/__phpunit_test__/../../../composer.json');
        $this->assertFileExists($target, 'a path escaping STORAGE_ROOT must never be deleted');
    }

    /**
     * save_gd_image_and_build_relative_path() is the logic shared by
     * download_and_store_image() (needs a live Replicate call) and
     * store_uploaded_image() (needs a real uploaded file) -- pulled out so
     * the actual save-and-path-build behavior is testable with just a plain
     * GD image resource, no network or upload required.
     */
    public function testSaveGdImageAndBuildRelativePathSavesFileAndReturnsExpectedPath(): void
    {
        $userId = 999999; // fake id, distinct from the delete-test's __phpunit_test__ dir
        $dir    = STORAGE_ROOT . '/' . $userId;

        $image        = imagecreatetruecolor(2, 2);
        $relativePath = save_gd_image_and_build_relative_path($image, $userId);

        $this->assertStringStartsWith(PANEL_STORAGE_PATH . $userId . '/', $relativePath);
        $this->assertStringEndsWith('.jpg', $relativePath);

        $absPath = __DIR__ . '/../..' . $relativePath;
        $this->assertFileExists($absPath, 'the image should actually be written to disk');

        unlink($absPath);
        rmdir($dir);
    }
}
