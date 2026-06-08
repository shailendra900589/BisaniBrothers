<?php
header('Content-Type: application/rss+xml; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/seo-config.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/blog-helpers.php';
require_once __DIR__ . '/includes/job-helpers.php';

$base = seo_site_url_rtrim();
$site = SEO_SITE_NAME;
$now = date('r');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title><?php echo htmlspecialchars($site); ?> — Blog &amp; Jobs Feed</title>
        <link><?php echo htmlspecialchars($base); ?>/</link>
        <description>Latest articles, insights, and job openings from Bisani Brothers Pvt. Ltd.</description>
        <language>en-in</language>
        <lastBuildDate><?php echo $now; ?></lastBuildDate>
        <atom:link href="<?php echo htmlspecialchars($base); ?>/rss.xml" rel="self" type="application/rss+xml"/>
        <image>
            <url><?php echo htmlspecialchars($base); ?>/assets/images/logos.png</url>
            <title><?php echo htmlspecialchars($site); ?></title>
            <link><?php echo htmlspecialchars($base); ?>/</link>
        </image>
<?php
try {
    $blogs = $pdo->query("SELECT title, slug, content, meta_desc, created_at, is_orphan FROM blogs WHERE is_published = 1 ORDER BY created_at DESC LIMIT 50");
    while ($row = $blogs->fetch(PDO::FETCH_ASSOC)) {
        $link = $base . '/' . rawurlencode($row['slug']);
        $desc = !empty($row['meta_desc']) ? $row['meta_desc'] : seo_strip_text($row['content'], 300);
        $pub = date('r', strtotime($row['created_at']));
        $category = !empty($row['is_orphan']) ? 'Orphan Article' : 'Blog';
        ?>
        <item>
            <title><?php echo htmlspecialchars($row['title']); ?></title>
            <link><?php echo htmlspecialchars($link); ?></link>
            <guid isPermaLink="true"><?php echo htmlspecialchars($link); ?></guid>
            <pubDate><?php echo $pub; ?></pubDate>
            <description><?php echo htmlspecialchars($desc); ?></description>
            <category><?php echo htmlspecialchars($category); ?></category>
        </item>
        <?php
    }
} catch (Exception $e) {
    try {
        $blogs = $pdo->query("SELECT title, slug, content, meta_desc, created_at FROM blogs ORDER BY created_at DESC LIMIT 50");
        while ($row = $blogs->fetch(PDO::FETCH_ASSOC)) {
            $link = $base . '/' . rawurlencode($row['slug']);
            $desc = !empty($row['meta_desc']) ? $row['meta_desc'] : seo_strip_text($row['content'], 300);
            $pub = date('r', strtotime($row['created_at']));
            ?>
        <item>
            <title><?php echo htmlspecialchars($row['title']); ?></title>
            <link><?php echo htmlspecialchars($link); ?></link>
            <guid isPermaLink="true"><?php echo htmlspecialchars($link); ?></guid>
            <pubDate><?php echo $pub; ?></pubDate>
            <description><?php echo htmlspecialchars($desc); ?></description>
            <category>Blog</category>
        </item>
            <?php
        }
    } catch (Exception $e2) {
    }
}

try {
    $jobs = $pdo->query("SELECT slug, title, location, type, description, posted_date FROM jobs WHERE status = 1 ORDER BY posted_date DESC");
    while ($row = $jobs->fetch(PDO::FETCH_ASSOC)) {
        $slug = $row['slug'] ?? job_make_slug($row['title'], $row['location'] ?? null);
        $link = job_post_url($slug, $base);
        $title = $row['title'] . ' — ' . $row['location'] . ' (' . $row['type'] . ')';
        $desc = seo_strip_text($row['description'] ?? '', 300);
        $pub = date('r', strtotime($row['posted_date']));
        ?>
        <item>
            <title><?php echo htmlspecialchars($title); ?></title>
            <link><?php echo htmlspecialchars($link); ?></link>
            <guid isPermaLink="true"><?php echo htmlspecialchars($link); ?></guid>
            <pubDate><?php echo $pub; ?></pubDate>
            <description><?php echo htmlspecialchars($desc); ?></description>
            <category>Job Opening</category>
        </item>
        <?php
    }
} catch (Exception $e) {
}
?>
    </channel>
</rss>
