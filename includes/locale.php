<?php
/**
 * Multilingual system — English at root, other locales at /{code}/ prefix.
 */
define('LOCALE_META', require __DIR__ . '/../lang/locale-config.php');
define('LOCALE_SUPPORTED', array_keys(LOCALE_META));
define('LOCALE_DEFAULT', 'en');
define('LOCALE_COOKIE', 'bb_lang');
define('LOCALE_STATIC_PAGES', 'search|blog|about|contact|careers|faqs|case-studies|industries|privacy|terms|sales-growth|survey-market-research|staffing-solutions|btl-atl|lending-collection|ev-infrastructure|partner-with-us|growth-partner|why-work-with-us|jobs-index|orphan-index');

/** @var array<string, array<string, mixed>> */
$GLOBALS['_locale_strings'] = [];

function locale_is_valid(?string $code): bool
{
    return $code !== null && $code !== '' && isset(LOCALE_META[$code]);
}

function locale_non_default_codes(): array
{
    return array_values(array_filter(LOCALE_SUPPORTED, fn($c) => $c !== LOCALE_DEFAULT));
}

function locale_normalize_path(string $path): string
{
    $path = preg_replace('#/+#', '/', $path);
    if ($path === '') {
        $path = '/';
    }

    $project_root = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__));
    $doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');

    if ($doc_root !== '' && str_starts_with($project_root, $doc_root)) {
        $relative_root = substr($project_root, strlen($doc_root));
        if ($relative_root !== '' && $relative_root !== false && str_starts_with($path, $relative_root)) {
            $path = substr($path, strlen($relative_root));
        }
    }

    $folder = basename($project_root);
    if ($folder !== '' && preg_match('#^/' . preg_quote($folder, '#') . '(.*)$#i', $path, $m)) {
        $path = ($m[1] === '' || $m[1] === false) ? '/' : $m[1];
    }

    if ($path === '' || $path === false) {
        $path = '/';
    }
    if ($path !== '/' && str_ends_with($path, '/')) {
        $path = rtrim($path, '/');
    }
    return $path;
}

