<?php

require_once __DIR__ . '/mail-smtp.php';
require_once __DIR__ . '/mail-local.php';
require_once __DIR__ . '/mail-profiles.php';

function mail_send(string|array $to, string $subject, string $bodyHtml, ?string $replyTo = null, array $extraHeaders = []): bool
{
    $cfg = mail_load_config();
    $fromEmail = $cfg['from_email'] ?? 'marketing@bisanibrother.com';
    $fromName  = $cfg['from_name'] ?? 'Bisani Brothers Website';
    $recipients = is_array($to) ? $to : [$to];
    $isLocal = mail_is_local_env();
    $hasSmtpAuth = trim((string) ($cfg['smtp']['username'] ?? '')) !== '';

    // Localhost without SMTP credentials: save to outbox instantly (XAMPP cannot use Google relay).
    if ($isLocal && !$hasSmtpAuth) {
        if (mail_send_outbox($recipients, $subject, $bodyHtml, $fromEmail, $fromName, $replyTo)) {
            return true;
        }
    }

    if (!$isLocal || $hasSmtpAuth) {
        $errors = [];
        foreach (mail_smtp_profiles($cfg) as $profile) {
            $tryCfg = $cfg;
            $tryCfg['smtp'] = array_merge($cfg['smtp'] ?? [], $profile);
            if (mail_smtp_send($tryCfg, $recipients, $subject, $bodyHtml, $replyTo, $extraHeaders)) {
                $label = (string) ($profile['label'] ?? $tryCfg['smtp']['host'] ?? 'smtp');
                mail_set_meta('smtp', 'Email sent via ' . $label . ' (' . ($tryCfg['smtp']['host'] ?? '') . ').');
                return true;
            }
            $errors[] = ($profile['label'] ?? 'smtp') . ': ' . mail_smtp_last_error();
        }
        error_log('SMTP all profiles failed: ' . implode(' | ', $errors));
    }

    if ($isLocal && $hasSmtpAuth) {
        foreach (mail_local_smtp_profiles() as $profile) {
            $tryCfg = $cfg;
            $tryCfg['smtp'] = array_merge($cfg['smtp'] ?? [], $profile);
            if (mail_smtp_send($tryCfg, $recipients, $subject, $bodyHtml, $replyTo, $extraHeaders)) {
                mail_set_meta('local_smtp', 'Email sent via local mail catcher (' . $profile['host'] . ':' . $profile['port'] . ').');
                return true;
            }
        }
    }

    error_log('SMTP send failed, falling back to PHP mail().');
    $joined = implode(', ', $recipients);
    if (mail_send_php($joined, $subject, $bodyHtml, $fromEmail, $fromName, $replyTo, $extraHeaders)) {
        mail_set_meta('php_mail', 'Email sent via PHP mail().');
        return true;
    }

    if ($isLocal && mail_send_outbox($recipients, $subject, $bodyHtml, $fromEmail, $fromName, $replyTo)) {
        return true;
    }

    $smtpErr = mail_smtp_last_error();
    mail_set_meta('failed', $isLocal
        ? 'Could not send. Check storage/mail-outbox folder permissions.'
        : ($smtpErr !== ''
            ? 'Could not send: ' . $smtpErr
            : 'Could not send. Add server IP to Google SMTP relay, set BISANI_SMTP_USER/PASS in Plesk, or enable Plesk mail on port 25.'));
    return false;
}

/**
 * Test each SMTP profile (admin diagnostics).
 *
 * @return array<int, array{label: string, ok: bool, error: string}>
 */
function mail_diagnose_smtp(): array
{
    $cfg = mail_load_config();
    $results = [];
    foreach (mail_smtp_profiles($cfg) as $profile) {
        $tryCfg = $cfg;
        $tryCfg['smtp'] = array_merge($cfg['smtp'] ?? [], $profile);
        $label = (string) ($profile['label'] ?? 'smtp');
        $ok = mail_smtp_send($tryCfg, [$cfg['from_email'] ?? 'marketing@bisanibrother.com'], 'SMTP Test', '<p>Bisani Brothers SMTP diagnostic.</p>');
        $results[] = [
            'label' => $label,
            'host'  => (string) ($tryCfg['smtp']['host'] ?? ''),
            'port'  => (int) ($tryCfg['smtp']['port'] ?? 0),
            'ok'    => $ok,
            'error' => $ok ? '' : mail_smtp_last_error(),
        ];
        if ($ok) {
            break;
        }
    }

    return $results;
}

function mail_enquiry_notification(string $type, array $data): void
{
    $cfg = mail_load_config();
    $notifyList = $cfg['notify_to'] ?? ['contact@bisanibrother.com', 'marketing@bisanibrother.com'];

    $subject = '[New Enquiry] ' . ($data['source'] ?? $type) . ' — ' . ($data['name'] ?? 'Unknown');
    $rows = '';
    foreach ($data as $key => $val) {
        if ($val === '' || $val === null) {
            continue;
        }
        $label = htmlspecialchars(ucwords(str_replace('_', ' ', $key)), ENT_QUOTES, 'UTF-8');
        $rows .= '<tr><td style="padding:8px 12px;font-weight:bold;color:#173978;border-bottom:1px solid #eee;">' . $label
            . '</td><td style="padding:8px 12px;border-bottom:1px solid #eee;">' . nl2br(htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8')) . '</td></tr>';
    }
    $body = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">'
        . '<div style="background:#173978;color:#fff;padding:16px 20px;"><h2 style="margin:0;font-size:18px;">New Website Enquiry</h2></div>'
        . '<table style="width:100%;border-collapse:collapse;background:#fff;">' . $rows . '</table>'
        . '<p style="font-size:12px;color:#888;padding:12px;">Sent instantly from Bisani Brothers website · ' . date('Y-m-d H:i:s') . '</p></div>';

    $replyTo = !empty($data['email']) ? ($data['name'] ?? 'Visitor') . ' <' . $data['email'] . '>' : null;
    mail_send($notifyList, $subject, $body, $replyTo);
}
