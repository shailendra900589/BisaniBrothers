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
    require_once __DIR__ . '/upload-storage.php';
    $clean = upload_storage_public_url($path);

    return rtrim($base_url, '/') . '/' . ltrim($clean, '/');
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
        $paths[] = seo_locale_path(ltrim($path, '/'));
    }

    try {
        $localeCol = $pdo->query("SHOW COLUMNS FROM blogs LIKE 'locale'")->fetch();
        $blogSql = $localeCol
            ? "SELECT slug, locale FROM blogs WHERE is_published = 1 ORDER BY created_at DESC"
            : "SELECT slug FROM blogs WHERE is_published = 1 ORDER BY created_at DESC";
        $blogs = $pdo->query($blogSql);
        while ($row = $blogs->fetch(PDO::FETCH_ASSOC)) {
            $paths[] = seo_locale_path(rawurlencode($row['slug']));
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
            $paths[] = seo_locale_path('jobs/' . rawurlencode($slug));
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
            $paths[] = seo_locale_path('case-studies/' . rawurlencode($row['slug']));
        }
    } catch (Exception $e) {
    }

    require_once __DIR__ . '/industry-config.php';
    foreach (array_keys(INDUSTRY_PAGES) as $indSlug) {
        $paths[] = seo_locale_path('industries/' . rawurlencode($indSlug));
    }

    $paths[] = seo_locale_path('');
    $paths[] = seo_locale_path('search');
    $paths[] = seo_locale_path('blog');
    $paths[] = seo_locale_path('faqs');

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

function seo_organization_knows_about(): array
{
    return [
        'Offline Service Provider',
        'Sales And Service Support',
        'Feet on Street Services',
        'Gig Services',
        'Brand Promotion Activity',
        'Offline And Online Services',
        'BTL ATL Field Force Services',
        'B2B and B2C Services',
        'EV Support Services',
        'Financial Services Provider',
        'Third Party Payroll Services',
        'Verification Services',
        'Staffing Services',
        'Market Research India',
        'Field Sales Execution',
    ];
}

function seo_organization_id(string $base_url): string
{
    return rtrim($base_url, '/') . '/#organization';
}

function seo_organization_schema(string $base_url): array
{
    $root = rtrim($base_url, '/');
    $social = seo_organization_same_as();

    return [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        '@id'      => seo_organization_id($base_url),
        'name'     => SEO_SITE_LEGAL_NAME,
        'alternateName' => 'Bisani Brothers',
        'url'      => $root . '/',
        'logo'     => $root . '/assets/images/logos.png',
        'description' => SEO_HOMEPAGE_META_DESC,
        'foundingDate' => '2014',
        'founder' => [
            '@type' => 'Person',
            'name'  => 'Ashish Bisani',
            'jobTitle' => 'Founder',
        ],
        'numberOfEmployees' => [
            '@type'    => 'QuantitativeValue',
            'minValue' => 2100,
        ],
        'knowsAbout' => seo_organization_knows_about(),
        'address'  => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => 'D-1012/13 Indira Nagar',
            'addressLocality' => 'Lucknow, Uttar Pradesh, India',
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
            'availableLanguage' => ['en'],
        ],
        'sameAs' => $social,
    ];
}

function seo_organization_same_as(): array
{
    return [
        'https://www.linkedin.com/company/bisani-brothers',
        'https://www.facebook.com/profile.php?id=61582749106777',
        'https://www.instagram.com/bisanibrothers/',
        SEO_YOUTUBE_CHANNEL,
    ];
}

