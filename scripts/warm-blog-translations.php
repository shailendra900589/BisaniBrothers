<?php
/**
 * Pre-build blog translation caches for all locales (CLI only).
 * Usage: php scripts/warm-blog-translations.php
 */
putenv('BISANI_BLOG_TRANSLATE_LIVE=1');

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/locale.php';
require_once dirname(__DIR__) . '/includes/blog-helpers.php';
require_once dirname(__DIR__) . '/includes/blog-translate.php';

$stmt = $pdo->query('SELECT * FROM blogs WHERE is_published = 1 ORDER BY id ASC');
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$locales = array_values(array_filter(LOCALE_SUPPORTED, fn($c) => $c !== LOCALE_DEFAULT));

echo 'Posts: ' . count($posts) . ', locales: ' . implode(', ', $locales) . PHP_EOL;

foreach ($posts as $post) {
    $id = (int) ($post['id'] ?? 0);
    echo "Blog #{$id} {$post['slug']}\n";
    foreach ($locales as $locale) {
        blog_localize_post($post, $locale, 'summary');
        echo "  {$locale} summary OK\n";
        blog_localize_post($post, $locale, 'full');
        echo "  {$locale} full OK\n";
    }
}

echo "Done.\n";
