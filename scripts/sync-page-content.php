<?php
/**
 * Sync lang/page-content/{code}.php from en base + lang/packs/pages-content.php
 * Usage: php scripts/sync-page-content.php
 */
$root = dirname(__DIR__);
$en = require $root . '/lang/page-content/en.php';
$packs = require $root . '/lang/packs/pages-content.php';
$extraLocales = require $root . '/lang/packs/pages-content-locales.php';
if (is_array($extraLocales)) {
    foreach ($extraLocales as $code => $data) {
        $packs[$code] = sync_page_merge($packs[$code] ?? [], $data);
    }
}
$locales = require $root . '/lang/locale-config.php';

function sync_page_write(string $path, array $data): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $export = var_export($data, true);
    file_put_contents($path, "<?php\n/** Auto-synced page translations */\nreturn {$export};\n");
}

function sync_page_merge(array $base, array $overlay): array
{
    foreach ($overlay as $key => $value) {
        if (is_array($value) && isset($base[$key]) && is_array($base[$key]) && !array_is_list($value)) {
            $base[$key] = sync_page_merge($base[$key], $value);
        } else {
            $base[$key] = $value;
        }
    }
    return $base;
}

$written = [];
foreach (array_keys($locales) as $code) {
    if ($code === 'en') {
        continue;
    }
    $overlay = is_array($packs[$code] ?? null) ? $packs[$code] : [];
    $merged = sync_page_merge($en, $overlay);
    $path = "{$root}/lang/page-content/{$code}.php";
    sync_page_write($path, $merged);
    $written[] = "lang/page-content/{$code}.php";
    echo "Wrote lang/page-content/{$code}.php\n";
}

echo "\nSynced " . count($written) . " page-content locale files.\n";
