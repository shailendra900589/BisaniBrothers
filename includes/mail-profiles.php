<?php

/**
 * Ordered SMTP profiles — first success wins (Google relay, Plesk MTA, authenticated Gmail).
 *
 * @return array<int, array<string, mixed>>
 */
function mail_smtp_profiles(array $cfg): array
{
    $base = $cfg['smtp'] ?? [];
    $relayHost = trim((string) ($base['host'] ?? 'smtp-relay.gmail.com'));
    $user = trim((string) ($base['username'] ?? ''));
    $pass = (string) ($base['password'] ?? '');
    $profiles = [];

    if ($user !== '' && $pass !== '') {
        $profiles[] = [
            'label'       => 'gmail_auth_tls',
            'host'        => 'smtp.gmail.com',
            'port'        => 587,
            'encryption'  => 'tls',
            'username'    => $user,
            'password'    => $pass,
            'timeout'     => 20,
            'verify_ssl'  => true,
        ];
        $profiles[] = [
            'label'       => 'gmail_auth_ssl',
            'host'        => 'smtp.gmail.com',
            'port'        => 465,
            'encryption'  => 'ssl',
            'username'    => $user,
            'password'    => $pass,
            'timeout'     => 20,
            'verify_ssl'  => true,
        ];
    }

    $profiles[] = [
        'label'      => 'google_relay_tls',
        'host'       => $relayHost,
        'port'       => (int) ($base['port'] ?? 587),
        'encryption' => 'tls',
        'username'   => '',
        'password'   => '',
        'timeout'    => (int) ($base['timeout'] ?? 15),
        'verify_ssl' => true,
    ];
    $profiles[] = [
        'label'      => 'google_relay_25',
        'host'       => $relayHost,
        'port'       => 25,
        'encryption' => 'none',
        'username'   => '',
        'password'   => '',
        'timeout'    => 12,
        'verify_ssl' => true,
    ];

    // Plesk / Windows IIS — local MTA (no outbound 587 required)
    foreach (['127.0.0.1', 'localhost'] as $host) {
        $profiles[] = [
            'label'      => 'plesk_mta_' . str_replace('.', '_', $host),
            'host'       => $host,
            'port'       => 25,
            'encryption' => 'none',
            'username'   => '',
            'password'   => '',
            'timeout'    => 10,
            'verify_ssl' => true,
        ];
    }

    // Domain mail host on same Plesk server
    $profiles[] = [
        'label'      => 'domain_mail_tls',
        'host'       => 'mail.bisanibrother.com',
        'port'       => 587,
        'encryption' => 'tls',
        'username'   => $user,
        'password'   => $pass,
        'timeout'    => 15,
        'verify_ssl' => true,
    ];
    $profiles[] = [
        'label'      => 'domain_mail_25',
        'host'       => 'mail.bisanibrother.com',
        'port'       => 25,
        'encryption' => 'none',
        'username'   => '',
        'password'   => '',
        'timeout'    => 12,
        'verify_ssl' => true,
    ];

    // Last resort for TLS handshake issues on older Windows PHP/OpenSSL
    $profiles[] = [
        'label'      => 'google_relay_tls_relaxed',
        'host'       => $relayHost,
        'port'       => 587,
        'encryption' => 'tls',
        'username'   => '',
        'password'   => '',
        'timeout'    => 15,
        'verify_ssl' => false,
    ];

    return $profiles;
}
