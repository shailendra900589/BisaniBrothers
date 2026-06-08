<?php
require_once __DIR__ . '/seo-config.php';
require_once __DIR__ . '/locale.php';

/**
 * Production URL for sitemaps/feeds; auto-detected URL on localhost.
 */
function seo_site_url(): string
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (preg_match('/^(localhost|127\.0\.0\.1)/i', $host)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $project_root = str_replace('\\', '/', realpath(dirname(__DIR__)));
        $doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
        $relative = substr($project_root, strlen($doc_root));
        $folder = ($relative === '' || $relative === '/') ? '/' : rtrim($relative, '/') . '/';
        return rtrim($protocol . '://' . $host . $folder, '/') . '/';
    }
    return SEO_PRODUCTION_URL . '/';
}

function seo_site_url_rtrim(): string
{
    return rtrim(seo_site_url(), '/');
}

function seo_is_production(): bool
{
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $host = preg_replace('/:\d+$/', '', $host);
    return in_array($host, [SEO_SITE_DOMAIN, 'bisanibrothers.com'], true);
}

/**
 * Search Console / Webmaster verification meta tags (live site only).
 */
function seo_verification_meta_tags(): array
{
    if (!seo_is_production()) {
        return [];
    }

    $tags = [];

    if (SEO_GOOGLE_SITE_VERIFICATION !== '') {
        $tags[] = [
            'name'    => 'google-site-verification',
            'content' => SEO_GOOGLE_SITE_VERIFICATION,
        ];
    }

    if (SEO_BING_SITE_VERIFICATION !== '') {
        $tags[] = [
            'name'    => 'msvalidate.01',
            'content' => SEO_BING_SITE_VERIFICATION,
        ];
    }

    if (SEO_YANDEX_SITE_VERIFICATION !== '') {
        $tags[] = [
            'name'    => 'yandex-verification',
            'content' => SEO_YANDEX_SITE_VERIFICATION,
        ];
    }

    return $tags;
}

function seo_build_canonical_url(string $base_url): string
{
    require_once __DIR__ . '/locale.php';
    $path = locale_request_path();

    return rtrim($base_url, '/') . ($path === '/' ? '/' : $path);
}

function seo_get_page_keywords(): string
{
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
    return SEO_PAGE_KEYWORDS[$script] ?? SEO_DEFAULT_KEYWORDS;
}

/**
 * Build SEO keywords from blog title, category and content when DB field is empty.
 */
function seo_suggest_blog_keywords(array $post): string
{
    if (!empty($post['keywords'])) {
        return trim($post['keywords']);
    }

    $parts = [];
    if (!empty($post['category'])) {
        $parts[] = trim($post['category']);
    }

    $title = strtolower($post['title'] ?? '');
    $content = strtolower(strip_tags(html_entity_decode($post['content'] ?? '')));

    $topicMap = [
        'fintech|fin tech|nbfc|lending|loan|finance|merchant onboarding|payment' => 'FinTech India, financial services, merchant onboarding, lending solutions',
        'staffing|workforce|hiring|recruitment|manpower|employment' => 'staffing solutions India, workforce deployment, bulk hiring, field jobs',
        'btl|atl|activation|brand|marketing|promotion' => 'BTL activation, brand marketing India, on-ground promotions, field marketing',
        'ev |electric vehicle|charging|infrastructure' => 'EV infrastructure India, charging station rollout, green mobility',
        'market research|survey|insights|data collection' => 'market research India, survey execution, consumer insights',
        'sales|growth|expansion|rollout|execution|partner' => 'sales execution India, business growth, on-ground execution, field teams',
        'tier 2|tier 3|metro|rural|local network|regional' => 'Tier 2 Tier 3 expansion, local networks India, regional growth',
        'distributor|dealer|channel|network' => 'distributor network, channel partners India, field partner network',
    ];

    $haystack = $title . ' ' . mb_substr($content, 0, 1200);
    foreach ($topicMap as $pattern => $keywords) {
        if (preg_match('/' . $pattern . '/i', $haystack)) {
            $parts[] = $keywords;
        }
    }

    $titleWords = preg_replace('/[^a-z0-9\s]/i', ' ', $title);
    $titleWords = preg_replace('/\s+/', ' ', trim($titleWords));
    if ($titleWords !== '') {
        $parts[] = $titleWords;
    }

    $parts[] = 'Bisani Brothers';
    $parts[] = 'business insights India';

    $joined = implode(', ', array_unique(array_filter(array_map('trim', $parts))));
    return mb_substr($joined, 0, 500);
}

