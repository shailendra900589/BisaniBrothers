<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/db.php';

if (!empty($_POST['website_check'])) {
    echo json_encode(['ok' => false, 'message' => 'Spam detected']);
    exit;
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) {
    echo json_encode(['ok' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO newsletter_subscribers (email, status) VALUES (?, ?) ON DUPLICATE KEY UPDATE status = ?');
    $stmt->execute([$email, 'active', 'active']);
    echo json_encode(['ok' => true, 'message' => 'Thank you! You are subscribed to Bisani Brothers insights.']);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'message' => 'Could not subscribe. Please try again later.']);
}
