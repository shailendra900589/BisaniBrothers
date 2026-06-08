<?php
/**
 * Database connection — auto-detects local XAMPP vs live server.
 * Production: copy db.local.example.php → db.local.php (gitignored).
 */
require_once __DIR__ . '/includes/locale.php';
locale_init();

$dbHost = 'localhost';
$dbName = 'bisanibrothers_2026';
$dbCharset = 'utf8mb4';
$dbUser = 'root';
$dbPass = '';

$localConfig = __DIR__ . '/db.local.php';
if (is_file($localConfig)) {
    require $localConfig;
}

if (($envHost = getenv('BISANI_DB_HOST')) !== false && $envHost !== '') {
    $dbHost = $envHost;
}
if (($envName = getenv('BISANI_DB_NAME')) !== false && $envName !== '') {
    $dbName = $envName;
}
if (($envUser = getenv('BISANI_DB_USER')) !== false && $envUser !== '') {
    $dbUser = $envUser;
}
if (($envPass = getenv('BISANI_DB_PASS')) !== false) {
    $dbPass = $envPass;
}

$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = (bool) preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $httpHost)
    || (php_sapi_name() === 'cli' && !getenv('BISANI_LIVE'));

if ($isLocal && !is_file($localConfig)) {
    try {
        $probe = new PDO("mysql:host={$dbHost};charset={$dbCharset}", 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $probe->query('SELECT 1');
        $dbUser = 'root';
        $dbPass = '';
        unset($probe);
    } catch (PDOException $e) {
        // Keep configured credentials
    }
}

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    error_log('Bisani Brothers DB error: ' . $e->getMessage());

    if ($isLocal) {
        throw new PDOException(
            'Database connection failed. Start MySQL, import bisanibrothers_2026.sql, or create db.local.php from db.local.example.php.',
            (int) $e->getCode()
        );
    }

    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Setup required</title></head><body style="font-family:sans-serif;max-width:640px;margin:3rem auto;padding:0 1rem;">';
    echo '<h1>Database connection failed</h1>';
    echo '<p>Create <strong>db.local.php</strong> in the site root (copy from <code>db.local.example.php</code>) with your Plesk database credentials, then reload.</p>';
    echo '<p>If the file already exists, verify database name, username, and password in Plesk → Databases.</p>';
    echo '</body></html>';
    exit;
}
