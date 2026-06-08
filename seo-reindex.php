<?php
/**
 * Bulk IndexNow submission — CLI or admin only.
 * Web access is blocked by .htaccess; use admin/seo-reindex.php or: php seo-reindex.php
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/seo.php';

$result = seo_run_bulk_reindex($pdo);
echo implode("\n", $result['messages']) . "\n";
