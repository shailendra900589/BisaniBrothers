<?php
/**
 * Job openings index — for search engine discovery (IndexNow, sitemap, crawlers).
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/seo-config.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/job-helpers.php';

$base = seo_site_url_rtrim();
$pageUrl = $base . '/jobs-index';
$jobs = job_fetch_active($pdo);
$updated = $jobs ? date('c', strtotime($jobs[0]['posted_date'])) : date('c');

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: index, follow');

$itemList = [];
foreach ($jobs as $i => $row) {
    $slug = $row['slug'] ?? job_make_slug($row['title'], $row['location'] ?? null, (int) $row['id']);
    $itemList[] = [
        '@type'    => 'ListItem',
        'position' => $i + 1,
        'url'      => job_post_url($slug, $base),
        'name'     => $row['title'] . ' — ' . ($row['location'] ?? ''),
    ];
}

$schema = [
    '@context'     => 'https://schema.org',
    '@type'        => 'CollectionPage',
    'name'         => 'Job Openings Index',
    'description'  => 'All current job openings at Bisani Brothers Private Limited.',
    'url'          => $pageUrl,
    'dateModified' => $updated,
    'mainEntity'   => [
        '@type'           => 'ItemList',
        'numberOfItems'   => count($jobs),
        'itemListElement' => $itemList,
    ],
];
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Openings Index | <?php echo seo_escape(SEO_SITE_NAME); ?></title>
    <meta name="description" content="Complete index of current job openings at Bisani Brothers — field sales, BDE, collection, and more across India.">
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
    <h1>Job Openings Index</h1>
    <p class="meta">Crawler discovery page · <?php echo count($jobs); ?> opening(s) · Updated <?php echo date('M d, Y'); ?></p>
    <?php if ($jobs): ?>
    <ul>
        <?php foreach ($jobs as $row):
            $slug = $row['slug'] ?? job_make_slug($row['title'], $row['location'] ?? null, (int) $row['id']);
        ?>
        <li>
            <a href="<?php echo seo_escape(job_post_url($slug, $base)); ?>"><?php echo seo_escape($row['title']); ?></a>
            <?php if (!empty($row['location'])): ?>
            <span class="meta"> — <?php echo seo_escape($row['location']); ?> (<?php echo seo_escape($row['type'] ?? 'Full-time'); ?>)</span>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="empty">No open positions at the moment.</p>
    <?php endif; ?>
</body>
</html>
