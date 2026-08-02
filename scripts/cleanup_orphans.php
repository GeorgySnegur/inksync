<?php
// cleanup_orphans.php — delete generated images that were never saved to the DB.
//
// When does an orphan appear?
//   A user generates a panel → image is downloaded to storage/ immediately →
//   but they close the tab without clicking "Save". The file sits on disk forever.
//
// Run this from the command line (not via browser — no auth needed, no web access required):
//   php scripts/cleanup_orphans.php
//   php scripts/cleanup_orphans.php --dry-run   (lists orphans without deleting)
//
// Safe to run at any time. Files newer than 2 hours are kept even if not in DB
// (the user might still be working on them).

define('ORPHAN_AGE_SECONDS', 2 * 60 * 60); // 2 hours
$dry_run = in_array('--dry-run', $argv ?? []);

// Bootstrap gives us $dbh. SESSION calls are no-ops on CLI — that's fine.
require_once __DIR__ . '/../backend/bootstrap.php';

$storage_root = __DIR__ . '/../storage/panels';

if (!is_dir($storage_root)) {
    echo "Storage directory does not exist: $storage_root\n";
    exit(0);
}

// 1. Load all known image paths from the DB
$known = [];
$rows = $dbh->query("SELECT image_url FROM storyboard_panels WHERE image_url LIKE '/storage/panels/%'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($rows as $path) {
    // Normalise to the basename so we can match against the filesystem
    $known[basename($path)] = true;
}

// 2. Walk through every file in storage/panels/{user_id}/
$deleted = 0;
$skipped_new = 0;
$user_dirs = glob($storage_root . '/*', GLOB_ONLYDIR);

foreach ($user_dirs as $user_dir) {
    foreach (glob($user_dir . '/*.jpg') as $file) {

        $age = time() - filemtime($file);

        // Keep files that are less than 2 hours old — user might still be working
        if ($age < ORPHAN_AGE_SECONDS) {
            $skipped_new++;
            continue;
        }

        // Keep files that exist in the DB
        if (isset($known[basename($file)])) {
            continue;
        }

        // This file is old and has no DB record — it's an orphan
        if ($dry_run) {
            echo "[DRY RUN] Would delete: $file\n";
        } else {
            unlink($file);
            echo "Deleted orphan: $file\n";
        }
        $deleted++;
    }
}

// 3. Remove empty user directories
foreach ($user_dirs as $user_dir) {
    if (count(glob($user_dir . '/*')) === 0) {
        if ($dry_run) {
            echo "[DRY RUN] Would remove empty dir: $user_dir\n";
        } else {
            rmdir($user_dir);
        }
    }
}

$label = $dry_run ? 'Would delete' : 'Deleted';
echo "\nDone. $label: $deleted orphan(s). Skipped (too new): $skipped_new.\n";
