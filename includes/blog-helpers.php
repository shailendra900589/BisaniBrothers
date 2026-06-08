<?php

function blog_get_safe_path(?string $dbPath): string
{
    $fallback = 'assets/bg/Blog_page.webp';
    if (empty($dbPath)) {
        return $fallback;
    }
    $path = ltrim(str_replace(['../', './'], '', $dbPath), '/');
    $full = dirname(__DIR__) . '/' . $path;
    if (!is_file($full)) {
        return $fallback;
    }

    return $path;
}

function blog_is_orphan(array $post): bool
{
    return !empty($post['is_orphan']);
}

/** Published posts visible on blog listing, filters, related links, etc. */
function blog_sql_public_only(string $alias = ''): string
{
    $p = $alias !== '' ? $alias . '.' : '';
    return "{$p}is_published = 1 AND ({$p}is_orphan = 0 OR {$p}is_orphan IS NULL)";
}

/** All published posts including orphan (sitemap, RSS, IndexNow). */
function blog_sql_indexable(string $alias = ''): string
{
    $p = $alias !== '' ? $alias . '.' : '';
    return "{$p}is_published = 1";
}

function blog_has_column(PDO $pdo, string $column): bool
{
    static $cache = [];
    if (array_key_exists($column, $cache)) {
        return $cache[$column];
    }
    try {
        $cache[$column] = (bool) $pdo->query('SHOW COLUMNS FROM blogs LIKE ' . $pdo->quote($column))->fetch();
    } catch (PDOException $e) {
        $cache[$column] = false;
    }

    return $cache[$column];
}

function blog_has_locale_column(PDO $pdo): bool
{
    return blog_has_column($pdo, 'locale');
}

/** Build a safe SELECT list for the live DB (locale/updated_at may be missing). */
function blog_sql_columns(PDO $pdo, array $columns): string
{
    $cols = $columns;
    if (blog_has_locale_column($pdo) && !in_array('locale', $cols, true)) {
        $cols[] = 'locale';
    }
    if (blog_has_column($pdo, 'updated_at') && !in_array('updated_at', $cols, true)) {
        $cols[] = 'updated_at';
    }

    return implode(', ', $cols);
}

function blog_normalize_slug(string $input): string
{
    $slug = strtolower(trim($input));
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    if (strlen($slug) > 120) {
        $slug = rtrim(substr($slug, 0, 120), '-');
    }

    return $slug;
}

function blog_make_slug(string $title, ?int $id = null): string
{
    $slug = blog_normalize_slug($title);

    return $slug !== '' ? $slug : 'post-' . ($id ?? time());
}

/** @return string[] */
function blog_reserved_slugs(): array
{
    require_once __DIR__ . '/seo-config.php';
    require_once __DIR__ . '/locale.php';

    static $reserved = null;
    if ($reserved !== null) {
        return $reserved;
    }

    $paths = [];
    foreach (array_keys(SEO_STATIC_PAGES) as $path) {
        $clean = ltrim((string) $path, '/');
        if ($clean !== '') {
            $paths[] = $clean;
        }
    }

    $paths = array_merge($paths, LOCALE_SUPPORTED, [
        'jobs',
        'job-details',
        'blog-details',
        'case-study-details',
        'industry',
        'admin',
        'includes',
        'uploads',
        'scripts',
        'storage',
        'assets',
        'sitemap',
        'rss',
        'feed',
        'llms',
        'opensearch',
        'login',
        '404',
        'sitemap.xml',
        'rss.xml',
        'feed.xml',
        'llms.txt',
        'opensearch.xml',
        'bb-analytics.js',
        'bb-beacon.gif',
        'seo-reindex',
    ]);

    $reserved = array_values(array_unique(array_filter($paths)));

    return $reserved;
}

function blog_validate_slug(string $slug): ?string
{
    if ($slug === '') {
        return 'URL slug is required.';
    }
    if (strlen($slug) > 120) {
        return 'URL slug must be 120 characters or less.';
    }
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
        return 'URL slug can only contain lowercase letters, numbers, and hyphens.';
    }
    if (in_array($slug, blog_reserved_slugs(), true)) {
        return 'This URL slug is reserved for a site page. Choose a different slug.';
    }

    return null;
}

