<?php
/**
 * Add slug column to jobs and generate unique slugs for existing rows.
 * Usage: php scripts/migrate-job-slugs.php
 */
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/job-helpers.php';

try {
    $cols = $pdo->query("SHOW COLUMNS FROM jobs LIKE 'slug'")->fetch();
    if (!$cols) {
        $pdo->exec('ALTER TABLE jobs ADD COLUMN slug VARCHAR(120) DEFAULT NULL AFTER title');
        $pdo->exec('ALTER TABLE jobs ADD UNIQUE KEY jobs_slug_unique (slug)');
        echo "Added jobs.slug column.\n";
    } else {
        echo "Column jobs.slug already exists.\n";
    }

    $jobs = $pdo->query('SELECT id, title, location, slug FROM jobs ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($jobs as $job) {
        if (!empty($job['slug'])) {
            continue;
        }
        $base = job_make_slug($job['title'], $job['location'] ?? null, (int) $job['id']);
        $slug = job_ensure_unique_slug($pdo, $base, (int) $job['id']);
        $pdo->prepare('UPDATE jobs SET slug = ? WHERE id = ?')->execute([$slug, $job['id']]);
        echo "Job #{$job['id']} → {$slug}\n";
    }
    echo "Done.\n";
} catch (PDOException $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
