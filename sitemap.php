<?php
ob_start();
error_reporting(0);
header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/seo-config.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/job-helpers.php';
require_once __DIR__ . '/includes/case-study-helpers.php';
require_once __DIR__ . '/includes/industry-config.php';

$base = seo_site_url_rtrim();
$today = date('c');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php
foreach (SEO_STATIC_PAGES as $path => $meta) {
    $url = ($path === '/') ? $base . '/' : $base . $path;
    ?>
    <url>
        <loc><?php echo htmlspecialchars($url, ENT_XML1); ?></loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq><?php echo $meta['changefreq']; ?></changefreq>
        <priority><?php echo $meta['priority']; ?></priority>
    </url>
    <?php
}

try {
    $stmt = $pdo->query("SELECT slug, title, image_path, created_at FROM blogs WHERE is_published = 1 ORDER BY created_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $url = $base . '/' . rawurlencode($row['slug']);
        $date = date('c', strtotime($row['created_at']));
        ?>
    <url>
        <loc><?php echo htmlspecialchars($url, ENT_XML1); ?></loc>
        <lastmod><?php echo $date; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.70</priority>
        <?php if (!empty($row['image_path'])): ?>
        <image:image>
            <image:loc><?php echo htmlspecialchars(seo_absolute_image($row['image_path'], seo_site_url()), ENT_XML1); ?></image:loc>
            <image:title><?php echo htmlspecialchars($row['title'], ENT_XML1); ?></image:title>
        </image:image>
        <?php endif; ?>
    </url>
        <?php
    }
} catch (Exception $e) {
    try {
        $stmt = $pdo->query("SELECT slug, title, image_path, created_at FROM blogs ORDER BY created_at DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $url = $base . '/' . rawurlencode($row['slug']);
            $date = date('c', strtotime($row['created_at']));
            ?>
    <url>
        <loc><?php echo htmlspecialchars($url, ENT_XML1); ?></loc>
        <lastmod><?php echo $date; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.70</priority>
    </url>
            <?php
        }
    } catch (Exception $e2) {
    }
}

try {
    $stmt = $pdo->query("SELECT slug, title, location, posted_date FROM jobs WHERE status = 1 ORDER BY posted_date DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $slug = $row['slug'] ?? job_make_slug($row['title'], $row['location'] ?? null);
        $url = job_post_url($slug, $base);
        $date = !empty($row['posted_date']) ? date('c', strtotime($row['posted_date'])) : $today;
        ?>
    <url>
        <loc><?php echo htmlspecialchars($url, ENT_XML1); ?></loc>
        <lastmod><?php echo $date; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.80</priority>
    </url>
        <?php
    }
} catch (Exception $e) {
}

try {
    $stmt = $pdo->query('SELECT slug, created_at FROM case_studies WHERE is_published = 1 ORDER BY created_at DESC');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $url = case_study_post_url($row['slug'], $base);
        $date = date('c', strtotime($row['created_at']));
        ?>
    <url>
        <loc><?php echo htmlspecialchars($url, ENT_XML1); ?></loc>
        <lastmod><?php echo $date; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.75</priority>
    </url>
        <?php
    }
} catch (Exception $e) {
}

foreach (array_keys(industry_get_all()) as $indSlug) {
    $url = $base . '/industries/' . rawurlencode($indSlug);
    ?>
    <url>
        <loc><?php echo htmlspecialchars($url, ENT_XML1); ?></loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.78</priority>
    </url>
    <?php
}
?>
</urlset>
<?php
ob_end_flush();
