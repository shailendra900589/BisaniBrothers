<?php

function mail_is_local_env(): bool
{
    if (getenv('BISANI_LIVE')) {
        return false;
    }
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $host)) {
        return true;
    }
    return php_sapi_name() === 'cli' && !getenv('BISANI_LIVE');
}

function mail_set_meta(string $method, string $message, ?string $path = null): void
{
    $GLOBALS['_mail_send_meta'] = [
        'method'  => $method,
        'message' => $message,
        'path'    => $path,
    ];
}

function mail_send_meta(): array
{
    return $GLOBALS['_mail_send_meta'] ?? ['method' => 'unknown', 'message' => '', 'path' => null];
}

function mail_outbox_dir(): string
{
    $dir = dirname(__DIR__) . '/storage/mail-outbox';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function mail_send_outbox(array $recipients, string $subject, string $bodyHtml, string $fromEmail, string $fromName, ?string $replyTo = null): bool
{
    $dir = mail_outbox_dir();
    $safeSubject = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $subject);
    $safeSubject = substr($safeSubject, 0, 60) ?: 'email';
    $toLabel = preg_replace('/[^a-zA-Z0-9._@-]+/', '_', implode('-', $recipients));
    $filename = date('Y-m-d_His') . '_' . $safeSubject . '_' . substr($toLabel, 0, 40) . '.html';
    $path = $dir . '/' . $filename;

    $meta = [
        'to'      => $recipients,
        'from'    => $fromName . ' <' . $fromEmail . '>',
        'subject' => $subject,
        'reply_to'=> $replyTo,
        'sent_at' => date('Y-m-d H:i:s'),
        'mode'    => 'local_outbox',
    ];

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>'
        . htmlspecialchars($subject, ENT_QUOTES)
        . '</title><style>body{font-family:Arial,sans-serif;background:#f8fafc;margin:0;padding:20px}'
        . '.meta{background:#173978;color:#fff;padding:16px 20px;border-radius:12px 12px 0 0;font-size:13px}'
        . '.meta strong{display:inline-block;min-width:70px}.body{background:#fff;padding:24px;border:1px solid #e2e8f0;border-top:0;border-radius:0 0 12px 12px}'
        . '</style></head><body><div class="meta">'
        . '<p><strong>To:</strong> ' . htmlspecialchars(implode(', ', $recipients), ENT_QUOTES) . '</p>'
        . '<p><strong>From:</strong> ' . htmlspecialchars($meta['from'], ENT_QUOTES) . '</p>'
        . '<p><strong>Subject:</strong> ' . htmlspecialchars($subject, ENT_QUOTES) . '</p>'
        . '<p><strong>Saved:</strong> ' . htmlspecialchars($meta['sent_at'], ENT_QUOTES) . ' (localhost — not sent over internet)</p>'
        . '</div><div class="body">' . $bodyHtml . '</div></body></html>';

    if (file_put_contents($path, $html) === false) {
        return false;
    }

    file_put_contents($path . '.json', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    mail_set_meta(
        'outbox',
        'Local dev: email saved to outbox. Preview in Admin → Mail Outbox.',
        $filename
    );
    return true;
}

function mail_local_smtp_profiles(): array
{
    return [
        [
            'label'      => 'mailpit',
            'host'       => '127.0.0.1',
            'port'       => 1025,
            'encryption' => 'none',
            'username'   => '',
            'password'   => '',
            'timeout'    => 1,
        ],
        [
            'label'      => 'fakesmtp',
            'host'       => '127.0.0.1',
            'port'       => 2525,
            'encryption' => 'none',
            'username'   => '',
            'password'   => '',
            'timeout'    => 1,
        ],
    ];
}