function seo_local_business_schema(string $base_url): array
{
    $root = rtrim($base_url, '/');

    return [
        '@context' => 'https://schema.org',
        '@type'    => 'LocalBusiness',
        '@id'      => $root . '/#localbusiness',
        'name'     => SEO_SITE_LEGAL_NAME,
        'alternateName' => 'Bisani Brothers',
        'url'      => $root . '/',
        'image'    => $root . '/assets/images/logos.png',
        'logo'     => $root . '/assets/images/logos.png',
        'description' => SEO_HOMEPAGE_META_DESC,
        'telephone' => '+91-522-4530208',
        'email'    => 'contact@bisanibrother.com',
        'priceRange' => '$$',
        'foundingDate' => '2014',
        'numberOfEmployees' => [
            '@type'    => 'QuantitativeValue',
            'minValue' => 2100,
        ],
        'knowsAbout' => seo_organization_knows_about(),
        'parentOrganization' => [
            '@type' => 'Organization',
            '@id'   => seo_organization_id($base_url),
        ],
        'address'  => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => 'D-1012/13 Indira Nagar',
            'addressLocality' => 'Lucknow',
            'addressRegion'   => 'Uttar Pradesh',
            'postalCode'      => '226016',
            'addressCountry'  => 'IN',
        ],
        'geo' => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => 26.8467,
            'longitude' => 80.9462,
        ],
        'areaServed' => [
            '@type' => 'Country',
            'name'  => 'India',
        ],
        'openingHoursSpecification' => [
            [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                'opens'     => '09:30',
                'closes'    => '18:30',
            ],
        ],
        'sameAs' => seo_organization_same_as(),
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
            '@id'   => seo_organization_id($base_url),
            'name'  => SEO_SITE_LEGAL_NAME,
            'url'   => $root . '/about',
            'knowsAbout' => seo_organization_knows_about(),
        ],
        'publisher'        => [
            '@type' => 'Organization',
            '@id'   => seo_organization_id($base_url),
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

function seo_webpage_schema(string $pageTitle, string $pageDesc, string $canonical_url, string $type = 'WebPage'): array
{
    return [
        '@context'    => 'https://schema.org',
        '@type'       => $type,
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

function seo_about_page_schema(string $pageTitle, string $pageDesc, string $canonical_url): array
{
    $schema = seo_webpage_schema($pageTitle, $pageDesc, $canonical_url, 'AboutPage');
    $schema['mainEntity'] = [
        '@type' => 'Organization',
        '@id'   => seo_organization_id(seo_site_url()),
    ];

    return $schema;
}

function seo_contact_page_schema(string $pageTitle, string $pageDesc, string $canonical_url): array
{
    $schema = seo_webpage_schema($pageTitle, $pageDesc, $canonical_url, 'ContactPage');
    $schema['telephone'] = '+91-522-4530208';
    $schema['email'] = 'contact@bisanibrother.com';

    return $schema;
}

function seo_collection_page_schema(string $pageTitle, string $pageDesc, string $canonical_url): array
{
    return seo_webpage_schema($pageTitle, $pageDesc, $canonical_url, 'CollectionPage');
}

/**
 * @param array<int, array{name: string, url: string}> $items
 */
function seo_item_list_schema(string $name, string $desc, array $items, string $canonical_url): array
{
    $list = [];
    $pos = 1;
    foreach ($items as $item) {
        if (empty($item['name']) || empty($item['url'])) {
            continue;
        }
        $list[] = [
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => $item['name'],
            'url'      => $item['url'],
        ];
    }

    return [
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => $name,
        'description'     => $desc,
        'url'             => $canonical_url,
        'numberOfItems'   => count($list),
        'itemListElement' => $list,
    ];
}

function seo_canonical_for_path(string $relativePath = ''): string
{
    require_once __DIR__ . '/locale.php';
    $path = locale_url(ltrim($relativePath, '/'));

    return seo_site_url_rtrim() . ($path === '/' ? '/' : '/' . ltrim($path, '/'));
}

function seo_service_schema(string $serviceName, string $pageDesc, string $canonical_url, string|array|null $serviceType = null): array
{
    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Service',
        'name'        => $serviceName,
        'description' => $pageDesc,
        'url'         => $canonical_url,
        'provider'    => [
            '@type' => 'Organization',
            'name'  => SEO_SITE_LEGAL_NAME,
            'url'   => seo_site_url(),
        ],
        'areaServed'  => [
            '@type' => 'Country',
            'name'  => 'India',
        ],
    ];

    if ($serviceType !== null && $serviceType !== '') {
        $schema['serviceType'] = $serviceType;
    }

    return $schema;
}

function seo_faq_schema(array $faqItems, string $pageUrl): array
{
    if ($faqItems === []) {
        return [];
    }

    $entities = [];
    foreach ($faqItems as $item) {
        if (empty($item['question']) || empty($item['answer'])) {
            continue;
        }
        $entities[] = [
            '@type'          => 'Question',
            'name'           => $item['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => strip_tags((string) $item['answer']),
            ],
        ];
    }

    if ($entities === []) {
        return [];
    }

    return [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $entities,
        'url'        => $pageUrl,
    ];
}

function seo_output_json_ld(array $data): string
{
    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