function seo_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function seo_absolute_image(?string $path, string $base_url): string
{
    if (empty($path)) {
        return rtrim($base_url, '/') . '/assets/images/logos.png';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $clean = ltrim(str_replace(['../', './'], '', $path), '/');
    return rtrim($base_url, '/') . '/' . $clean;
}

function seo_strip_text(string $html, int $limit = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($html))));
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $limit - 3)) . '...';
}

function seo_get_static_pages(): array
{
    return SEO_STATIC_PAGES;
}

function seo_get_all_indexable_urls(PDO $pdo): array
{
    require_once __DIR__ . '/locale.php';
    $base = seo_site_url_rtrim();
    $paths = [];

    foreach (array_keys(SEO_STATIC_PAGES) as $path) {
        foreach (LOCALE_SUPPORTED as $loc) {
            $paths[] = seo_locale_path(ltrim($path, '/'), $loc);
        }
    }

    try {
        $localeCol = $pdo->query("SHOW COLUMNS FROM blogs LIKE 'locale'")->fetch();
        $blogSql = $localeCol
            ? "SELECT slug, locale FROM blogs WHERE is_published = 1 ORDER BY created_at DESC"
            : "SELECT slug FROM blogs WHERE is_published = 1 ORDER BY created_at DESC";
        $blogs = $pdo->query($blogSql);
        while ($row = $blogs->fetch(PDO::FETCH_ASSOC)) {
            $loc = $row['locale'] ?? LOCALE_DEFAULT;
            $paths[] = seo_locale_path(rawurlencode($row['slug']), $loc);
        }
    } catch (Exception $e) {
    }

    try {
        require_once __DIR__ . '/job-helpers.php';
        $localeCol = job_has_locale_column($pdo);
        $jobSql = $localeCol
            ? 'SELECT slug, title, location, locale FROM jobs WHERE status = 1 ORDER BY posted_date DESC'
            : 'SELECT slug, title, location FROM jobs WHERE status = 1 ORDER BY posted_date DESC';
        $jobs = $pdo->query($jobSql);
        while ($row = $jobs->fetch(PDO::FETCH_ASSOC)) {
            $slug = $row['slug'] ?? job_make_slug($row['title'], $row['location'] ?? null);
            $loc = $row['locale'] ?? LOCALE_DEFAULT;
            $paths[] = seo_locale_path('jobs/' . rawurlencode($slug), $loc);
        }
    } catch (Exception $e) {
    }

    try {
        require_once __DIR__ . '/case-study-helpers.php';
        $localeCol = case_study_has_locale_column($pdo);
        $caseSql = $localeCol
            ? 'SELECT slug, locale FROM case_studies WHERE is_published = 1'
            : 'SELECT slug FROM case_studies WHERE is_published = 1';
        $cases = $pdo->query($caseSql);
        while ($row = $cases->fetch(PDO::FETCH_ASSOC)) {
            $loc = $row['locale'] ?? LOCALE_DEFAULT;
            $paths[] = seo_locale_path('case-studies/' . rawurlencode($row['slug']), $loc);
        }
    } catch (Exception $e) {
    }

    require_once __DIR__ . '/industry-config.php';
    foreach (array_keys(INDUSTRY_PAGES) as $indSlug) {
        foreach (LOCALE_SUPPORTED as $loc) {
            $paths[] = seo_locale_path('industries/' . rawurlencode($indSlug), $loc);
        }
    }

    foreach (LOCALE_SUPPORTED as $loc) {
        $paths[] = seo_locale_path('', $loc);
        $paths[] = seo_locale_path('search', $loc);
        $paths[] = seo_locale_path('blog', $loc);
        $paths[] = seo_locale_path('faqs', $loc);
    }

    $paths[] = 'sitemap.xml';
    $paths[] = 'rss.xml';
    $paths[] = 'llms.txt';
    $paths[] = 'orphan-index';
    $paths[] = 'jobs-index';

    $urls = [];
    foreach (array_unique($paths) as $rel) {
        $urls[] = rtrim($base, '/') . '/' . ltrim($rel, '/');
    }

    return array_values(array_unique($urls));
}

