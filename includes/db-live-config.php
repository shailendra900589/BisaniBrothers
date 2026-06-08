<?php
/**
 * Live server database credentials (Plesk).
 * Loaded only when HTTP_HOST is bisanibrothers.com — not used on localhost.
 * Override anytime with db.local.php (gitignored) or BISANI_DB_* env vars.
 */
return [
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'bisanibrothers_2026',
    'user' => 'BisaniBrothers_2026',
    'pass' => 'BBPLNTPL@4321',
];
