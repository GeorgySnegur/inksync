<?php

// storage.php — helpers for saving and deleting generated panel images.
//
// Images are stored at:  storage/panels/{user_id}/{uuid}.jpg
// DB stores the relative path (e.g. /storage/panels/2/abc123.jpg)
// so it survives the localhost <-> uni-server hostname switch.

// Absolute path to the storage root (used internally — never sent to clients)
define('STORAGE_ROOT', __DIR__ . '/../storage/panels');

// Relative path prefix as stored in the DB and served via BASE_URL
define('PANEL_STORAGE_PATH', '/storage/panels/');


// Shared by download_and_store_image() and store_uploaded_image(): create the
// per-user storage dir if needed, save the given GD image resource as a JPEG
// under a collision-free filename, and return the DB-relative path.
//
// Pulled out on its own so it's unit-testable without a network call
// (download_and_store_image) or a real uploaded file (store_uploaded_image) —
// both of those just need to hand it a decoded GD image resource.
function save_gd_image_and_build_relative_path($image, int $user_id): string
{
    // Create the per-user storage directory if it doesn't exist yet.
    // Mode 0775 (not 0755) so the web server's group also gets write access —
    // on shared hosting the PHP process and the file owner are often different
    // users that only share a group.
    $dir = STORAGE_ROOT . '/' . $user_id;
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new Exception("Could not create storage directory: $dir (check filesystem permissions on STORAGE_ROOT = " . STORAGE_ROOT . ")");
        }
    }
    if (!is_writable($dir)) {
        throw new Exception("Storage directory is not writable: $dir (check file permissions / ownership on the server)");
    }

    // Generate a collision-free filename — never derived from user input (no path traversal)
    $uuid     = bin2hex(random_bytes(16));
    $filename = $uuid . '.jpg';
    $abs_path = $dir . '/' . $filename;

    // Save as JPEG at quality 85.
    // 85 is the sweet spot: visually lossless vs the AI-generated PNG,
    // but ~3-5x smaller file size.
    $saved = imagejpeg($image, $abs_path, 85);
    imagedestroy($image);

    if (!$saved) {
        throw new Exception("Could not write image to disk at $abs_path (imagejpeg() returned false — likely a permissions or disk-space issue)");
    }

    // Return the relative path (no hostname) so it works on both localhost and the uni server
    return PANEL_STORAGE_PATH . $user_id . '/' . $filename;
}

// Download a Replicate output image, compress it to JPEG, and save it locally.
// Returns the relative path suitable for storing in the DB and serving via BASE_URL.
function download_and_store_image(string $replicate_url, int $user_id): string
{
    // 1. Download the raw image bytes from Replicate
    $raw = file_get_contents($replicate_url);
    if ($raw === false) {
        throw new Exception("Could not download generated image from Replicate.");
    }

    // 2. Decode into a GD image resource — imagecreatefromstring() handles PNG, JPEG, WebP, etc.
    $image = imagecreatefromstring($raw);
    if ($image === false) {
        throw new Exception("Could not decode generated image.");
    }

    // 3. Save it and build the DB-relative path (shared with store_uploaded_image())
    return save_gd_image_and_build_relative_path($image, $user_id);
}


// Save a user-uploaded hero image (from <input type="file"> on the Projects
// page), re-encode it to JPEG, and save it locally. Re-encoding through GD
// (rather than just moving the uploaded file) strips EXIF/metadata and
// guarantees the saved file is genuinely an image, not a disguised script.
// Returns the relative path suitable for storing in the DB and serving via BASE_URL.
function store_uploaded_image(string $tmp_path, int $user_id): string
{
    // 1. Confirm this is really pixel data, not e.g. a renamed .php file
    $dims = @getimagesize($tmp_path);
    if ($dims === false) {
        throw new Exception("Uploaded file is not a valid image.");
    }

    // 1b. Decompression-bomb guard: a small file can still declare a huge
    // pixel grid and blow up memory when GD decodes it below. Reject
    // oversized dimensions before that happens.
    [$width, $height] = $dims;
    if ($width > 8000 || $height > 8000 || ($width * $height) > 40_000_000) {
        throw new Exception("Image resolution is too large.");
    }

    // 2. Decode into a GD image resource — imagecreatefromstring() handles PNG, JPEG, WebP, etc.
    $raw   = file_get_contents($tmp_path);
    $image = $raw !== false ? imagecreatefromstring($raw) : false;
    if ($image === false) {
        throw new Exception("Could not decode uploaded image.");
    }

    // 3. Save it and build the DB-relative path (shared with download_and_store_image())
    return save_gd_image_and_build_relative_path($image, $user_id);
}


// Delete a stored panel image from disk.
// Pass the relative path as it is stored in the DB (e.g. /storage/panels/2/abc.jpg).
// Called from Phase 6 when a panel or project is deleted.
// Safe to call with null or an external URL — it silently does nothing in those cases.
function delete_panel_image(?string $relative_path): void
{
    if ($relative_path === null) {
        return;
    }

    // Only delete files that live inside our own storage directory
    if (!str_starts_with($relative_path, PANEL_STORAGE_PATH)) {
        return;
    }

    $abs_path = __DIR__ . '/..' . $relative_path;

    // Resolve the real path and confirm it is still inside STORAGE_ROOT
    // (guards against path traversal like /../../../etc/passwd)
    $real = realpath($abs_path);
    if ($real === false) {
        return; // file doesn't exist
    }

    if (!str_starts_with($real, realpath(STORAGE_ROOT))) {
        return; // outside storage root
    }

    unlink($real);
}
