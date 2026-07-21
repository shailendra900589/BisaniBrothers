<?php
/**
 * Widen SEO text columns (keywords, tags, meta_desc, meta_title) on blogs, jobs, case_studies.
 * Usage: php scripts/migrate-seo-text-columns.php
 */
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/admin-schema.php';

$tables = ['blogs', 'jobs', 'case_studies'];

foreach ($tables as $table) {
    try {
        $pdo->query('SELECT 1 FROM `' . $table . '` LIMIT 1');
    } catch (PDOException $e) {
        echo "Skip {$table} (table missing)\n";
        continue;
    }

    $actions = admin_ensure_seo_text_columns($pdo, $table);
    if ($actions === []) {
        echo "{$table}: all SEO columns already wide enough\n";
        continue;
    }

    foreach ($actions as $column => $action) {
        echo "{$table}.{$column}: {$action}\n";
    }
}

echo "SEO text column migration complete.\n";
