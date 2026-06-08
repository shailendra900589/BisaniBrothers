<?php
/**
 * Auto-translate lang/page-content/en.php into all non-English locales.
 * Preserves existing manual translations (hi pack + pages-content-locales).
 *
 * Usage: php scripts/translate-page-content.php [--locale=bn] [--force]
 */
$root = dirname(__DIR__);
$en = require $root . '/lang/page-content/en.php';
$locales = require $root . '/lang/locale-config.php';
$hiPack = (require $root . '/lang/packs/pages-content.php')['hi'] ?? [];
$existingLocales = require $root . '/lang/packs/pages-content-locales.php';
$cacheDir = $root . '/lang/cache/page-translations';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

$onlyLocale = null;
$force = in_array('--force', $argv ?? [], true);
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--locale=')) {
        $onlyLocale = substr($arg, 9);
    }
}

function tp_google_translate(string $text, string $targetLang): string
{
    $text = trim($text);
    if ($text === '' || strlen($text) < 2) {
        return $text;
    }
    if (preg_match('#^https?://#i', $text)) {
        return $text;
    }

    $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl='
        . rawurlencode($targetLang)
        . '&dt=t&q=' . rawurlencode($text);

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 30,
            'header' => "User-Agent: Mozilla/5.0\r\n",
        ],
    ]);

    $json = @file_get_contents($url, false, $ctx);
    if ($json === false) {
        fwrite(STDERR, "  [warn] translate failed for: " . substr($text, 0, 60) . "...\n");
        return $text;
    }

    $data = json_decode($json, true);
    if (!isset($data[0]) || !is_array($data[0])) {
        return $text;
    }

    $parts = [];
    foreach ($data[0] as $chunk) {
        if (isset($chunk[0]) && is_string($chunk[0])) {
            $parts[] = $chunk[0];
        }
    }

    return $parts !== [] ? implode('', $parts) : $text;
}

function tp_load_cache(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function tp_save_cache(string $path, array $data): void
{
    file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function tp_merge_deep(array $base, array $overlay): array
{
    foreach ($overlay as $key => $value) {
        if (is_array($value) && isset($base[$key]) && is_array($base[$key]) && !array_is_list($value)) {
            $base[$key] = tp_merge_deep($base[$key], $value);
        } else {
            $base[$key] = $value;
        }
    }
    return $base;
}

function tp_translate_tree(array $enTree, array $existing, string $lang, array &$cache, bool $force): array
{
    $out = [];
    foreach ($enTree as $key => $enVal) {
        if (is_array($enVal)) {
            $out[$key] = tp_translate_tree(
                $enVal,
                is_array($existing[$key] ?? null) ? $existing[$key] : [],
                $lang,
                $cache,
                $force
            );
            continue;
        }

        if (!is_string($enVal)) {
            continue;
        }

        $cur = $existing[$key] ?? null;
        if (!$force && is_string($cur) && $cur !== '' && $cur !== $enVal) {
            $out[$key] = $cur;
            continue;
        }

        $cacheKey = md5($lang . '|' . $enVal);
        if (!$force && isset($cache[$cacheKey])) {
            $out[$key] = $cache[$cacheKey];
            continue;
        }

        $translated = tp_google_translate($enVal, $lang);
        $cache[$cacheKey] = $translated;
        $out[$key] = $translated;
        usleep(120000);
    }

    return $out;
}

function tp_strip_english(array $enTree, array $locTree): array
{
    $out = [];
    foreach ($locTree as $key => $val) {
        if (!array_key_exists($key, $enTree)) {
            $out[$key] = $val;
            continue;
        }
        $enVal = $enTree[$key];
        if (is_array($val) && is_array($enVal)) {
            $nested = tp_strip_english($enVal, $val);
            if ($nested !== []) {
                $out[$key] = $nested;
            }
            continue;
        }
        if (is_string($val) && is_string($enVal) && $val !== $enVal) {
            $out[$key] = $val;
        }
    }
    return $out;
}

$output = [];
$codes = array_values(array_filter(array_keys($locales), fn($k) => $k !== 'en'));

foreach ($codes as $code) {
    if ($code === 'en') {
        continue;
    }
    if ($onlyLocale !== null && $onlyLocale !== $code) {
        continue;
    }

    echo "Translating {$code}...\n";

    $existing = $existingLocales[$code] ?? [];
    if ($code === 'hi') {
        $existing = tp_merge_deep($existing, $hiPack);
    }

    $cachePath = "{$cacheDir}/{$code}.json";
    $cache = tp_load_cache($cachePath);

    $full = tp_translate_tree($en, $existing, $code, $cache, $force);
    tp_save_cache($cachePath, $cache);

    $output[$code] = tp_strip_english($en, $full);
    echo "  Done {$code} (" . count($cache) . " cached strings)\n";
}

$export = var_export($output, true);
$outFile = $root . '/lang/packs/pages-content-locales.php';
file_put_contents(
    $outFile,
    "<?php\n/** Auto-translated page content — run scripts/sync-page-content.php after edits */\nreturn {$export};\n"
);

echo "\nWrote {$outFile}\n";
echo "Run: php scripts/sync-page-content.php\n";
