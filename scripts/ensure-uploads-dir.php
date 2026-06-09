<?php
/**
 * Create uploads folders and fix IIS permissions after Git pull.
 * Usage: php scripts/ensure-uploads-dir.php
 */
$root = dirname(__DIR__);
require_once $root . '/includes/upload-storage.php';

$subs = ['', 'resumes', 'marketing'];
$failed = false;

$active = upload_storage_active_root(true);
echo 'Active storage: ' . ($active ?? 'NONE') . "\n";

foreach ($subs as $sub) {
    $result = upload_storage_ensure_dir($sub);
    $label = $sub === '' ? 'uploads' : 'uploads/' . $sub;
    if ($result['ok']) {
        echo "OK: {$label} @ {$result['path']}\n";
        continue;
    }
    echo "FAIL: {$label} — {$result['error']}\n";
    $failed = true;
}

exit($failed ? 1 : 0);
