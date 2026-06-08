<?php
/**
 * Pre-translate all English blogs into every non-English locale (warms disk cache).
 * Usage: php scripts/translate-blogs.php [--locale=hi] [--limit=5]
 */
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/blog-helpers.php';
require_once dirname(__DIR__) . '/includes/blog-translate.php';
require_once dirname(__DIR__) . '/includes/locale.php';

$locales = require dirname(__DIR__) . '/lang/locale-config.php';
$onlyLocale = null;
$limit = null;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--locale=')) {
        $onlyLocale = substr($arg, 9);
    }
    if (str_starts_with($arg, '--limit=')) {
        $limit = (int) substr($arg, 8);
    }
}

$sql = 'SELECT * FROM blogs WHERE ' . blog_sql_public_only();
if (blog_has_locale_column($pdo)) {
    $sql .= " AND locale = 'en'";
}
$sql .= ' ORDER BY id ASC';
if ($limit) {
    $sql .= ' LIMIT ' . max(1, $limit);
}

$blogs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$codes = array_values(array_filter(array_keys($locales), fn($c) => $c !== 'en'));

echo 'Found ' . count($blogs) . " English blog(s).\n";

foreach ($blogs as $blog) {
    $id = (int) $blog['id'];
    echo "Blog #{$id}: {$blog['title']}\n";
    foreach ($codes as $code) {
        if ($onlyLocale !== null && $onlyLocale !== $code) {
            continue;
        }
        echo "  -> {$code}... ";
        blog_localize_post($blog, $code);
        echo "done\n";
    }
}

echo "Blog translation cache warmed.\n";
