<?php
/**
 * Pre-build blog translation caches for all locales (CLI / deploy).
 * Usage: php scripts/warm-blog-translations.php [--force] [--post=SLUG]
 */
$force = in_array('--force', $argv ?? [], true);
$postSlug = null;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--post=')) {
        $postSlug = substr($arg, 7);
    }
}

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/locale.php';
require_once dirname(__DIR__) . '/includes/blog-helpers.php';
require_once dirname(__DIR__) . '/includes/blog-translate.php';

$sql = 'SELECT * FROM blogs WHERE is_published = 1';
$params = [];
if ($postSlug !== null && $postSlug !== '') {
    $sql .= ' AND slug = ?';
    $params[] = $postSlug;
}
$sql .= ' ORDER BY id ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$locales = locale_non_default_codes();

echo 'Posts: ' . count($posts) . ', locales: ' . implode(', ', $locales) . PHP_EOL;

$built = 0;
$skipped = 0;

foreach ($posts as $post) {
    $id = (int) ($post['id'] ?? 0);
    echo "Blog #{$id} {$post['slug']}\n";

    foreach ($locales as $locale) {
        foreach (['summary', 'full'] as $depth) {
            $cacheKey = $depth === 'full' ? 'full' : 'summary';
            $localeKey = $locale . '-' . $cacheKey;
            $sourceHash = blog_translate_source_hash($post, $depth) . '|' . $cacheKey;

            if (!$force && blog_translate_cache_get($id, $localeKey, $sourceHash) !== null) {
                $skipped++;
                echo "  {$locale} {$depth} cached\n";
                continue;
            }

            $ok = blog_warm_post_translation($post, $locale, $depth);
            if ($ok) {
                $built++;
                echo "  {$locale} {$depth} OK\n";
            } else {
                echo "  {$locale} {$depth} FAILED\n";
            }
        }
    }
}

$categories = [];
foreach ($posts as $post) {
    $cat = trim((string) ($post['category'] ?? ''));
    if ($cat !== '') {
        $categories[strtolower($cat)] = $cat;
    }
}

foreach ($locales as $locale) {
    foreach ($categories as $category) {
        $translated = blog_translate_run_live(static fn(): string => blog_translate_text($category, $locale));
        blog_translate_category_cache_store($locale, $category, $translated);
    }
    echo "Categories warmed for {$locale}\n";
}

echo "Done. built={$built} skipped={$skipped}\n";
