<?php

require_once __DIR__ . '/case-studies-demo.php';

function case_study_post_url(string $slug, ?string $base = null, ?string $locale = null): string
{
    if ($base === null) {
        require_once __DIR__ . '/seo.php';
        $base = seo_site_url_rtrim();
    }
    require_once __DIR__ . '/locale.php';
    return seo_locale_absolute('case-studies/' . rawurlencode($slug), $base, $locale);
}

function case_study_has_locale_column(PDO $pdo): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $cache = (bool) $pdo->query("SHOW COLUMNS FROM case_studies LIKE 'locale'")->fetch();
    } catch (PDOException $e) {
        $cache = false;
    }
    return $cache;
}

function case_study_sql_locale(string $alias = '', ?string $locale = null): string
{
    require_once __DIR__ . '/locale.php';
    $locale = $locale ?? locale_current();
    $p = $alias !== '' ? $alias . '.' : '';
    return "{$p}locale = '" . str_replace("'", "''", $locale) . "'";
}

function case_study_fetch_by_slug(PDO $pdo, string $slug, ?string $locale = null): ?array
{
    require_once __DIR__ . '/locale.php';
    $locale = $locale ?? locale_current();

    try {
        if (case_study_has_locale_column($pdo)) {
            $stmt = $pdo->prepare('SELECT * FROM case_studies WHERE slug = ? AND locale = ? AND is_published = 1 LIMIT 1');
            $stmt->execute([$slug, $locale]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row && $locale !== LOCALE_DEFAULT) {
                $stmt->execute([$slug, LOCALE_DEFAULT]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $row['_locale_fallback'] = true;
                }
            }
            return $row ?: case_study_demo_by_slug($slug);
        }
        $stmt = $pdo->prepare('SELECT * FROM case_studies WHERE slug = ? AND is_published = 1 LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: case_study_demo_by_slug($slug);
    } catch (PDOException $e) {
        return case_study_demo_by_slug($slug);
    }
}

function case_study_sql_published(string $alias = ''): string
{
    $p = $alias !== '' ? $alias . '.' : '';
    return "{$p}is_published = 1";
}

function case_study_fetch_published(PDO $pdo, ?string $industry = null, int $limit = 50): array
{
    try {
        $sql = 'SELECT * FROM case_studies WHERE ' . case_study_sql_published();
        if (case_study_has_locale_column($pdo)) {
            $sql .= ' AND ' . case_study_sql_locale();
        }
        $params = [];
        if ($industry) {
            $sql .= ' AND industry LIKE ?';
            $params[] = '%' . $industry . '%';
        }
        $sql .= ' ORDER BY created_at DESC LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows !== [] ? $rows : case_study_demo_entries();
    } catch (PDOException $e) {
        return case_study_demo_entries();
    }
}

function case_study_make_slug(string $title, ?int $id = null): string
{
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    return $slug !== '' ? $slug : 'case-study-' . ($id ?? time());
}

function case_study_ensure_unique_slug(PDO $pdo, string $slug, ?int $excludeId = null): string
{
    $candidate = $slug;
    $n = 2;
    while (true) {
        if ($excludeId) {
            $stmt = $pdo->prepare('SELECT id FROM case_studies WHERE slug = ? AND id != ? LIMIT 1');
            $stmt->execute([$candidate, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM case_studies WHERE slug = ? LIMIT 1');
            $stmt->execute([$candidate]);
        }
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $candidate = $slug . '-' . $n;
        $n++;
    }
}

function seo_case_study_schema(array $cs, string $base): array
{
    $url = case_study_post_url($cs['slug'], $base);
    return [
        '@context' => 'https://schema.org',
        '@type'    => 'Article',
        'headline' => $cs['title'],
        'description' => $cs['meta_desc'] ?? strip_tags($cs['results'] ?? ''),
        'url'      => $url,
        'datePublished' => date('c', strtotime($cs['created_at'])),
        'author'   => ['@type' => 'Organization', 'name' => 'Bisani Brothers Pvt. Ltd.'],
        'publisher' => [
            '@type' => 'Organization',
            'name'  => 'Bisani Brothers Pvt. Ltd.',
            'logo'  => ['@type' => 'ImageObject', 'url' => rtrim($base, '/') . '/assets/images/logos.png'],
        ],
        'about'    => $cs['industry'] ?? 'Business Services',
    ];
}

function seo_ping_after_case_study_change(PDO $pdo): void
{
    require_once __DIR__ . '/seo.php';
    require_once __DIR__ . '/industry-config.php';
    $base = seo_site_url_rtrim();
    $urls = [
        $base . '/case-studies',
        $base . '/industries',
        $base . '/sitemap.xml',
        $base . '/llms.txt',
        $base . '/rss.xml',
    ];
    try {
        $rows = $pdo->query('SELECT slug FROM case_studies WHERE is_published = 1');
        while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
            $urls[] = case_study_post_url($row['slug'], $base);
        }
    } catch (Exception $e) {
    }
    foreach (array_keys(industry_get_all()) as $indSlug) {
        $urls[] = $base . '/industries/' . rawurlencode($indSlug);
    }
    seo_submit_indexnow(array_values(array_unique($urls)));
    seo_ping_bing_sitemap();
}
