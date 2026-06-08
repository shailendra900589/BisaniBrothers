<?php

require_once __DIR__ . '/locale.php';

function search_service_pages(): array
{
    locale_init();
    $strings = $GLOBALS['_locale_strings'] ?? [];
    if (!empty($strings['search_pages']) && is_array($strings['search_pages'])) {
        return $strings['search_pages'];
    }
    $en = require dirname(__DIR__) . '/lang/en.php';
    return $en['search_pages'] ?? [];
}

function search_match_score(string $haystack, string $query): int
{
    $haystack = strtolower($haystack);
    $query = strtolower(trim($query));
    if ($query === '') {
        return 0;
    }
    if ($haystack === $query) {
        return 100;
    }
    if (str_contains($haystack, $query)) {
        return 80;
    }
    $words = preg_split('/\s+/', $query);
    $score = 0;
    foreach ($words as $w) {
        if (strlen($w) >= 3 && str_contains($haystack, $w)) {
            $score += 20;
        }
    }
    return min($score, 70);
}

function search_site(PDO $pdo, string $query, int $limit = 30): array
{
    $query = trim($query);
    if ($query === '' || mb_strlen($query) < 2) {
        return [];
    }

    require_once __DIR__ . '/blog-helpers.php';
    require_once __DIR__ . '/job-helpers.php';

    $results = [];
    $like = '%' . $query . '%';
    $locale = locale_current();

    try {
        require_once __DIR__ . '/blog-translate.php';
        $localeSql = blog_has_locale_column($pdo) ? ' AND ' . blog_sql_locale_with_fallback() : '';
        $stmt = $pdo->prepare(
            'SELECT id, title, slug, category, meta_desc, content, locale, faq_json, created_at FROM blogs WHERE '
            . blog_sql_public_only()
            . $localeSql
            . ' AND (title LIKE ? OR content LIKE ? OR meta_desc LIKE ? OR keywords LIKE ? OR tags LIKE ?) ORDER BY created_at DESC LIMIT 30'
        );
        $stmt->execute([$like, $like, $like, $like, $like]);
        $blogRows = blog_pick_locale_rows($stmt->fetchAll(PDO::FETCH_ASSOC), $locale);
        if ($locale !== LOCALE_DEFAULT) {
            $blogRows = blog_localize_posts($blogRows, $locale, 'summary');
        }
        foreach (array_slice($blogRows, 0, 15) as $row) {
            $results[] = [
                'type'  => 'Blog',
                'title' => $row['title'],
                'url'   => locale_url($row['slug']),
                'desc'  => $row['meta_desc'] ?: seo_strip_text($row['content'] ?? '', 140),
                'score' => search_match_score($row['title'] . ' ' . ($row['category'] ?? ''), $query) + 10,
            ];
        }
    } catch (Exception $e) {
    }

    try {
        $localeSql = job_has_locale_column($pdo) ? ' AND ' . job_sql_locale() : '';
        $stmt = $pdo->prepare(
            'SELECT title, slug, location, type, description FROM jobs WHERE status = 1'
            . $localeSql
            . ' AND (title LIKE ? OR location LIKE ? OR description LIKE ? OR type LIKE ?) ORDER BY posted_date DESC LIMIT 10'
        );
        $stmt->execute([$like, $like, $like, $like]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $slug = $row['slug'] ?? job_make_slug($row['title'], $row['location'] ?? null);
            $results[] = [
                'type'  => 'Job',
                'title' => $row['title'] . ' — ' . ($row['location'] ?? ''),
                'url'   => locale_url('jobs/' . $slug),
                'desc'  => seo_strip_text($row['description'] ?? '', 140),
                'score' => search_match_score($row['title'] . ' ' . ($row['location'] ?? ''), $query) + 8,
            ];
        }
    } catch (Exception $e) {
    }

    try {
        require_once __DIR__ . '/case-study-helpers.php';
        $localeSql = case_study_has_locale_column($pdo) ? ' AND ' . case_study_sql_locale() : '';
        $stmt = $pdo->prepare(
            'SELECT title, slug, industry, service_line, client_name, results, challenge, approach, content, keywords
             FROM case_studies WHERE is_published = 1'
             . $localeSql
             . ' AND (title LIKE ? OR results LIKE ? OR challenge LIKE ? OR approach LIKE ? OR industry LIKE ?
                  OR service_line LIKE ? OR client_name LIKE ? OR content LIKE ? OR keywords LIKE ?)
             LIMIT 10'
        );
        $stmt->execute([$like, $like, $like, $like, $like, $like, $like, $like, $like]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = [
                'type'  => 'Case Study',
                'title' => $row['title'],
                'url'   => locale_url('case-studies/' . $row['slug']),
                'desc'  => seo_strip_text($row['results'] ?? $row['challenge'] ?? '', 140),
                'score' => search_match_score($row['title'] . ' ' . ($row['industry'] ?? ''), $query) + 12,
            ];
        }
    } catch (Exception $e) {
    }

    require_once __DIR__ . '/industry-config.php';
    foreach (industry_get_all() as $slug => $page) {
        $highlights = isset($page['highlights']) ? implode(' ', $page['highlights']) : '';
        $text = ($page['name'] ?? '') . ' ' . ($page['headline'] ?? '') . ' ' . ($page['tagline'] ?? '')
            . ' ' . ($page['body'] ?? '') . ' ' . $highlights;
        $score = search_match_score($text, $query);
        if ($score >= 15) {
            $results[] = [
                'type'  => 'Industry',
                'title' => $page['name'] . ' — ' . ($page['headline'] ?? ''),
                'url'   => industry_url($slug),
                'desc'  => $page['tagline'] ?? '',
                'score' => $score + 6,
            ];
        }
    }

    foreach (search_service_pages() as $page) {
        $text = $page['title'] . ' ' . ($page['keywords'] ?? '');
        $score = search_match_score($text, $query);
        if ($score >= 20) {
            $results[] = [
                'type'  => 'Page',
                'title' => $page['title'],
                'url'   => locale_url($page['url']),
                'desc'  => ucfirst($page['keywords'] ?? ''),
                'score' => $score,
            ];
        }
    }

    try {
        $localeCol = $pdo->query("SHOW COLUMNS FROM site_faqs LIKE 'locale'")->fetch();
        $localeSql = $localeCol ? ' AND locale = ?' : '';
        $sql = 'SELECT question, answer, category FROM site_faqs WHERE is_active = 1' . $localeSql
            . ' AND (question LIKE ? OR answer LIKE ?) LIMIT 8';
        $stmt = $pdo->prepare($sql);
        if ($localeCol) {
            $stmt->execute([$locale, $like, $like]);
        } else {
            $stmt->execute([$like, $like]);
        }
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = [
                'type'  => 'FAQ',
                'title' => $row['question'],
                'url'   => locale_url('faqs') . '#' . rawurlencode('faq-' . md5($row['question'])),
                'desc'  => seo_strip_text($row['answer'], 120),
                'score' => search_match_score($row['question'] . ' ' . $row['answer'], $query) + 5,
            ];
        }
    } catch (Exception $e) {
    }

    usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
    return array_slice($results, 0, $limit);
}
