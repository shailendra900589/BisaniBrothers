<?php
/**
 * Database connection — auto-detects local XAMPP vs live server.
 * Local override: db.local.php (gitignored).
 * Live server: includes/db-live-config.php (auto on bisanibrothers.com).
 */
require_once __DIR__ . '/includes/locale.php';
locale_init();

$dbHost = 'localhost';
$dbName = 'bisanibrothers_2026';
$dbCharset = 'utf8mb4';
$dbUser = 'root';
$dbPass = '';

$httpHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
$isLocal = (bool) preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $httpHost)
    || (php_sapi_name() === 'cli' && !getenv('BISANI_LIVE'));
$isLiveSite = !$isLocal && str_contains($httpHost, 'bisanibrothers.com');

$localConfig = __DIR__ . '/db.local.php';
if (is_file($localConfig)) {
    require $localConfig;
} elseif ($isLiveSite && is_file(__DIR__ . '/includes/db-live-config.php')) {
    $live = require __DIR__ . '/includes/db-live-config.php';
    if (is_array($live)) {
        $dbHost = $live['host'] ?? $dbHost;
        $dbName = $live['name'] ?? $dbName;
        $dbUser = $live['user'] ?? $dbUser;
        $dbPass = $live['pass'] ?? $dbPass;
    }
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

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

function db_build_candidates(string $host, string $name, string $user, string $pass, string $charset): array
{
    $candidates = [[
        'host' => $host,
        'name' => $name,
        'user' => $user,
        'pass' => $pass,
        'charset' => $charset,
    ]];

    $prefixes = ['BisaniBrothers_', 'bisanibrothers_', 'BisaniBrothers_com_'];
    foreach ($prefixes as $prefix) {
        if (!str_starts_with($name, $prefix)) {
            $candidates[] = [
                'host'    => $host,
                'name'    => $prefix . $name,
                'user'    => str_starts_with($user, $prefix) ? $user : $prefix . $user,
                'pass'    => $pass,
                'charset' => $charset,
            ];
        }
    }

    if ($host === 'localhost') {
        $candidates[] = [
            'host'    => '127.0.0.1',
            'name'    => $name,
            'user'    => $user,
            'pass'    => $pass,
            'charset' => $charset,
        ];
    }

    $unique = [];
    foreach ($candidates as $candidate) {
        $key = implode('|', [$candidate['host'], $candidate['name'], $candidate['user']]);
        $unique[$key] = $candidate;
    }

    return array_values($unique);
}

function db_connect(array $candidates, array $options): ?PDO
{
    $lastError = null;

    foreach ($candidates as $cfg) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['name'],
            $cfg['charset']
        );

        try {
            return new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
        } catch (PDOException $e) {
            $lastError = $e;
        }
    }

    if ($lastError !== null) {
        throw $lastError;
    }

    return null;
}

try {
    $candidates = db_build_candidates($dbHost, $dbName, $dbUser, $dbPass, $dbCharset);
    $pdo = db_connect($candidates, $pdoOptions);
    if (!$pdo instanceof PDO) {
        throw new PDOException('Database connection failed.');
    }
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
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database error</title></head><body style="font-family:sans-serif;max-width:640px;margin:3rem auto;padding:0 1rem;">';
    echo '<h1>Database connection failed</h1>';
    echo '<p>Pull the latest code in Plesk Git, then verify in <strong>Plesk → Databases</strong> that:</p>';
    echo '<ul><li>Database <code>bisanibrothers_2026</code> exists</li>';
    echo '<li>User <code>BisaniBrothers_2026</code> is linked to that database</li>';
    echo '<li>SQL dump is imported</li></ul>';
    echo '<p>Or create <code>db.local.php</code> in httpdocs to override credentials.</p>';
    echo '</body></html>';
    exit;
}
