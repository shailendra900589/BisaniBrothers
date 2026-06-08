<?php
require_once __DIR__ . '/includes/meta-pixel.php';

if (meta_pixel_active()) {
    meta_pixel_fire_beacon();
}

header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

echo meta_pixel_transparent_gif();
