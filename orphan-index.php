<?php
/**
 * Orphan articles index — for search engine discovery only.
 * Not linked in site navigation, blog listing, or related posts.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/seo-config.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/blog-helpers.php';

$base = seo_site_url_rtrim();
$pageUrl = $base . '/orphan-index';
$orphans = blog_fetch_orphan_posts($pdo);
$updated = $orphans ? date('c', strtotime($orphans[0]['created_at'])) : date('c');

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: index, follow');

$itemList = [];
foreach ($orphans as $i => $row) {
    $itemList[] = [
        '@type'    => 'ListItem',
        'position' => $i + 1,
        'url'      => blog_post_url($row['slug'], $base),
        'name'     => $row['title'],
    ];
}

$schema = [
    '@context'        => 'https://schema.org',
    '@type'           => 'CollectionPage',
    'name'            => 'Orphan Articles Index',
    'description'     => 'Index of standalone articles published on Bisani Brothers.',
    'url'             => $pageUrl,
    'dateModified'    => $updated,
    'mainEntity'      => [
        '@type'           => 'ItemList',
        'numberOfItems'   => count($orphans),
        'itemListElement' => $itemList,
    ],
];
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orphan Articles Index | <?php echo seo_escape(SEO_SITE_NAME); ?></title>
    <meta name="description" content="Standalone article index for search engines — Bisani Brothers published content.">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="<?php echo seo_escape($pageUrl); ?>">
    <script type="application/ld+json"><?php echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; color: #1e293b; line-height: 1.6; }
        h1 { font-size: 1.35rem; color: #173978; }
        .meta { color: #64748b; font-size: 0.875rem; margin-bottom: 1.5rem; }
        ul { padding-left: 1.25rem; }
        li { margin-bottom: 0.65rem; }
        a { color: #173978; font-weight: 600; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .empty { color: #64748b; }
    </style>
</head>
<body>
    <h1>Orphan Articles Index</h1>
    <p class="meta">Crawler discovery page · <?php echo count($orphans); ?> article(s) · Updated <?php echo date('M d, Y'); ?></p>
    <?php if ($orphans): ?>
    <ul>
        <?php foreach ($orphans as $row): ?>
        <li>
            <a href="<?php echo seo_escape(blog_post_url($row['slug'], $base)); ?>"><?php echo seo_escape($row['title']); ?></a>
            <?php if (!empty($row['created_at'])): ?>
            <span class="meta"> — <?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="empty">No orphan articles published yet.</p>
    <?php endif; ?>
</body>
</html>
