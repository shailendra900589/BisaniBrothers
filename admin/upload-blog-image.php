<?php
/**
 * AJAX cover-image upload — small POST body avoids post_max_size issues with large articles.
 */
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin', 'writer']);

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if (empty($_FILES['image']['name'])) {
    echo json_encode(['ok' => false, 'error' => 'No image selected.']);
    exit;
}

$upload = security_store_upload($_FILES['image'], '', ['jpg', 'jpeg', 'png', 'gif', 'webp'], 10485760);

echo json_encode([
    'ok'    => $upload['ok'],
    'path'  => $upload['db_path'],
    'error' => $upload['error'],
], JSON_UNESCAPED_UNICODE);
