<?php
/**
 * Serve uploaded files from primary or fallback storage (IIS/Plesk tmp).
 */
require_once __DIR__ . '/includes/upload-storage.php';

$f = isset($_GET['f']) ? (string) $_GET['f'] : '';
$f = str_replace('\\', '/', $f);
$f = ltrim($f, '/');

if ($f === '' || str_contains($f, '..') || !preg_match('#^[a-zA-Z0-9_./-]+$#', $f)) {
    http_response_code(400);
    exit('Bad request');
}

$physical = upload_storage_resolve_file('uploads/' . $f);
if ($physical === null || !is_file($physical)) {
    http_response_code(404);
    exit('Not found');
}

$ext = strtolower(pathinfo($physical, PATHINFO_EXTENSION));
$mimes = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$mime = $mimes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($physical));
header('Cache-Control: public, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');

readfile($physical);