function blog_ensure_unique_slug(PDO $pdo, string $slug, ?int $excludeId = null, ?string $locale = null): string
{
    require_once __DIR__ . '/locale.php';
    $locale = $locale ?? LOCALE_DEFAULT;
    $hasLocale = blog_has_locale_column($pdo);
    $candidate = $slug;
    $n = 2;

    while (true) {
        if ($excludeId) {
            if ($hasLocale) {
                $stmt = $pdo->prepare('SELECT id FROM blogs WHERE slug = ? AND locale = ? AND id != ? LIMIT 1');
                $stmt->execute([$candidate, $locale, $excludeId]);
            } else {
                $stmt = $pdo->prepare('SELECT id FROM blogs WHERE slug = ? AND id != ? LIMIT 1');
                $stmt->execute([$candidate, $excludeId]);
            }
        } elseif ($hasLocale) {
            $stmt = $pdo->prepare('SELECT id FROM blogs WHERE slug = ? AND locale = ? LIMIT 1');
            $stmt->execute([$candidate, $locale]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM blogs WHERE slug = ? LIMIT 1');
            $stmt->execute([$candidate]);
        }

        if (!$stmt->fetch()) {
            return $candidate;
        }

        $candidate = $slug . '-' . $n;
        $n++;
    }
}

function blog_sql_locale(string $alias = '', ?string $locale = null): string
{
    require_once __DIR__ . '/locale.php';
    $locale = $locale ?? locale_current();
    $p = $alias !== '' ? $alias . '.' : '';
    return "{$p}locale = '" . str_replace("'", "''", $locale) . "'";
}

/** Include English posts when viewing a non-English locale (for auto-translation). */
function blog_sql_locale_with_fallback(string $alias = '', ?string $locale = null): string
{
    require_once __DIR__ . '/locale.php';
    $locale = $locale ?? locale_current();
    $p = $alias !== '' ? $alias . '.' : '';
    if ($locale === LOCALE_DEFAULT) {
        return "{$p}locale = 'en'";
    }
    $esc = str_replace("'", "''", $locale);
    return "{$p}locale IN ('{$esc}', 'en')";
}

/**
 * Prefer native locale row per slug; fall back to English for auto-translation.
 *
 * @param array<int, array<string, mixed>> $rows
 * @return array<int, array<string, mixed>>
 */
function blog_pick_locale_rows(array $rows, ?string $locale = null): array
{
    require_once __DIR__ . '/locale.php';
    $locale = $locale ?? locale_current();
    $bySlug = [];
    foreach ($rows as $row) {
        $slug = (string) ($row['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        $rowLocale = $row['locale'] ?? LOCALE_DEFAULT;
        if (!isset($bySlug[$slug])) {
            $bySlug[$slug] = $row;
            continue;
        }
        $existingLocale = $bySlug[$slug]['locale'] ?? LOCALE_DEFAULT;
        if ($rowLocale === $locale && $existingLocale !== $locale) {
            $bySlug[$slug] = $row;
        }
    }
    return array_values($bySlug);
}

function blog_should_auto_translate(array $post, ?string $locale = null): bool
{
    require_once __DIR__ . '/locale.php';
    $locale = $locale ?? locale_current();
    $postLocale = $post['locale'] ?? LOCALE_DEFAULT;
    return $locale !== LOCALE_DEFAULT && $postLocale !== $locale;
}

function blog_post_url(string $slug, ?string $base = null, ?string $locale = null): string
{
    if ($base === null) {
        require_once __DIR__ . '/seo.php';
        $base = seo_site_url_rtrim();
    }
    require_once __DIR__ . '/locale.php';
    return seo_locale_absolute(rawurlencode($slug), $base, $locale);
}

function blog_fetch_by_slug(PDO $pdo, string $slug, ?string $locale = null): ?array
{
    require_once __DIR__ . '/locale.php';
    require_once __DIR__ . '/blog-translate.php';
    $locale = $locale ?? locale_current();
    $hasLocale = blog_has_locale_column($pdo);

    if ($hasLocale) {
        $stmt = $pdo->prepare('SELECT * FROM blogs WHERE slug = ? AND locale = ? AND is_published = 1 LIMIT 1');
        $stmt->execute([$slug, $locale]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($post) {
            return blog_should_auto_translate($post, $locale)
                ? blog_localize_post_for_web($post, $locale, 'full')
                : $post;
        }
        if ($locale !== LOCALE_DEFAULT) {
            $stmt->execute([$slug, LOCALE_DEFAULT]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($post) {
                return blog_localize_post_for_web($post, $locale, 'full');
            }
        }
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM blogs WHERE slug = ? AND is_published = 1 LIMIT 1');
    $stmt->execute([$slug]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) {
        return null;
    }
    return blog_should_auto_translate($post, $locale)
        ? blog_localize_post_for_web($post, $locale, 'full')
        : $post;
}

/**
 * @return array<int, array<string, mixed>>
 */
function blog_fetch_list(PDO $pdo, array $filters = []): array
{
    require_once __DIR__ . '/blog-translate.php';
    $locale = locale_current();
    $where = [blog_sql_public_only()];
    $params = [];

    if (blog_has_locale_column($pdo)) {
        $where[] = blog_sql_locale_with_fallback();
    }

    if (!empty($filters['category'])) {
        $where[] = 'category = ?';
        $params[] = $filters['category'];
    }
    if (!empty($filters['tag'])) {
        $where[] = '(tags LIKE ? OR keywords LIKE ?)';
        $like = '%' . $filters['tag'] . '%';
        $params[] = $like;
        $params[] = $like;
    }
    if (!empty($filters['search'])) {
        $where[] = '(title LIKE ? OR content LIKE ? OR meta_desc LIKE ? OR keywords LIKE ?)';
        $slike = '%' . $filters['search'] . '%';
        $params[] = $slike;
        $params[] = $slike;
        $params[] = $slike;
        $params[] = $slike;
    }

    $sql = 'SELECT ' . blog_sql_columns($pdo, ['id', 'title', 'slug', 'image_path', 'category', 'created_at', 'meta_desc', 'meta_title', 'tags', 'keywords', 'faq_json'])
        . ' FROM blogs WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = blog_pick_locale_rows($stmt->fetchAll(PDO::FETCH_ASSOC), $locale);

    if ($locale === LOCALE_DEFAULT) {
        return $rows;
    }

    return blog_localize_posts_for_web($rows, $locale, 'summary');
}

function blog_fetch_linkable_posts(PDO $pdo): array
{
    require_once __DIR__ . '/blog-translate.php';
    $locale = locale_current();
    $localeSql = blog_has_locale_column($pdo) ? ' AND ' . blog_sql_locale_with_fallback() : '';
    $sql = 'SELECT ' . blog_sql_columns($pdo, ['id', 'title', 'slug']) . ' FROM blogs WHERE '
        . blog_sql_public_only() . $localeSql . ' ORDER BY created_at DESC LIMIT 80';
    $rows = blog_pick_locale_rows($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC), $locale);
    if ($locale !== LOCALE_DEFAULT) {
        $rows = blog_localize_posts_for_web($rows, $locale, 'summary');
    }

    return array_map(static fn(array $row): array => [
        'title' => $row['title'],
        'slug'  => $row['slug'],
    ], array_slice($rows, 0, 40));
}

function blog_fetch_orphan_posts(PDO $pdo): array
{
    $sql = 'SELECT id, title, slug, meta_desc, created_at, category FROM blogs WHERE is_published = 1 AND is_orphan = 1 ORDER BY created_at DESC';
    try {
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function blog_has_orphan_posts(PDO $pdo): bool
{
    try {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM blogs WHERE is_published = 1 AND is_orphan = 1')->fetchColumn();
        return $count > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Sanitize and professionally structure blog HTML (Google Docs / paste cleanup).
 */
function blog_normalize_content(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    $html = preg_replace('/\s(data-start|data-end|data-path-to-node|data-index-in-node|data-pm-slice|dir|id)="[^"]*"/i', '', $html);
    $html = preg_replace('/<span id="docs-internal-guid[^"]*"[^>]*>/i', '', $html);
    $html = preg_replace('/\sstyle="[^"]*"/i', '', $html);
    $html = preg_replace('/\sclass=""/i', '', $html);

    for ($i = 0; $i < 8; $i++) {
        $next = preg_replace('/<span[^>]*>(.*?)<\/span>/is', '$1', $html);
        if ($next === null || $next === $html) {
            break;
        }
        $html = $next;
    }
    for ($i = 0; $i < 4; $i++) {
        $next = preg_replace('/<font[^>]*>(.*?)<\/font>/is', '$1', $html);
        if ($next === null || $next === $html) {
            break;
        }
        $html = $next;
    }

    $html = preg_replace('/<h1\b/i', '<h2', $html);
    $html = preg_replace('/<\/h1>/i', '</h2>', $html);
    $html = preg_replace('/<b\b/i', '<strong', $html);
    $html = preg_replace('/<\/b>/i', '</strong>', $html);
    $html = preg_replace('/<i\b/i', '<em', $html);
    $html = preg_replace('/<\/i>/i', '</em>', $html);

    $html = preg_replace('/<p>\s*<strong>\s*introduction\s*<\/strong>\s*<\/p>/i', '<h2>Introduction</h2>', $html);
    $html = preg_replace('/<p>\s*<strong>\s*INTRODUCTION\s*<\/strong>\s*<\/p>/i', '<h2>Introduction</h2>', $html);
    $html = preg_replace('/<p>\s*<strong>\s*INTRODUCTION\s*<\/strong>\s*\n?\s*/i', '<h2>Introduction</h2><p>', $html);

    $html = preg_replace_callback('/<p>(.*?)<\/p>/is', 'blog_normalize_paragraph_block', $html);

    $html = preg_replace_callback('/<p>(.*?)<\/p>/is', function (array $m): string {
        $inner = $m[1];
        $chunks = preg_split('/(?:<br\s*\/?>\s*){2,}/i', $inner) ?: [$inner];
        if (count($chunks) <= 1) {
            $single = trim(preg_replace('/<br\s*\/?>/i', ' ', $inner));
            return $single === '' ? '' : '<p>' . $single . '</p>';
        }
        $out = '';
        foreach ($chunks as $chunk) {
            $chunk = trim(preg_replace('/<br\s*\/?>/i', ' ', (string) $chunk));
            if ($chunk !== '') {
                $out .= '<p>' . $chunk . '</p>';
            }
        }
        return $out;
    }, $html);

    $html = preg_replace('/<h2>\s*<strong>(.*?)<\/strong>\s*<\/h2>/is', '<h2>$1</h2>', $html);
    $html = preg_replace('/<h3>\s*<strong>(.*?)<\/strong>\s*<\/h3>/is', '<h3>$1</h3>', $html);
    $html = preg_replace_callback('/<li>\s*(.*?)\s*<\/li>/is', function ($m) {
        return '<li>' . trim(strip_tags($m[1], '<a><strong><em>')) . '</li>';
    }, $html);

    $html = preg_replace('/<p[^>]*>(\s|&nbsp;|<br\s*\/?>)*<\/p>/i', '', $html);
    $html = preg_replace('/(<br\s*\/?>\s*){3,}/i', '<br><br>', $html);
    $html = preg_replace('/<h2>([^<]+)<\/h2>\s*<h2>\1<\/h2>/i', '<h2>$1</h2>', $html);

    return trim($html);
}

/**
 * @internal Split messy paragraphs into headings + body copy.
 */
function blog_normalize_paragraph_block(array $matches): string
{
    $inner = trim($matches[1]);
    if ($inner === '' || $inner === '<br>' || $inner === '<br/>') {
        return '';
    }

    $plain = trim(preg_replace('/\s+/', ' ', strip_tags($inner)));
    if ($plain === '') {
        return '';
    }

    if (preg_match('/^<strong>([^<]{2,120})<\/strong>\s*(?:<br\s*\/?>)?\s*$/i', $inner, $m)) {
        return blog_heading_tag_for_text($m[1]) . blog_heading_text($m[1]) . blog_heading_close($m[1]);
    }

    if (preg_match('/^<strong>([^<]{2,120})<\/strong>\s*(?:<br\s*\/?>)?\s*(.+)$/is', $inner, $m)) {
        $body = trim($m[2]);
        $out = blog_heading_tag_for_text($m[1]) . blog_heading_text($m[1]) . blog_heading_close($m[1]);
        if ($body !== '' && strip_tags($body) !== '') {
            $out .= '<p>' . $body . '</p>';
        }
        return $out;
    }

    if (preg_match('/^(.+?)(?:<br\s*\/?>\s*)+<strong>([^<]{2,120})<\/strong>\s*$/is', $inner, $m)) {
        $before = trim($m[1]);
        $out = $before !== '' ? '<p>' . $before . '</p>' : '';
        $out .= blog_heading_tag_for_text($m[2]) . blog_heading_text($m[2]) . blog_heading_close($m[2]);
        return $out;
    }

    if (preg_match('/^(.+?)<strong>([^<]{2,120})<\/strong>\s*(.+)$/is', $inner, $m)) {
        $before = trim($m[1]);
        $after = trim($m[3]);
        $title = trim($m[2]);
        if (mb_strlen($title) <= 80 && mb_strlen(strip_tags($before)) > 40) {
            $out = '<p>' . $before . '</p>';
            $out .= blog_heading_tag_for_text($title) . blog_heading_text($title) . blog_heading_close($title);
            if ($after !== '') {
                $out .= '<p>' . $after . '</p>';
            }
            return $out;
        }
    }

    if (preg_match('/^<strong>([^<]{2,80})<\/strong>\s*$/i', $plain)) {
        return blog_heading_tag_for_text($plain) . blog_heading_text($plain) . blog_heading_close($plain);
    }

    return '<p>' . $inner . '</p>';
}

function blog_heading_text(string $text): string
{
    $text = trim(strip_tags($text));
    if (strcasecmp($text, 'introduction') === 0) {
        return 'Introduction';
    }
    if (strcasecmp($text, 'conclusion') === 0) {
        return 'Conclusion';
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function blog_heading_tag_for_text(string $text): string
{
    $plain = trim(strip_tags($text));
    $lower = strtolower($plain);
    if (in_array($lower, ['introduction', 'conclusion', 'summary', 'key takeaways', 'final thoughts'], true)) {
        return '<h2>';
    }
    if (mb_strlen($plain) <= 55) {
        return '<h3>';
    }
    return '<h2>';
}

function blog_heading_close(string $text): string
{
    $plain = trim(strip_tags($text));
    return blog_heading_tag_for_text($text) === '<h3>' ? '</h3>' : '</h2>';
}

function blog_parse_tags(?string $tags, ?string $keywords = null): array
{
    $raw = trim((string) ($tags ?: ''));
    if ($raw === '' && !empty($keywords)) {
        $raw = $keywords;
    }
    if ($raw === '') {
        return [];
    }
    $parts = preg_split('/\s*,\s*/', $raw);
    $out = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $out[] = $part;
        }
    }
    return array_values(array_unique($out));
}

function blog_parse_faq(?string $faqJson): array
{
    if (empty($faqJson)) {
        return [];
    }
    $data = json_decode($faqJson, true);
    if (!is_array($data)) {
        return [];
    }
    $items = [];
    foreach ($data as $row) {
        $q = trim($row['question'] ?? $row['q'] ?? '');
        $a = trim($row['answer'] ?? $row['a'] ?? '');
        if ($q !== '' && $a !== '') {
            $items[] = ['question' => $q, 'answer' => $a];
        }
    }
    return $items;
}

function blog_reading_time(string $html): int
{
    $text = trim(strip_tags(html_entity_decode($html)));
    $words = str_word_count($text);
    return max(1, (int) ceil($words / 200));
}

function blog_clean_content(string $html, ?string $pageTitle = null, ?string $featuredImage = null): string
{
    $html = preg_replace('/<p[^>]*>(\s|&nbsp;|<br\s*\/?>)*<\/p>/i', '', $html);
    $html = preg_replace('/\s(data-start|data-end|data-path-to-node|data-index-in-node|data-pm-slice|dir|data-state)="[^"]*"/i', '', $html);
    $html = preg_replace('/<span class="" data-state="closed"><\/span>/i', '', $html);
    $html = preg_replace('/<p>\s*<br\s*\/?>\s*<\/p>/i', '', $html);
    $html = preg_replace('/\sstyle="[^"]*"/i', '', $html);
    $html = preg_replace('/\swidth="[^"]*"/i', '', $html);
    $html = preg_replace('/\salign="[^"]*"/i', '', $html);

    if ($pageTitle) {
        $plainTitle = strtolower(trim(strip_tags($pageTitle)));
        $html = preg_replace_callback('/<h1[^>]*>(.*?)<\/h1>/is', function ($m) use ($plainTitle) {
            $inner = strtolower(trim(strip_tags($m[1])));
            if ($inner === $plainTitle || levenshtein($inner, $plainTitle) < 5) {
                return '';
            }
            return $m[0];
        }, $html);
    }

    if ($featuredImage) {
        $featured = basename(blog_get_safe_path($featuredImage));
        $removedFirst = false;
        $html = preg_replace_callback('/<img[^>]+>/i', function ($m) use ($featured, &$removedFirst) {
            if ($removedFirst) {
                return $m[0];
            }
            if (stripos($m[0], $featured) !== false) {
                $removedFirst = true;
                return '';
            }
            return $m[0];
        }, $html);
        $html = preg_replace('/<p>\s*<\/p>/i', '', $html);
    }

    return trim($html);
}

function blog_excerpt(string $html, int $limit = 140): string
{
    $text = html_entity_decode($html);
    $text = preg_replace('/<h[1-6][^>]*>\s*introduction\s*<\/h[1-6]>/i', ' ', $text);
    $text = strip_tags($text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim(str_replace(['&nbsp;', 'IntroductionAs', 'Introduction'], [' ', 'Introduction As ', ''], $text));
    if ($text === '') {
        return '';
    }
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $limit - 3)) . '...';
}

function blog_faq_schema(array $faqItems, string $pageUrl): array
{
    if (empty($faqItems)) {
        return [];
    }
    $entities = [];
    foreach ($faqItems as $item) {
        $entities[] = [
            '@type'          => 'Question',
            'name'           => $item['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => strip_tags($item['answer']),
            ],
        ];
    }
    return [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $entities,
        'url'        => $pageUrl,
    ];
}

function blog_filter_url(?string $category = null, ?string $tag = null): string
{
    require_once __DIR__ . '/locale.php';
    $params = [];
    if ($category !== null && trim($category) !== '') {
        $params['category'] = trim($category);
    }
    if ($tag !== null && trim($tag) !== '') {
        $params['tag'] = trim($tag);
    }
    return locale_url(empty($params) ? 'blog' : 'blog?' . http_build_query($params));
}

function blog_detail_url(string $slug): string
{
    return blog_post_url($slug);
}

function blog_page_t(string $key, ?string $fallback = null): string
{
    locale_init();
    $blog = $GLOBALS['_locale_strings']['page']['blog'] ?? [];
    if (isset($blog[$key]) && is_string($blog[$key]) && $blog[$key] !== '') {
        return $blog[$key];
    }
    return $fallback ?? $key;
}

function blog_page_te(string $key, ?string $fallback = null): string
{
    return htmlspecialchars(blog_page_t($key, $fallback), ENT_QUOTES, 'UTF-8');
}

function blog_page_t_vars(string $key, array $vars, ?string $fallback = null): string
{
    $text = blog_page_t($key, $fallback);
    foreach ($vars as $name => $value) {
        $text = str_replace('{' . $name . '}', (string) $value, $text);
    }
    if (preg_match('/\{[^}]+\}/', $text) && count($vars) === 1) {
        $text = preg_replace('/\{[^}]+\}/', (string) reset($vars), $text);
    }
    return $text;
}

function blog_page_te_vars(string $key, array $vars, ?string $fallback = null): string
{
    return htmlspecialchars(blog_page_t_vars($key, $vars, $fallback), ENT_QUOTES, 'UTF-8');
}

function blog_translate_category(string $category, ?string $locale = null): string
{
    require_once __DIR__ . '/locale.php';
    require_once __DIR__ . '/blog-translate.php';
    $category = trim($category);
    if ($category === '') {
        return '';
    }
    $locale = $locale ?? locale_current();
    if ($locale === LOCALE_DEFAULT) {
        return $category;
    }
    static $cache = [];
    $key = $locale . '|' . strtolower($category);
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $file = blog_translate_cache_dir() . '/categories-' . preg_replace('/[^a-z0-9_-]/i', '', $locale) . '.json';
    if (is_file($file)) {
        $map = json_decode((string) file_get_contents($file), true);
        if (is_array($map)) {
            $lookup = $map[strtolower($category)] ?? null;
            if (is_string($lookup) && $lookup !== '') {
                $cache[$key] = $lookup;
                return $lookup;
            }
        }
    }

    if (blog_translate_runtime_enabled()) {
        $translated = blog_translate_text($category, $locale);
        blog_translate_category_cache_store($locale, $category, $translated);
        $cache[$key] = $translated;
        return $translated;
    }

    blog_queue_category_warm($category, $locale);
    $cache[$key] = $category;
    return $category;
}

function blog_translate_category_cache_store(string $locale, string $category, string $translated): void
{
    $file = blog_translate_cache_dir() . '/categories-' . preg_replace('/[^a-z0-9_-]/i', '', $locale) . '.json';
    $map = [];
    if (is_file($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded)) {
            $map = $decoded;
        }
    }
    $map[strtolower($category)] = $translated;
    @file_put_contents($file, json_encode($map, JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function blog_esc_attr(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function blog_fetch_sidebar_posts(PDO $pdo, int $excludeId, ?string $category = null, int $limit = 6, bool $publicOnly = true): array
{
    require_once __DIR__ . '/blog-translate.php';
    $locale = locale_current();
    $limit = max(1, min(12, $limit));
    $where = $publicOnly ? blog_sql_public_only() : blog_sql_indexable();
    if (blog_has_locale_column($pdo)) {
        $where .= ' AND ' . blog_sql_locale_with_fallback();
    }

    $sidebarCols = blog_sql_columns($pdo, ['id', 'title', 'slug', 'category', 'created_at', 'meta_desc', 'meta_title']);
    if ($category) {
        $stmt = $pdo->prepare(
            "SELECT {$sidebarCols} FROM blogs
             WHERE {$where} AND id != ? AND category = ?
             ORDER BY created_at DESC LIMIT " . ($limit * 3)
        );
        $stmt->execute([$excludeId, $category]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT {$sidebarCols} FROM blogs
             WHERE {$where} AND id != ?
             ORDER BY created_at DESC LIMIT " . ($limit * 3)
        );
        $stmt->execute([$excludeId]);
    }

    $rows = blog_pick_locale_rows($stmt->fetchAll(PDO::FETCH_ASSOC), $locale);
    $rows = array_slice($rows, 0, $limit);
    if ($locale !== LOCALE_DEFAULT) {
        $rows = blog_localize_posts_for_web($rows, $locale, 'summary');
    }
    return $rows;
}

function blog_fetch_prev_next(PDO $pdo, int $currentId, string $createdAt, bool $publicOnly = true): array
{
    require_once __DIR__ . '/blog-translate.php';
    $locale = locale_current();
    $where = $publicOnly ? blog_sql_public_only() : blog_sql_indexable();
    if (blog_has_locale_column($pdo)) {
        $where .= ' AND ' . blog_sql_locale_with_fallback();
    }

    $navCols = blog_sql_columns($pdo, ['id', 'title', 'slug', 'category', 'meta_desc', 'meta_title', 'created_at']);
    $prevStmt = $pdo->prepare(
        "SELECT {$navCols} FROM blogs
         WHERE {$where} AND created_at < ?
         ORDER BY created_at DESC LIMIT 6"
    );
    $prevStmt->execute([$createdAt]);
    $prevRows = blog_pick_locale_rows($prevStmt->fetchAll(PDO::FETCH_ASSOC), $locale);
    $prev = $prevRows[0] ?? null;

    $nextStmt = $pdo->prepare(
        "SELECT {$navCols} FROM blogs
         WHERE {$where} AND created_at > ?
         ORDER BY created_at ASC LIMIT 6"
    );
    $nextStmt->execute([$createdAt]);
    $nextRows = blog_pick_locale_rows($nextStmt->fetchAll(PDO::FETCH_ASSOC), $locale);
    $next = $nextRows[0] ?? null;

    if ($locale !== LOCALE_DEFAULT) {
        if ($prev) {
            $prev = blog_localize_post_for_web($prev, $locale, 'summary');
        }
        if ($next) {
            $next = blog_localize_post_for_web($next, $locale, 'summary');
        }
    }

    return ['prev' => $prev, 'next' => $next];
}

/**
 * @return array<int, array<string, mixed>>
 */
function blog_fetch_related(PDO $pdo, int $excludeId, string $category, int $limit = 3): array
{
    require_once __DIR__ . '/blog-translate.php';
    $locale = locale_current();
    $where = blog_sql_public_only();
    if (blog_has_locale_column($pdo)) {
        $where .= ' AND ' . blog_sql_locale_with_fallback();
    }

    $relatedCols = blog_sql_columns($pdo, ['id', 'title', 'slug', 'image_path', 'created_at', 'category', 'meta_desc', 'meta_title']);
    $stmt = $pdo->prepare(
        "SELECT {$relatedCols} FROM blogs
         WHERE {$where} AND category = ? AND id != ?
         ORDER BY created_at DESC LIMIT " . max($limit * 3, $limit)
    );
    $stmt->execute([$category, $excludeId]);
    $rows = blog_pick_locale_rows($stmt->fetchAll(PDO::FETCH_ASSOC), $locale);
    $rows = array_slice($rows, 0, $limit);
    if ($locale !== LOCALE_DEFAULT) {
        $rows = blog_localize_posts_for_web($rows, $locale, 'summary');
    }
    return $rows;
}

function blog_inject_internal_links(string $html, array $posts, string $currentSlug, int $maxLinks = 3): string
{
    if ($html === '' || empty($posts)) {
        return $html;
    }

    usort($posts, function ($a, $b) {
        return mb_strlen($b['title'] ?? '') - mb_strlen($a['title'] ?? '');
    });

    $linked = 0;
    foreach ($posts as $post) {
        if ($linked >= $maxLinks) {
            break;
        }
        $slug = trim($post['slug'] ?? '');
        $title = trim($post['title'] ?? '');
        if ($slug === '' || $title === '' || $slug === $currentSlug) {
            continue;
        }
        if (mb_strlen($title) < 12) {
            continue;
        }
        if (stripos($html, 'href="' . $slug . '"') !== false) {
            continue;
        }

        $pattern = '/(?<![\w="\'\/])' . preg_quote($title, '/') . '(?![^<]*>)/u';
        $replacement = '<a href="' . blog_esc_attr(blog_post_url($slug)) . '" title="' . blog_esc_attr($title) . '" class="blog-inline-link">$0</a>';
        $newHtml = preg_replace($pattern, $replacement, $html, 1, $count);
        if ($count > 0 && is_string($newHtml)) {
            $html = $newHtml;
            $linked++;
        }
    }

    return $html;
}

/**
 * Add id attributes to h2/h3 for table of contents anchors.
 */
function blog_add_heading_ids(string $html): string
{
    $index = 0;
    return preg_replace_callback('/<(h[23])([^>]*)>(.*?)<\/\1>/is', function ($m) use (&$index) {
        $index++;
        $tag = $m[1];
        $attrs = $m[2];
        if (preg_match('/\sid="/i', $attrs)) {
            return $m[0];
        }
        $text = trim(strip_tags($m[3]));
        $slug = 'section-' . $index . '-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($text));
        $slug = trim($slug, '-');
        return '<' . $tag . $attrs . ' id="' . blog_esc_attr($slug) . '">' . $m[3] . '</' . $tag . '>';
    }, $html);
}

/**
 * Build table of contents from h2/h3 headings.
 *
 * @return array<int, array{level: int, text: string, id: string}>
 */
function blog_build_toc(string $html): array
{
    $items = [];
    if (!preg_match_all('/<(h[23])[^>]*\sid="([^"]+)"[^>]*>(.*?)<\/\1>/is', $html, $matches, PREG_SET_ORDER)) {
        return $items;
    }
    foreach ($matches as $m) {
        $text = trim(strip_tags($m[3]));
        if ($text === '') {
            continue;
        }
        $items[] = [
            'level' => (int) substr($m[1], 1),
            'text'  => $text,
            'id'    => $m[2],
        ];
    }
    return $items;
}
