<?php
header('Content-Type: text/plain; charset=UTF-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/seo-config.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/blog-helpers.php';
require_once __DIR__ . '/includes/job-helpers.php';
require_once __DIR__ . '/includes/case-study-helpers.php';
require_once __DIR__ . '/includes/industry-config.php';

$base = seo_site_url_rtrim();
$site = SEO_SITE_NAME;

echo "# {$site} - LLM Context & Sitemap\n";
echo "> We support businesses with structured teams, disciplined processes, and on-ground execution that delivers consistent outcomes.\n\n";

echo "## Core Pages & Services\n";
$pageTitles = [
    '/'                       => 'Home',
    '/about'                  => 'About Us',
    '/why-work-with-us'       => 'Why Work With Us',
    '/sales-growth'           => 'Sales & Growth Solutions',
    '/survey-market-research' => 'Survey & Market Research',
    '/staffing-solutions'     => 'Staffing Solutions',
    '/btl-atl'                => 'BTL & ATL Activation',
    '/lending-collection'     => 'Lending & Collection Solutions',
    '/ev-infrastructure'      => 'EV Infrastructure Rollout Support',
    '/partner-with-us'        => 'Partner With Us',
    '/growth-partner'         => 'Become a Growth Partner',
    '/careers'                => 'Careers & Job Openings',
    '/blog'                   => 'Knowledge Hub & Blog',
    '/contact'                => 'Contact Us',
    '/case-studies'            => 'Client Success Stories',
    '/industries'              => 'Industries We Serve',
    '/faqs'                    => 'FAQs',
];

foreach ($pageTitles as $path => $title) {
    $url = ($path === '/') ? $base . '/' : $base . $path;
    echo "- [{$title}]({$url})\n";
}

echo "\n## Current Job Openings\n";
try {
    $jobs = $pdo->query("SELECT slug, id, title, location, type, posted_date FROM jobs WHERE status = 1 ORDER BY posted_date DESC")->fetchAll(PDO::FETCH_ASSOC);
    if ($jobs) {
        foreach ($jobs as $job) {
            $title = strip_tags(trim($job['title']));
            $loc = strip_tags(trim($job['location']));
            $type = strip_tags(trim($job['type']));
            $date = date('M d, Y', strtotime($job['posted_date']));
            $slug = $job['slug'] ?? job_make_slug($job['title'], $job['location'] ?? null, (int) $job['id']);
            echo "- [{$title} - {$loc} ({$type})](" . job_post_url($slug, $base) . ") (Posted: {$date})\n";
        }
    } else {
        echo "No open positions at the moment.\n";
    }
} catch (PDOException $e) {
    echo "Jobs listing temporarily unavailable.\n";
}

echo "\n## Case Studies (Client Success Stories)\n";
try {
    $cases = case_study_fetch_published($pdo);
    if ($cases) {
        foreach ($cases as $cs) {
            $title = strip_tags(trim($cs['title']));
            $ind = strip_tags(trim($cs['industry'] ?? ''));
            $svc = strip_tags(trim($cs['service_line'] ?? ''));
            $date = date('M d, Y', strtotime($cs['created_at']));
            $meta = trim($ind . ($svc ? " · {$svc}" : ''));
            echo "- [{$title}](" . case_study_post_url($cs['slug'], $base) . ")";
            echo $meta !== '' ? " ({$meta} · {$date})" : " ({$date})";
            echo "\n";
        }
    } else {
        echo "No case studies published yet.\n";
    }
} catch (PDOException $e) {
    echo "Case studies listing temporarily unavailable.\n";
}

echo "\n## Industries\n";
foreach (industry_get_all() as $slug => $ind) {
    $name = strip_tags(trim($ind['name']));
    $tagline = strip_tags(trim($ind['tagline'] ?? ''));
    echo "- [{$name} — {$ind['headline']}]({$base}/" . industry_url($slug) . ")\n";
    if ($tagline !== '') {
        echo "  {$tagline}\n";
    }
}

echo "\n## Discovery Feeds\n";
echo "- [XML Sitemap]({$base}/sitemap.xml)\n";
echo "- [RSS Feed]({$base}/rss.xml)\n";
echo "- [OpenSearch]({$base}/opensearch.xml)\n";
echo "- [Orphan Articles Index]({$base}/orphan-index)\n";
echo "- [Job Openings Index]({$base}/jobs-index)\n";
echo "- [FAQs]({$base}/faqs)\n";
echo "- [Case Studies]({$base}/case-studies)\n";
echo "- [Industries]({$base}/industries)\n";

echo "\n## Orphan Articles (direct URL only — not on blog listing)\n";
try {
    $orphans = blog_fetch_orphan_posts($pdo);
    if ($orphans) {
        foreach ($orphans as $blog) {
            $title = strip_tags(trim($blog['title']));
            $date = date('M d, Y', strtotime($blog['created_at']));
            echo "- [{$title}]({$base}/" . rawurlencode($blog['slug']) . ") (Orphan · {$date})\n";
        }
    } else {
        echo "No orphan articles.\n";
    }
} catch (PDOException $e) {
    echo "Orphan listing unavailable.\n";
}

echo "\n## Published Blogs & Insights\n";
try {
    $blogs = $pdo->query('SELECT title, slug, created_at FROM blogs WHERE ' . blog_sql_public_only() . ' ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
    if (!$blogs) {
        $blogs = $pdo->query("SELECT title, slug, created_at FROM blogs ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
    if ($blogs) {
        foreach ($blogs as $blog) {
            $title = strip_tags(trim($blog['title']));
            $slug = strip_tags(trim($blog['slug']));
            $date = date('M d, Y', strtotime($blog['created_at']));
            echo "- [{$title}]({$base}/{$slug}) (Published: {$date})\n";
        }
    } else {
        echo "No published blogs available at the moment.\n";
    }
} catch (PDOException $e) {
    echo "Blog listing temporarily unavailable.\n";
}

echo "\n---\n";
echo "End of sitemap. Generated dynamically on: " . date('Y-m-d H:i:s') . "\n";
