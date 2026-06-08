<?php
/**
 * Database connection — auto-detects local XAMPP vs live server.
 * Live server: includes/db-live-config.php (bisanibrothers.com only).
 * Local override: db.local.php (gitignored, localhost only).
 */
require_once __DIR__ . '/includes/locale.php';
locale_init();

$dbHost = 'localhost';
$dbPort = 3306;
$dbName = 'bisanibrothers_2026';
$dbCharset = 'utf8mb4';
$dbUser = 'root';
$dbPass = '';

$httpHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
$isLocal = (bool) preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $httpHost)
    || (php_sapi_name() === 'cli' && !getenv('BISANI_LIVE'));
$isLiveSite = !$isLocal && str_contains($httpHost, 'bisanibrothers.com');

$localConfig = __DIR__ . '/db.local.php';

if ($isLiveSite && is_file(__DIR__ . '/includes/db-live-config.php')) {
    $live = require __DIR__ . '/includes/db-live-config.php';
    if (is_array($live)) {
        $dbHost = $live['host'] ?? $dbHost;
        $dbPort = (int) ($live['port'] ?? $dbPort);
        $dbName = $live['name'] ?? $dbName;
        $dbUser = $live['user'] ?? $dbUser;
        $dbPass = $live['pass'] ?? $dbPass;
    }
} elseif ($isLocal && is_file($localConfig)) {
    require $localConfig;
}

if (($envHost = getenv('BISANI_DB_HOST')) !== false && $envHost !== '') {
    $dbHost = $envHost;
}
if (($envPort = getenv('BISANI_DB_PORT')) !== false && $envPort !== '') {
    $dbPort = (int) $envPort;
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

function db_build_candidates(string $host, int $port, string $name, string $user, string $pass, string $charset): array
{
    $hosts = array_unique(array_filter([
        $host,
        $host === 'localhost' ? '127.0.0.1' : null,
        'localhost',
    ]));

    $names = [$name];
    $users = [$user, strtolower($user)];

    $prefixes = ['BisaniBrothers_', 'bisanibrothers_', 'BisaniBrothers_com_'];
    foreach ($prefixes as $prefix) {
        if (!str_starts_with($name, $prefix)) {
            $names[] = $prefix . $name;
        }
    }

    foreach ($prefixes as $prefix) {
        if (!str_starts_with($user, $prefix)) {
            $users[] = $prefix . $user;
            $users[] = $prefix . strtolower($user);
        }
    }

    $names = array_values(array_unique($names));
    $users = array_values(array_unique($users));

    $candidates = [];
    foreach ($hosts as $h) {
        foreach ($names as $n) {
            foreach ($users as $u) {
                $candidates[] = [
                    'host'    => $h,
                    'port'    => $port,
                    'name'    => $n,
                    'user'    => $u,
                    'pass'    => $pass,
                    'charset' => $charset,
                ];
            }
        }
    }

    return $candidates;
}

function db_connect(array $candidates, array $options): ?PDO
{
    $lastError = null;

    foreach ($candidates as $cfg) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'],
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
    $candidates = db_build_candidates($dbHost, $dbPort, $dbName, $dbUser, $dbPass, $dbCharset);
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

    $safeError = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database error</title></head><body style="font-family:sans-serif;max-width:720px;margin:3rem auto;padding:0 1rem;">';
    echo '<h1>Database connection failed</h1>';
    echo '<p><strong>Server says:</strong> <code>' . $safeError . '</code></p>';
    echo '<p>In <strong>Plesk → Databases → bisanibrothers_2026</strong>:</p>';
    echo '<ol>';
    echo '<li>Open the database → ensure user <code>BisaniBrothers_2026</code> is <strong>assigned</strong> to this database</li>';
    echo '<li>Reset password to match <code>includes/db-live-config.php</code>, then Git Pull</li>';
    echo '<li>Import <code>bisanibrothers_2026.sql</code> via phpMyAdmin if tables are missing</li>';
    echo '<li>If you created <code>db.local.php</code> in httpdocs with wrong details — <strong>delete it</strong></li>';
    echo '</ol>';
    echo '<p>Then: Plesk → Git → <strong>Pull Updates</strong> and reload this page.</p>';
    echo '</body></html>';
    exit;
}
