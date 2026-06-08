<?php
require_once __DIR__ . '/includes/meta-pixel.php';

header('Content-Type: application/javascript; charset=UTF-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');

echo meta_pixel_emit_script();