/** Bulk IndexNow + Bing sitemap ping (CLI, cron, or admin button). */
function seo_run_bulk_reindex(PDO $pdo): array
{
    $urls = seo_get_all_indexable_urls($pdo);
    seo_submit_indexnow($urls);
    seo_ping_bing_sitemap();

    return [
        'url_count' => count($urls),
        'sitemap'   => seo_site_url_rtrim() . '/sitemap.xml',
        'messages'  => [
            'IndexNow: submitted ' . count($urls) . ' URLs.',
            'Bing sitemap ping sent for ' . seo_site_url_rtrim() . '/sitemap.xml',
            'Google Search Console: submit sitemap manually after verification → sitemap.xml',
        ],
    ];
}

/**
 * Submit URLs to IndexNow (Bing, Yandex, DuckDuckGo via Bing index, etc.)
 */
function seo_submit_indexnow(array $urls): void
{
    $urls = array_values(array_unique(array_filter($urls)));
    if (empty($urls)) {
        return;
    }

    $host = parse_url(seo_site_url(), PHP_URL_HOST);
    $key = SEO_INDEXNOW_KEY;
    $keyLocation = seo_site_url_rtrim() . '/' . $key . '.txt';

    $payload = json_encode([
        'host'        => $host,
        'key'         => $key,
        'keyLocation' => $keyLocation,
        'urlList'     => array_slice($urls, 0, 10000),
    ]);

    $endpoints = [
        'https://api.indexnow.org/indexnow',
        'https://www.bing.com/indexnow',
        'https://yandex.com/indexnow',
    ];

    foreach ($endpoints as $endpoint) {
        seo_http_post_json($endpoint, $payload);
    }
}

/** Notify Bing Webmaster that sitemap was updated. */
function seo_ping_bing_sitemap(): void
{
    $sitemap = seo_site_url_rtrim() . '/sitemap.xml';
    $pingUrl = 'https://www.bing.com/webmaster/ping.aspx?siteMap=' . rawurlencode($sitemap);
    if (function_exists('curl_init')) {
        $ch = curl_init($pingUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        curl_exec($ch);
        curl_close($ch);
        return;
    }
    @file_get_contents($pingUrl);
}

function seo_http_post_json(string $url, string $json): void
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        curl_exec($ch);
        curl_close($ch);
        return;
    }

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $json,
            'timeout' => 8,
        ],
    ]);
    @file_get_contents($url, false, $context);
}

function seo_ping_after_blog_change(PDO $pdo, string $slug, bool $isOrphan = false): void
{
    require_once __DIR__ . '/blog-helpers.php';
    $base = seo_site_url_rtrim();
    $urls = [
        $base . '/' . rawurlencode($slug),
        $base . '/blog',
        $base . '/sitemap.xml',
        $base . '/rss.xml',
        $base . '/llms.txt',
    ];
    if ($isOrphan || blog_has_orphan_posts($pdo)) {
        $urls[] = $base . '/orphan-index';
    }
    seo_submit_indexnow($urls);
}

function seo_ping_after_job_change(PDO $pdo): void
{
    require_once __DIR__ . '/job-helpers.php';
    job_refresh_seo_signals($pdo);
}

