<?php
require_once __DIR__ . '/../includes/security.php';
security_bootstrap();
header('Content-Type: application/json; charset=utf-8');

if (!security_admin_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'admin' && $role !== 'marketer') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Forbidden']);
    exit;
}

require '../db.php';
require_once '../includes/marketing-helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

if (!security_verify_csrf($input['_csrf'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Invalid security token']);
    exit;
}

$campaignId = (int) ($input['campaign_id'] ?? 0);
$offset = max(0, (int) ($input['offset'] ?? 0));
$limit = min(15, max(1, (int) ($input['limit'] ?? 10)));

if ($campaignId <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid campaign']);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM marketing_campaigns WHERE id = ?');
$stmt->execute([$campaignId]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$campaign) {
    echo json_encode(['ok' => false, 'message' => 'Campaign not found']);
    exit;
}

$selectedIds = json_decode($campaign['recipient_emails'] ?? '[]', true) ?: [];
$recipients = marketing_get_recipients($pdo, $campaign['recipient_mode'], $selectedIds);
$batch = array_slice($recipients, $offset, $limit);

$sent = (int) $campaign['sent_count'];
$failed = (int) $campaign['failed_count'];
$batchSent = 0;
$batchFailed = 0;

foreach ($batch as $row) {
    $email = $row['email'];
    if (marketing_send_to_recipient($campaign['subject'], $campaign['body_html'], $email)) {
        $sent++;
        $batchSent++;
        marketing_log_recipient($pdo, $campaignId, $email, 'sent');
    } else {
        $failed++;
        $batchFailed++;
        marketing_log_recipient($pdo, $campaignId, $email, 'failed');
    }
    usleep(150000);
}

$total = count($recipients);
$nextOffset = $offset + $limit;
$done = $nextOffset >= $total;
$status = $done ? ($failed > 0 && $sent === 0 ? 'failed' : 'sent') : 'sending';

marketing_update_campaign_counts($pdo, $campaignId, $sent, $failed, $status);

echo json_encode([
    'ok'           => true,
    'batch_sent'   => $batchSent,
    'batch_failed' => $batchFailed,
    'total_sent'   => $sent,
    'total_failed' => $failed,
    'total'        => $total,
    'next_offset'  => $nextOffset,
    'done'         => $done,
    'status'       => $status,
]);
