<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin', 'marketer']);

require_once __DIR__ . '/../includes/mail-helpers.php';

header('Content-Type: application/json; charset=utf-8');

$cfg = mail_load_config();
$hasAuth = trim((string) ($cfg['smtp']['username'] ?? '')) !== '';
$results = mail_diagnose_smtp();
$working = null;
foreach ($results as $row) {
    if ($row['ok']) {
        $working = $row;
        break;
    }
}

echo json_encode([
    'ok'        => $working !== null,
    'has_auth'  => $hasAuth,
    'working'   => $working,
    'profiles'  => $results,
    'from'      => $cfg['from_email'] ?? '',
    'message'   => $working
        ? 'SMTP OK via ' . $working['label']
        : 'No SMTP profile succeeded. See profiles for details.',
], JSON_UNESCAPED_UNICODE);