function seo_organization_schema(string $base_url): array
{
    return [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => SEO_SITE_NAME,
        'alternateName' => 'Bisani Brothers',
        'url'      => rtrim($base_url, '/') . '/',
        'logo'     => rtrim($base_url, '/') . '/assets/images/logos.png',
        'description' => 'Integrated business solutions provider specializing in Sales Execution, Staffing, and Market Research across India.',
        'address'  => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => 'D-1012/13 Indira Nagar',
            'addressLocality' => 'Lucknow',
            'addressRegion'   => 'Uttar Pradesh',
            'postalCode'      => '226016',
            'addressCountry'  => 'IN',
        ],
        'contactPoint' => [
            '@type'             => 'ContactPoint',
            'telephone'         => '+91-522-4530208',
            'contactType'       => 'customer service',
            'email'             => 'contact@bisanibrother.com',
            'areaServed'        => 'IN',
            'availableLanguage' => ['en', 'hi'],
        ],
        'sameAs' => [
            'https://www.linkedin.com/company/bisani-brothers',
            'https://www.facebook.com/bisanibrothers',
            'https://www.instagram.com/bisanibrothers',
            'https://twitter.com/bisanibrothers',
            'https://www.youtube.com/@bisanibrothers',
        ],
    ];
}

function seo_website_schema(string $base_url): array
{
    $root = rtrim($base_url, '/');
    return [
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => SEO_SITE_NAME,
        'url'      => $root . '/',
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => $root . '/search?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

function seo_breadcrumb_schema(array $items): array
{
    $list = [];
    $pos = 1;
    foreach ($items as $item) {
        $list[] = [
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => $item['name'],
            'item'     => $item['url'],
        ];
    }
    return [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $list,
    ];
}

function seo_blog_posting_schema(array $post, string $base_url): array
{
    $root = rtrim($base_url, '/');
    $slug = $post['slug'];
    $image = seo_absolute_image($post['image_path'] ?? '', $base_url);
    $desc = !empty($post['meta_desc'])
        ? $post['meta_desc']
        : seo_strip_text($post['content'] ?? '', 160);

    return [
        '@context'         => 'https://schema.org',
        '@type'            => 'BlogPosting',
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id'   => $root . '/' . rawurlencode($slug),
        ],
        'headline'         => $post['title'],
        'description'      => $desc,
        'image'            => [$image],
        'datePublished'    => date('c', strtotime($post['created_at'])),
        'dateModified'     => date('c', strtotime($post['created_at'])),
        'author'           => [
            '@type' => 'Organization',
            'name'  => 'Bisani Brothers Editorial Team',
            'url'   => $root . '/',
        ],
        'publisher'        => [
            '@type' => 'Organization',
            'name'  => SEO_SITE_NAME,
            'logo'  => [
                '@type' => 'ImageObject',
                'url'   => $root . '/assets/images/logos.png',
            ],
        ],
        'articleSection'   => $post['category'] ?? 'Business Insights',
        'keywords'         => $post['keywords'] ?? SEO_DEFAULT_KEYWORDS,
        'inLanguage'       => locale_schema_language(),
        'url'              => $root . '/' . rawurlencode($slug),
    ];
}

function seo_webpage_schema(string $pageTitle, string $pageDesc, string $canonical_url): array
{
    return [
        '@context'    => 'https://schema.org',
        '@type'       => 'WebPage',
        'name'        => $pageTitle,
        'description' => $pageDesc,
        'url'         => $canonical_url,
        'inLanguage'  => locale_schema_language(),
        'isPartOf'    => [
            '@type' => 'WebSite',
            'name'  => SEO_SITE_NAME,
            'url'   => seo_site_url(),
        ],
    ];
}

function seo_service_schema(string $serviceName, string $pageDesc, string $canonical_url): array
{
    return [
        '@context'    => 'https://schema.org',
        '@type'       => 'Service',
        'name'        => $serviceName,
        'description' => $pageDesc,
        'url'         => $canonical_url,
        'provider'    => [
            '@type' => 'Organization',
            'name'  => SEO_SITE_NAME,
            'url'   => seo_site_url(),
        ],
        'areaServed'  => [
            '@type' => 'Country',
            'name'  => 'India',
        ],
    ];
}

function seo_output_json_ld(array $data): string
{
    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
