<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin', 'hr']);

require '../db.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit('Not found');
}

$stmt = $pdo->prepare('SELECT resume_path, applicant_name FROM applications WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row['resume_path'])) {
    http_response_code(404);
    exit('Not found');
}

$root = dirname(__DIR__);
$relative = ltrim(str_replace(['../', './'], '', (string) $row['resume_path']), '/');
$fullPath = realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
$resumesDir = realpath($root . '/uploads/resumes');

if ($fullPath === false || $resumesDir === false || !str_starts_with($fullPath, $resumesDir) || !is_file($fullPath)) {
    http_response_code(404);
    exit('Not found');
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mime = match ($ext) {
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    default => 'application/octet-stream',
};

$name = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) ($row['applicant_name'] ?? 'resume'));
$name = trim($name, '-') ?: 'resume';

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $name . '.' . $ext . '"');
header('Content-Length: ' . (string) filesize($fullPath));
header('X-Content-Type-Options: nosniff');
readfile($fullPath);
exit;
