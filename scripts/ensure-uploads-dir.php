<?php
/**
 * Create uploads folders and fix IIS permissions after Git pull.
 * Usage: php scripts/ensure-uploads-dir.php
 */
$root = dirname(__DIR__);
require_once $root . '/includes/security.php';

$subs = ['', 'resumes', 'marketing'];
$failed = false;

foreach ($subs as $sub) {
    $result = security_ensure_upload_dir($sub);
    $label = $sub === '' ? 'uploads' : 'uploads/' . $sub;
    if ($result['ok']) {
        echo "OK: {$label}\n";
        continue;
    }
    echo "FAIL: {$label} — {$result['error']}\n";
    $failed = true;
}

exit($failed ? 1 : 0);
