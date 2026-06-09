<?php
/**
 * Copy to mail-config.local.php on the live server (not committed to Git).
 *
 * Option A — Google Workspace App Password (works without IP allowlist):
 * 1. Google Admin → Users → marketing@bisanibrother.com → Security → App passwords
 * 2. Or myaccount.google.com/apppasswords (if enabled)
 * 3. Paste credentials below.
 *
 * Option B — Plesk environment variables (Hosting → PHP settings):
 *   BISANI_SMTP_USER=marketing@bisanibrother.com
 *   BISANI_SMTP_PASS=your-app-password
 *
 * Option C — Google SMTP relay (no password):
 *   Admin Console → Apps → Gmail → Routing → SMTP relay service
 *   → Allow only mail from your website server IP (find IP in Plesk).
 */
return [
    'smtp' => [
        'host'       => 'smtp.gmail.com',
        'port'       => 587,
        'encryption' => 'tls',
        'username'   => 'marketing@bisanibrother.com',
        'password'   => 'paste-google-app-password-here',
        'timeout'    => 20,
    ],
];
