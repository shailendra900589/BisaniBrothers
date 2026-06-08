<?php
/**
 * Database connection — auto-detects local XAMPP vs live server.
 * Production credentials: copy db.local.example.php → db.local.php (gitignored).
 */
require_once __DIR__ . '/includes/locale.php';
locale_init();

$dbHost = 'localhost';
$dbName = 'bisanibrothers_2026';
$dbCharset = 'utf8mb4';
$dbUser = 'root';
$dbPass = '';

if (is_file(__DIR__ . '/db.local.php')) {
    require __DIR__ . '/db.local.php';
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

if ($isLocal && !is_file(__DIR__ . '/db.local.php')) {
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
    if ($isLocal) {
        throw new PDOException(
            'Database connection failed. Start MySQL, import bisanibrothers_2026.sql, or create db.local.php from db.local.example.php.',
            (int) $e->getCode()
        );
    }
    throw new PDOException('Database connection failed.', (int) $e->getCode());
}
