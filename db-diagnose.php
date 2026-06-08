<?php
/**
 * One-time DB diagnostic — delete this file after the site works.
 * Visit: /db-diagnose.php?key=bb-setup-2026
 */
header('Content-Type: text/plain; charset=utf-8');

if (($_GET['key'] ?? '') !== 'bb-setup-2026') {
    http_response_code(404);
    exit('Not found');
}

echo "PHP " . PHP_VERSION . "\n";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'yes' : 'NO') . "\n";
echo "Host header: " . ($_SERVER['HTTP_HOST'] ?? '') . "\n\n";

$configFile = __DIR__ . '/includes/db-live-config.php';
echo "db-live-config.php: " . (is_file($configFile) ? 'found' : 'MISSING') . "\n";
echo "db.local.php: " . (is_file(__DIR__ . '/db.local.php') ? 'found (ignored on live)' : 'not present') . "\n\n";

if (!is_file($configFile)) {
    exit("Pull latest Git code first.\n");
}

$cfg = require $configFile;
$host = $cfg['host'] ?? 'localhost';
$port = (int) ($cfg['port'] ?? 3306);
$name = $cfg['name'] ?? '';
$user = $cfg['user'] ?? '';
$pass = $cfg['pass'] ?? '';

$attempts = [
    ['host' => $host, 'port' => $port, 'name' => $name, 'user' => $user],
    ['host' => '127.0.0.1', 'port' => $port, 'name' => $name, 'user' => $user],
    ['host' => $host, 'port' => $port, 'name' => $name, 'user' => strtolower($user)],
];

$prefixes = ['BisaniBrothers_', 'bisanibrothers_'];
foreach ($prefixes as $prefix) {
    $attempts[] = ['host' => $host, 'port' => $port, 'name' => $prefix . $name, 'user' => $prefix . $user];
}

foreach ($attempts as $i => $a) {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $a['host'], $a['port'], $a['name']);
    echo "Attempt " . ($i + 1) . ": {$a['user']} @ {$a['host']}:{$a['port']} / {$a['name']}\n";
    try {
        $pdo = new PDO($dsn, $a['user'], $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $count = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ' . $pdo->quote($a['name']))->fetchColumn();
        echo "  OK — connected, tables in schema: {$count}\n\n";
        echo "Use in db-live-config.php:\n";
        echo "  host => '{$a['host']}'\n";
        echo "  port => {$a['port']}\n";
        echo "  name => '{$a['name']}'\n";
        echo "  user => '{$a['user']}'\n";
        exit;
    } catch (PDOException $e) {
        echo "  FAIL — " . $e->getMessage() . "\n\n";
    }
}

echo "No attempt worked. Fix user/database assignment in Plesk, then run this again.\n";
