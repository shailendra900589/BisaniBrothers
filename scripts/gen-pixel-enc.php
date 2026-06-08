<?php
/**
 * Generate SEO_META_PIXEL_ENC for includes/seo-config.php
 * Usage: php scripts/gen-pixel-enc.php YOUR_PIXEL_ID
 */
require __DIR__ . '/../includes/seo-config.php';

$id = trim($argv[1] ?? '');
if ($id === '' || !preg_match('/^\d{10,20}$/', $id)) {
    fwrite(STDERR, "Usage: php scripts/gen-pixel-enc.php PIXEL_ID\n");
    exit(1);
}

$key = hash('sha256', SEO_INDEXNOW_KEY, true);
$enc = '';
for ($i = 0; $i < strlen($id); $i++) {
    $enc .= chr(ord($id[$i]) ^ ord($key[$i % strlen($key)]));
}

echo "define('SEO_META_PIXEL_ENC', '" . bin2hex($enc) . "');\n";