function locale_detect_from_path(?string $path = null): ?string
{
    $path = locale_normalize_path($path ?? (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
    foreach (locale_non_default_codes() as $code) {
        if (preg_match('#^/' . preg_quote($code, '#') . '(/|$)#', $path)) {
            return $code;
        }
    }
    return null;
}

function locale_detect(): string
{
    if (isset($_GET['lang'])) {
        $q = strtolower(trim((string) $_GET['lang']));
        if ($q === '' || $q === 'en') {
            return LOCALE_DEFAULT;
        }
        if (locale_is_valid($q)) {
            return $q;
        }
    }

    $fromPath = locale_detect_from_path();
    if ($fromPath !== null) {
        return $fromPath;
    }

    return LOCALE_DEFAULT;
}

function locale_collect_string_pairs(array $en, array $loc, array &$map, int $minLen = 2): void
{
    foreach ($en as $key => $enVal) {
        if (!array_key_exists($key, $loc)) {
            continue;
        }
        $locVal = $loc[$key];
        if (is_array($enVal) && is_array($locVal)) {
            locale_collect_string_pairs($enVal, $locVal, $map, $minLen);
            continue;
        }
        if (!is_string($enVal) || !is_string($locVal)) {
            continue;
        }
        if ($enVal === $locVal || strlen($enVal) < $minLen) {
            continue;
        }
        if (preg_match('#^https?://#i', $enVal) || str_contains($enVal, '@')) {
            continue;
        }
        $map[$enVal] = $locVal;
    }
}

function locale_merge_overlay_replacements(string $code, array &$map): void
{
    $overlayFile = dirname(__DIR__) . '/lang/overlays/' . $code . '.php';
    if (!is_file($overlayFile)) {
        return;
    }
    $overlay = require $overlayFile;
    if (!is_array($overlay)) {
        return;
    }
    foreach ($overlay as $section => $pairs) {
        if (!is_array($pairs)) {
            continue;
        }
        foreach ($pairs as $en => $tr) {
            if (is_string($en) && is_string($tr) && $en !== '' && $en !== $tr) {
                $map[$en] = $tr;
            }
        }
    }
}

function locale_build_replacements(string $code, array $merged, array $enBase): array
{
    if ($code === LOCALE_DEFAULT) {
        return [];
    }

    $map = [];
    locale_collect_string_pairs($enBase, $merged, $map);
    $pageEn = locale_load_page_strings(LOCALE_DEFAULT);
    $pageLoc = locale_load_page_strings($code);
    locale_collect_string_pairs($pageEn, $pageLoc, $map);
    locale_merge_overlay_replacements($code, $map);

    return $map;
}

function locale_load_page_strings(string $code): array
{
    $root = dirname(__DIR__) . '/lang/page-content';
    $en = is_file("{$root}/en.php") ? require "{$root}/en.php" : [];
    if (!is_array($en)) {
        $en = [];
    }
    if ($code === LOCALE_DEFAULT) {
        return $en;
    }
    $loc = is_file("{$root}/{$code}.php") ? require "{$root}/{$code}.php" : [];
    if (!is_array($loc)) {
        $loc = [];
    }
    return array_replace_recursive($en, $loc);
}

function locale_load_strings(string $code): array
{
    $enFile = dirname(__DIR__) . '/lang/en.php';
    $enBase = is_file($enFile) ? require $enFile : [];
    if (!is_array($enBase)) {
        $enBase = [];
    }

    if ($code === LOCALE_DEFAULT) {
        $enBase['page'] = locale_load_page_strings($code);
        return $enBase;
    }

    $file = dirname(__DIR__) . '/lang/' . $code . '.php';
    $local = is_file($file) ? require $file : [];
    if (!is_array($local)) {
        $local = [];
    }

    $merged = array_replace_recursive($enBase, $local);
    $merged['page'] = locale_load_page_strings($code);
    $merged['_replacements'] = locale_build_replacements($code, $merged, $enBase);

    return $merged;
}

function locale_init(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $current = locale_detect();
    $GLOBALS['_locale_current'] = $current;
    $loaded = locale_load_strings($current);
    $GLOBALS['_locale_replacements'] = $loaded['_replacements'] ?? [];
    unset($loaded['_replacements']);
    $GLOBALS['_locale_strings'] = $loaded;
    locale_sync_cookie($current);

    require_once __DIR__ . '/page-i18n.php';

    if (locale_current() !== LOCALE_DEFAULT && !defined('LOCALE_OUTPUT_BUFFER')) {
        define('LOCALE_OUTPUT_BUFFER', true);
        require_once __DIR__ . '/i18n-output.php';
        locale_start_output_buffer();
    }
}

function locale_sync_cookie(string $code): void
{
    if (headers_sent()) {
        return;
    }
    if ($code === LOCALE_DEFAULT) {
        setcookie(LOCALE_COOKIE, '', ['expires' => time() - 3600, 'path' => '/', 'samesite' => 'Lax']);
        return;
    }
    setcookie(LOCALE_COOKIE, $code, [
        'expires'  => time() + 365 * 86400,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

function locale_current(): string
{
    locale_init();
    return $GLOBALS['_locale_current'] ?? LOCALE_DEFAULT;
}

function locale_meta(?string $code = null): array
{
    $code = $code ?? locale_current();
    return LOCALE_META[$code] ?? LOCALE_META[LOCALE_DEFAULT];
}

function locale_label(string $code): string
{
    return LOCALE_META[$code]['native'] ?? LOCALE_META[$code]['label'] ?? strtoupper($code);
}

function locale_html_lang(?string $code = null): string
{
    return locale_meta($code)['html'] ?? 'en-IN';
}

function locale_og_locale(?string $code = null): string
{
    return locale_meta($code)['og'] ?? 'en_IN';
}

function locale_schema_language(?string $code = null): string
{
    return locale_html_lang($code);
}

function locale_is_rtl(?string $code = null): bool
{
    return !empty(locale_meta($code)['rtl']);
}

function locale_request_path(): string
{
    $path = locale_normalize_path(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

    foreach (locale_non_default_codes() as $code) {
        if (preg_match('#^/' . preg_quote($code, '#') . '(.*)$#', $path, $m)) {
            $path = $m[1] === '' ? '/' : $m[1];
            break;
        }
    }

    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($script === 'blog-details.php' && !empty($_GET['slug'])) {
        $path = '/' . rawurlencode($_GET['slug']);
    } elseif ($script === 'job-details.php' && !empty($_GET['slug'])) {
        $path = '/jobs/' . rawurlencode($_GET['slug']);
    } elseif ($script === 'case-study-details.php' && !empty($_GET['slug'])) {
        $path = '/case-studies/' . rawurlencode($_GET['slug']);
    } elseif ($script === 'industry.php' && !empty($_GET['slug'])) {
        $path = '/industries/' . rawurlencode($_GET['slug']);
    } elseif ($script !== 'index.php' && $script !== '' && $path === '/' . $script) {
        $path = '/' . basename($script, '.php');
    }

    return $path === '' ? '/' : $path;
}

function locale_url(string $path = '', ?string $locale = null): string
{
    $locale = $locale ?? locale_current();
    $path = ltrim($path, '/');

    if ($locale === LOCALE_DEFAULT) {
        return $path === '' ? '/' : $path;
    }

    return $path === '' ? $locale . '/' : $locale . '/' . $path;
}

function seo_locale_path(string $relativePath = '', ?string $locale = null): string
{
    return locale_url(ltrim($relativePath, '/'), $locale);
}

function seo_locale_absolute(string $relativePath, string $base, ?string $locale = null): string
{
    $path = seo_locale_path($relativePath, $locale);
    return rtrim($base, '/') . ($path === '/' ? '/' : '/' . ltrim($path, '/'));
}

function locale_switch_url(string $targetLocale): string
{
    if (!locale_is_valid($targetLocale)) {
        $targetLocale = LOCALE_DEFAULT;
    }

    $path = locale_request_path();
    $relative = $path === '/' ? '' : ltrim($path, '/');
    $url = locale_url($relative, $targetLocale);

    if ($targetLocale === LOCALE_DEFAULT) {
        $url .= (str_contains($url, '?') ? '&' : '?') . 'lang=en';
    }

    return $url;
}

function t(string $key, ?string $fallback = null): string
{
    locale_init();
    $strings = $GLOBALS['_locale_strings'] ?? [];
    $parts = explode('.', $key);
    $val = $strings;
    foreach ($parts as $part) {
        if (!is_array($val) || !array_key_exists($part, $val)) {
            return $fallback ?? $key;
        }
        $val = $val[$part];
    }
    return is_string($val) ? $val : ($fallback ?? $key);
}

function locale_page_meta(?string $script = null): ?array
{
    $script = $script ?? basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
    locale_init();
    $pages = $GLOBALS['_locale_strings']['pages'] ?? [];
    if (!isset($pages[$script]) || !is_array($pages[$script])) {
        return null;
    }
    $entry = $pages[$script];
    if (empty($entry['title']) && empty($entry['desc'])) {
        return null;
    }
    return [
        'title' => $entry['title'] ?? null,
        'desc'  => $entry['desc'] ?? null,
    ];
}

function locale_type_label(string $type): string
{
    $map = [
        'Blog'        => t('search.types.blog', 'Blog'),
        'Job'         => t('search.types.job', 'Job'),
        'Case Study'  => t('search.types.case_study', 'Case Study'),
        'Industry'    => t('search.types.industry', 'Industry'),
        'Page'        => t('search.types.page', 'Page'),
        'FAQ'         => t('search.types.faq', 'FAQ'),
    ];
    return $map[$type] ?? $type;
}

function locale_hreflang_alternates(string $base_url): array
{
    $path = locale_request_path();
    $relative = $path === '/' ? '' : ltrim($path, '/');
    $links = [];
    foreach (LOCALE_SUPPORTED as $loc) {
        $urlPath = locale_url($relative, $loc);
        $full = rtrim($base_url, '/') . ($urlPath === '/' ? '/' : '/' . ltrim($urlPath, '/'));
        if ($loc === LOCALE_DEFAULT && str_contains($full, '?')) {
            $full = strtok($full, '?');
        }
        $links[] = [
            'hreflang' => locale_meta($loc)['hreflang'],
            'href'     => $full,
        ];
    }
    $defaultPath = locale_url($relative, LOCALE_DEFAULT);
    $defaultFull = rtrim($base_url, '/') . ($defaultPath === '/' ? '/' : '/' . ltrim($defaultPath, '/'));
    $links[] = ['hreflang' => 'x-default', 'href' => strtok($defaultFull, '?') ?: $defaultFull];
    return $links;
}

function locale_short_label(string $code): string
{
    return strtoupper($code);
}

function locale_render_switcher(): string
{
    $current = locale_current();
    ob_start();
    ?>
    <div class="relative group locale-switcher shrink-0">
        <button type="button" class="flex items-center gap-1 text-[10px] font-bold text-[#173978] border border-gray-200 rounded-full px-1.5 py-0.5 hover:border-[#2fcaf0] hover:bg-cyan-50 transition-all focus:outline-none leading-none" aria-haspopup="true" aria-label="<?php echo htmlspecialchars(t('nav.language', 'Language')); ?>">
            <i class="fa-solid fa-globe text-[#2fcaf0] text-[9px]"></i>
            <span><?php echo htmlspecialchars(locale_short_label($current)); ?></span>
            <i class="fa-solid fa-chevron-down text-[8px] text-gray-400 group-hover:rotate-180 transition-transform"></i>
        </button>
        <div class="absolute right-0 top-full pt-1.5 hidden group-hover:block group-focus-within:block z-[60] min-w-[8.5rem]">
            <div class="bg-white shadow-lg rounded-md border border-gray-100 py-0.5 overflow-hidden max-h-52 overflow-y-auto">
                <?php foreach (LOCALE_SUPPORTED as $code): ?>
                <a href="<?php echo htmlspecialchars(locale_switch_url($code)); ?>" class="block px-2.5 py-1.5 text-[11px] font-semibold whitespace-nowrap <?php echo $code === $current ? 'text-[#173978] bg-cyan-50' : 'text-gray-600 hover:bg-gray-50 hover:text-[#173978]'; ?> transition-colors" hreflang="<?php echo htmlspecialchars(locale_meta($code)['hreflang']); ?>" lang="<?php echo $code; ?>">
                    <?php echo htmlspecialchars(locale_label($code)); ?>
                    <?php if ($code === $current): ?><i class="fa-solid fa-check text-[#2fcaf0] ml-1 text-[9px]"></i><?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function locale_admin_options(?string $selected = 'en'): string
{
    $html = '';
    foreach (LOCALE_SUPPORTED as $code) {
        $sel = ($selected === $code) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($code) . '"' . $sel . '>' . htmlspecialchars(locale_label($code)) . '</option>';
    }
    return $html;
}
