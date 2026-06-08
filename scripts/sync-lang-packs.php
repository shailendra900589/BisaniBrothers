<?php
/**
 * Sync language packs — merge pack data into lang files.
 * Loads en.php as base, merges lang/packs/{code}.php for each non-en locale.
 *
 * Usage: php scripts/sync-lang-packs.php
 */
$root = dirname(__DIR__);
$en = require $root . '/lang/en.php';
$locales = require $root . '/lang/locale-config.php';
$packDir = $root . '/lang/packs';
$footerExtraFile = $packDir . '/footer-extra.php';
$footerExtra = is_file($footerExtraFile) ? require $footerExtraFile : [];
if (!is_array($footerExtra)) {
    $footerExtra = [];
}
$indexContentFile = $packDir . '/index-content.php';
$indexContent = is_file($indexContentFile) ? require $indexContentFile : [];
if (!is_array($indexContent)) {
    $indexContent = [];
}

function sync_deep_merge(array $base, array $overlay): array
{
    foreach ($overlay as $key => $value) {
        if (is_array($value) && isset($base[$key]) && is_array($base[$key]) && !array_is_list($value) && !array_is_list($base[$key])) {
            $base[$key] = sync_deep_merge($base[$key], $value);
        } else {
            $base[$key] = $value;
        }
    }
    return $base;
}

function sync_write_php(string $path, array $data, string $comment = ''): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $export = var_export($data, true);
    $content = "<?php\n";
    if ($comment !== '') {
        $content .= "/**\n * " . str_replace("\n", "\n * ", trim($comment)) . "\n */\n";
    }
    $content .= "return {$export};\n";
    file_put_contents($path, $content);
}

function sync_build_lang(array $en, array $pack): array
{
    $sections = ['nav', 'services', 'footer', 'search', 'common', 'index', 'pages', 'testimonials'];
    $out = [];
    foreach ($sections as $section) {
        $out[$section] = isset($pack[$section])
            ? sync_deep_merge($en[$section] ?? [], $pack[$section])
            : ($en[$section] ?? []);
    }

    $out['search_pages'] = [];
    $titleMap = $pack['search_page_titles'] ?? [];
    $keywordMap = $pack['search_page_keywords'] ?? [];
    foreach ($en['search_pages'] as $page) {
        $url = $page['url'];
        $out['search_pages'][] = [
            'title'    => $titleMap[$url] ?? $page['title'],
            'url'      => $page['url'],
            'keywords' => $keywordMap[$url] ?? $page['keywords'],
        ];
    }

    return $out;
}

$written = [];
$skipped = [];

foreach (array_keys($locales) as $code) {
    if ($code === 'en') {
        continue;
    }

    $packFile = $packDir . '/' . $code . '.php';
    if (!is_file($packFile)) {
        $skipped[] = $code;
        echo "SKIP {$code}: missing {$packFile}\n";
        continue;
    }

    $pack = require $packFile;
    if (!is_array($pack)) {
        $skipped[] = $code;
        echo "SKIP {$code}: pack must return array\n";
        continue;
    }

    if (isset($indexContent[$code]) && is_array($indexContent[$code])) {
        $extra = $indexContent[$code];
        if (!empty($extra['index']) && is_array($extra['index'])) {
            $pack['index'] = sync_deep_merge($pack['index'] ?? [], $extra['index']);
        }
        if (!empty($extra['testimonials']) && is_array($extra['testimonials'])) {
            $pack['testimonials'] = $extra['testimonials'];
        }
    }

    if (!empty($footerExtra[$code]) && is_array($footerExtra[$code])) {
        $pack['footer'] = sync_deep_merge($pack['footer'] ?? [], $footerExtra[$code]);
    }

    $langPath = "{$root}/lang/{$code}.php";
    sync_write_php($langPath, sync_build_lang($en, $pack));
    $written[] = "lang/{$code}.php";
    echo "Wrote lang/{$code}.php\n";

    if (!empty($pack['overlay']) && is_array($pack['overlay'])) {
        $overlayPath = "{$root}/lang/overlays/{$code}.php";
        sync_write_php(
            $overlayPath,
            $pack['overlay'],
            "Full-page string replacements (English phrase => translated) applied via output buffer.\n * Keys: global (all pages), or page slug (about, careers, …)."
        );
        $written[] = "lang/overlays/{$code}.php";
        echo "Wrote lang/overlays/{$code}.php\n";
    }

    if (!empty($pack['industries']) && is_array($pack['industries'])) {
        $label = $locales[$code]['label'] ?? $code;
        $indPath = "{$root}/lang/{$code}/industries.php";
        sync_write_php(
            $indPath,
            $pack['industries'],
            "{$label} industry page content — merged by industry_get() when locale is {$code}."
        );
        $written[] = "lang/{$code}/industries.php";
        echo "Wrote lang/{$code}/industries.php\n";
    }
}

$syncedLocales = [];
foreach (array_keys($locales) as $code) {
    if ($code !== 'en' && !in_array($code, $skipped, true)) {
        $syncedLocales[] = $code;
    }
}
echo "\n=== Sync complete ===\n";
echo 'Locales synced: ' . implode(', ', $syncedLocales) . "\n";
echo 'Files written: ' . count($written) . "\n";
foreach ($written as $f) {
    echo "  - {$f}\n";
}
if ($skipped !== []) {
    echo 'Skipped: ' . implode(', ', $skipped) . "\n";
}
