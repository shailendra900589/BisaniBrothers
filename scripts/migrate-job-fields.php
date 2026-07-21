<?php
/**
 * Extended job posting fields for admin + Google Jobs / SEO.
 * Usage: php scripts/migrate-job-fields.php
 */
require_once dirname(__DIR__) . '/db.php';

$columns = [
    'department'  => "VARCHAR(100) DEFAULT NULL AFTER location",
    'work_mode'   => "VARCHAR(20) NOT NULL DEFAULT 'on-site' AFTER type",
    'vacancies'   => "INT NOT NULL DEFAULT 1 AFTER work_mode",
    'apply_email' => "VARCHAR(255) DEFAULT NULL AFTER experience_months",
    'meta_title'  => "VARCHAR(120) DEFAULT NULL AFTER apply_email",
    'meta_desc'   => "VARCHAR(255) DEFAULT NULL AFTER meta_title",
    'keywords'    => "VARCHAR(1000) DEFAULT NULL AFTER meta_desc",
];

foreach ($columns as $name => $def) {
    $exists = $pdo->query("SHOW COLUMNS FROM jobs LIKE " . $pdo->quote($name))->fetch();
    if (!$exists) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN {$name} {$def}");
        echo "Added jobs.{$name}\n";
    } else {
        echo "Skip jobs.{$name} (exists)\n";
    }
}

require_once dirname(__DIR__) . '/includes/admin-schema.php';
foreach (admin_ensure_seo_text_columns($pdo, 'jobs') as $col => $action) {
    echo "jobs.{$col}: {$action}\n";
}

echo "Job fields migration complete.\n";
