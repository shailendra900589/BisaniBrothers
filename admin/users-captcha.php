<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin']);

if (!isset($_GET['generate_captcha'])) {
    http_response_code(404);
    exit;
}

header('Content-Type: image/png');
$code = substr(str_shuffle('0123456789ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 5);
$_SESSION['custom_captcha'] = $code;

$im = imagecreate(120, 40);
$bg = imagecolorallocate($im, 241, 245, 249);
$fg = imagecolorallocate($im, 23, 57, 120);
$line_color = imagecolorallocate($im, 47, 202, 240);

for ($i = 0; $i < 5; $i++) {
    imageline($im, rand(0, 120), rand(0, 40), rand(0, 120), rand(0, 40), $line_color);
}

imagestring($im, 5, 35, 12, $code, $fg);
imagepng($im);
imagedestroy($im);
exit;
