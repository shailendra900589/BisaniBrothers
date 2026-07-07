<?php
/**
 * English-only locale helpers.
 * Page copy lives in lang/en.php and lang/page-content/en.php (loaded per page via page_te / page_t).
 */
define('LOCALE_META', require __DIR__ . '/../lang/locale-config.php');
define('LOCALE_SUPPORTED', ['en']);
define('LOCALE_DEFAULT', 'en');
define('LOCALE_COOKIE', 'bb_lang');
define('LOCALE_STATIC_PAGES', 'search|blog|about|contact|careers|faqs|case-studies|industries|privacy|terms|sales-growth|survey-market-research|staffing-solutions|btl-atl|lending-collection|ev-infrastructure|partner-with-us|growth-partner|why-work-with-us|jobs-index|orphan-index');

/** @var array<string, mixed> */
$GLOBALS['_locale_strings'] = [];
$GLOBALS['_locale_current'] = LOCALE_DEFAULT;

function locale_is_valid(?string $code): bool
{
    return $code === null || $code === '' || $code === LOCALE_DEFAULT;
}

function locale_non_default_codes(): array
{
    return [];
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

function locale_load_page_strings(string $code = LOCALE_DEFAULT): array
{
    $root = dirname(__DIR__) . '/lang/page-content';
    $en = is_file("{$root}/en.php") ? require "{$root}/en.php" : [];

    return is_array($en) ? $en : [];
}

function locale_load_strings(string $code = LOCALE_DEFAULT): array
{
    $enFile = dirname(__DIR__) . '/lang/en.php';
    $strings = is_file($enFile) ? require $enFile : [];
    if (!is_array($strings)) {
        $strings = [];
    }
    $strings['page'] = locale_load_page_strings();

    return $strings;
}

function locale_init(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $GLOBALS['_locale_current'] = LOCALE_DEFAULT;
    $GLOBALS['_locale_strings'] = locale_load_strings();

    if (headers_sent()) {
        return;
    }
    setcookie(LOCALE_COOKIE, '', ['expires' => time() - 3600, 'path' => '/', 'samesite' => 'Lax']);

    require_once __DIR__ . '/page-i18n.php';
}

function locale_current(): string
{
    locale_init();

    return LOCALE_DEFAULT;
}

function locale_meta(?string $code = null): array
{
    return LOCALE_META[LOCALE_DEFAULT];
}

function locale_label(string $code): string
{
    return 'English';
}

function locale_html_lang(?string $code = null): string
{
    return 'en-IN';
}

function locale_og_locale(?string $code = null): string
{
    return 'en_IN';
}

function locale_schema_language(?string $code = null): string
{
    return 'en-IN';
}

function locale_is_rtl(?string $code = null): bool
{
    return false;
}

function locale_request_path(): string
{
    $path = locale_normalize_path(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($script === 'blog-details.php' && !empty($_GET['slug'])) {
        $slug = trim(urldecode((string) $_GET['slug']));
        if ($slug !== '' && preg_match('/\s/', $slug)) {
            $slug = strtolower(trim(preg_replace('/[^a-z0-9-]+/', '-', $slug), '-'));
        }
        $path = '/' . rawurlencode($slug);
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
    $path = ltrim($path, '/');

    return $path === '' ? '/' : $path;
}

function seo_locale_path(string $relativePath = '', ?string $locale = null): string
{
    return locale_url(ltrim($relativePath, '/'));
}

function seo_locale_absolute(string $relativePath, string $base, ?string $locale = null): string
{
    $path = seo_locale_path($relativePath);

    return rtrim($base, '/') . ($path === '/' ? '/' : '/' . ltrim($path, '/'));
}

function locale_switch_url(string $targetLocale): string
{
    return locale_url(ltrim(locale_request_path(), '/'));
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
        'Blog'       => t('search.types.blog', 'Blog'),
        'Job'        => t('search.types.job', 'Job'),
        'Case Study' => t('search.types.case_study', 'Case Study'),
        'Industry'   => t('search.types.industry', 'Industry'),
        'Page'       => t('search.types.page', 'Page'),
        'FAQ'        => t('search.types.faq', 'FAQ'),
    ];

    return $map[$type] ?? $type;
}

function locale_hreflang_alternates(string $base_url): array
{
    $path = locale_request_path();
    $relative = $path === '/' ? '' : ltrim($path, '/');
    $urlPath = locale_url($relative);
    $full = rtrim($base_url, '/') . ($urlPath === '/' ? '/' : '/' . ltrim($urlPath, '/'));

    return [
        ['hreflang' => 'en-IN', 'href' => $full],
        ['hreflang' => 'x-default', 'href' => $full],
    ];
}

function locale_short_label(string $code): string
{
    return 'EN';
}

function locale_render_switcher(): string
{
    return '';
}

function locale_admin_options(?string $selected = 'en'): string
{
    return '<option value="en" selected>English</option>';
}

// Legacy no-ops (old i18n pipeline removed)
function locale_detect(): string { return LOCALE_DEFAULT; }
function locale_detect_from_path(?string $path = null): ?string { return null; }
function locale_ensure_replacements(): array { return []; }
function locale_build_replacements(string $code, array $merged, array $enBase): array { return []; }
