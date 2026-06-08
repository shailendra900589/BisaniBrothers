<?php
/**
 * Refresh job listing SEO signals (IndexNow + Bing sitemap ping).
 * JobPosting validThrough rolls forward automatically on each page view.
 *
 * Cron (weekly): php scripts/refresh-job-seo.php
 * Force now:      php scripts/refresh-job-seo.php --force
 */
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/job-helpers.php';

$force = in_array('--force', $argv ?? [], true);

if ($force) {
    $result = job_refresh_seo_signals($pdo);
    $line = 'Forced refresh: ' . $result['job_count'] . ' job(s), ' . $result['url_count'] . ' URL(s) at ' . $result['refreshed_at'];
} else {
    $result = job_maybe_refresh_seo_signals($pdo);
    if ($result === null) {
        $last = job_seo_last_refresh();
        $line = 'Skipped (recent refresh). Last: ' . ($last['refreshed_at'] ?? 'never');
    } else {
        $line = 'Refreshed: ' . $result['job_count'] . ' job(s), ' . $result['url_count'] . ' URL(s) at ' . $result['refreshed_at'];
    }
}

if (php_sapi_name() === 'cli') {
    echo $line . PHP_EOL;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $line . PHP_EOL;
}
