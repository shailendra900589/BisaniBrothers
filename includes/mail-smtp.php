<?php

function mail_load_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $config = require __DIR__ . '/mail-config.php';
    $local = __DIR__ . '/mail-config.local.php';
    if (is_file($local)) {
        $override = require $local;
        if (is_array($override)) {
            $config = array_replace_recursive($config, $override);
        }
    }

    return $config;
}

function mail_smtp_read($socket): string
{
    $data = '';
    while ($line = fgets($socket, 515)) {
        $data .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    return $data;
}

function mail_smtp_cmd($socket, string $cmd, array $okCodes): bool
{
    if ($cmd !== '') {
        fwrite($socket, $cmd . "\r\n");
    }
    $resp = mail_smtp_read($socket);
    $code = (int) substr($resp, 0, 3);
    return in_array($code, $okCodes, true);
}

function mail_smtp_encode_header(string $value): string
{
    if (preg_match('/[^\x20-\x7E]/', $value)) {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
    return $value;
}

function mail_smtp_send(array $cfg, array $recipients, string $subject, string $bodyHtml, ?string $replyTo = null, array $extraHeaders = []): bool
{
    $smtp = $cfg['smtp'] ?? [];
    $host = trim((string) ($smtp['host'] ?? ''));
    $port = (int) ($smtp['port'] ?? 587);
    if ($host === '' || $port <= 0) {
        return false;
    }

    $fromEmail = $cfg['from_email'] ?? 'marketing@bisanibrother.com';
    $fromName  = $cfg['from_name'] ?? 'Bisani Brothers Website';
    $timeout   = (int) ($smtp['timeout'] ?? 12);
    $encryption = strtolower((string) ($smtp['encryption'] ?? 'tls'));
    $username  = trim((string) ($smtp['username'] ?? ''));
    $password  = (string) ($smtp['password'] ?? '');

    $recipients = array_values(array_unique(array_filter(array_map(
        static fn($e) => filter_var(trim((string) $e), FILTER_VALIDATE_EMAIL) ?: null,
        $recipients
    ))));
    if ($recipients === []) {
        return false;
    }

    $remote = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $socket = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        error_log("SMTP connect failed ({$remote}): {$errstr} ({$errno})");
        return false;
    }

    stream_set_timeout($socket, $timeout);

    if (!mail_smtp_read($socket)) {
        fclose($socket);
        return false;
    }

    $ehloHost = $_SERVER['SERVER_NAME'] ?? 'bisanibrother.com';
    if (!mail_smtp_cmd($socket, 'EHLO ' . $ehloHost, [250])) {
        mail_smtp_cmd($socket, 'HELO ' . $ehloHost, [250]);
    }

    if ($encryption === 'tls') {
        if (!mail_smtp_cmd($socket, 'STARTTLS', [220])) {
            fclose($socket);
            return false;
        }
        $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        }
        if (!@stream_socket_enable_crypto($socket, true, $crypto)) {
            error_log('SMTP STARTTLS handshake failed.');
            fclose($socket);
            return false;
        }
        if (!mail_smtp_cmd($socket, 'EHLO ' . $ehloHost, [250])) {
            fclose($socket);
            return false;
        }
    }

    if ($username !== '' && $password !== '') {
        if (!mail_smtp_cmd($socket, 'AUTH LOGIN', [334])
            || !mail_smtp_cmd($socket, base64_encode($username), [334])
            || !mail_smtp_cmd($socket, base64_encode($password), [235])) {
            error_log('SMTP authentication failed for ' . $username);
            fclose($socket);
            return false;
        }
    }

    if (!mail_smtp_cmd($socket, 'MAIL FROM:<' . $fromEmail . '>', [250])) {
        fclose($socket);
        return false;
    }

    foreach ($recipients as $rcpt) {
        if (!mail_smtp_cmd($socket, 'RCPT TO:<' . $rcpt . '>', [250, 251])) {
            fclose($socket);
            return false;
        }
    }

    if (!mail_smtp_cmd($socket, 'DATA', [354])) {
        fclose($socket);
        return false;
    }

    $encodedSubject = mail_smtp_encode_header($subject);
    $fromHeader = mail_smtp_encode_header($fromName) . ' <' . $fromEmail . '>';
    $headers = [
        'Date: ' . date('r'),
        'From: ' . $fromHeader,
        'To: ' . implode(', ', $recipients),
        'Subject: ' . $encodedSubject,
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: BisaniBrothers/1.0',
    ];
    if ($replyTo) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    foreach ($extraHeaders as $name => $value) {
        if ($value !== '') {
            $headers[] = $name . ': ' . $value;
        }
    }

    $message = implode("\r\n", $headers) . "\r\n\r\n" . $bodyHtml;
    $message = preg_replace("/\r\n\./", "\r\n..", $message);
    fwrite($socket, $message . "\r\n.\r\n");

    $ok = mail_smtp_cmd($socket, '', [250]);
    mail_smtp_cmd($socket, 'QUIT', [221]);
    fclose($socket);

    return $ok;
}

function mail_send_php(string $to, string $subject, string $bodyHtml, string $fromEmail, string $fromName, ?string $replyTo = null, array $extraHeaders = []): bool
{
    $fromHeader = mail_smtp_encode_header($fromName) . ' <' . $fromEmail . '>';
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $fromHeader,
        'X-Mailer: BisaniBrothers/1.0',
    ];
    if ($replyTo) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    foreach ($extraHeaders as $name => $value) {
        if ($value !== '') {
            $headers[] = $name . ': ' . $value;
        }
    }
    return @mail($to, $subject, $bodyHtml, implode("\r\n", $headers));
}
