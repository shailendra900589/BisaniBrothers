<?php

/**
 * Mail settings — Google Workspace (bisanibrother.com).
 *
 * Instant enquiry alerts are sent FROM marketing@ and delivered TO contact@ + marketing@.
 *
 * Passwordless option (recommended on live server):
 * Google Admin → Apps → Google Workspace → Gmail → Routing → SMTP relay service
 * → Allow only mail from specified IP addresses → add your website server IP
 * → use smtp-relay.gmail.com (no username/password).
 *
 * Optional: create includes/mail-config.local.php to override SMTP (e.g. App Password for local testing).
 */
return [
    'from_email'  => 'marketing@bisanibrother.com',
    'from_name'   => 'Bisani Brothers Website',
    'notify_to'   => [
        'contact@bisanibrother.com',
        'marketing@bisanibrother.com',
    ],
    'smtp' => [
        'host'       => 'smtp-relay.gmail.com',
        'port'       => 587,
        'encryption' => 'tls',
        'username'   => '',
        'password'   => '',
        'timeout'    => 12,
    ],
];
