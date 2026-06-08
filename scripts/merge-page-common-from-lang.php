<?php
/**
 * Merge _common UI strings from lang/{code}.php into lang/page-content/{code}.php
 */
$root = dirname(__DIR__);
$en = require $root . '/lang/page-content/en.php';
$locales = require $root . '/lang/locale-config.php';

$map = [
    'know_more'     => ['common.know_more', 'common.learn_more'],
    'success_title' => ['index.success_title'],
    'success_desc'  => ['index.success_desc'],
    'form_submit'   => ['footer.subscribe'],
];

function pick(array $lang, array $paths, string $fallback): string
{
    foreach ($paths as $path) {
        $val = $lang;
        foreach (explode('.', $path) as $p) {
            if (!is_array($val) || !isset($val[$p])) {
                $val = null;
                break;
            }
            $val = $val[$p];
        }
        if (is_string($val) && $val !== '') {
            return $val;
        }
    }
    return $fallback;
}

foreach (array_keys($locales) as $code) {
    if ($code === 'en') {
        continue;
    }
    $pageFile = "{$root}/lang/page-content/{$code}.php";
    $page = is_file($pageFile) ? require $pageFile : $en;
    $langFile = "{$root}/lang/{$code}.php";
    if (!is_file($langFile)) {
        continue;
    }
    $lang = require $langFile;
    foreach ($map as $key => $paths) {
        $page['_common'][$key] = pick($lang, $paths, $en['_common'][$key]);
    }
    $overlayFile = "{$root}/lang/overlays/{$code}.php";
    if (is_file($overlayFile)) {
        $overlay = require $overlayFile;
        $global = $overlay['global'] ?? [];
        $reverse = array_flip($en['_common']);
        foreach ($global as $english => $translated) {
            if (isset($reverse[$english])) {
                $page['_common'][$reverse[$english]] = $translated;
            }
        }
        foreach ($overlay as $slug => $pairs) {
            if ($slug === 'global' || !is_array($pairs)) {
                continue;
            }
            if (!isset($page[$slug])) {
                $page[$slug] = [];
            }
            $enPage = $en[$slug] ?? [];
            foreach ($pairs as $english => $translated) {
                foreach ($enPage as $k => $enVal) {
                    if ($enVal === $english) {
                        $page[$slug][$k] = $translated;
                    }
                }
            }
        }
    }
    $export = var_export($page, true);
    file_put_contents($pageFile, "<?php\n/** Auto-generated page translations */\nreturn {$export};\n");
    echo "Updated {$code}\n";
}

echo "Done.\n";
