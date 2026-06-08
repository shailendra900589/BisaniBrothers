<?php
/**
 * Pre-build locale replacement caches (run after deploy).
 * Usage: php scripts/warm-locale-cache.php
 */
require_once dirname(__DIR__) . '/includes/locale.php';

foreach (LOCALE_SUPPORTED as $code) {
    if ($code === LOCALE_DEFAULT) {
        continue;
    }
    $loaded = locale_load_strings($code);
    $count = count($loaded['_replacements'] ?? []);
    echo "Warmed {$code}: {$count} replacements\n";
}

echo "Done.\n";
