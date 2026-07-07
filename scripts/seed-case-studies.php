<?php
/**
 * Seed case studies into the database (idempotent — skips existing slugs).
 * Usage: php scripts/seed-case-studies.php
 */
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/case-studies-demo.php';
require_once dirname(__DIR__) . '/includes/case-study-helpers.php';
require_once dirname(__DIR__) . '/includes/seo.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS case_studies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    client_name VARCHAR(120) DEFAULT NULL,
    industry VARCHAR(80) DEFAULT NULL,
    service_line VARCHAR(80) DEFAULT NULL,
    challenge TEXT DEFAULT NULL,
    approach TEXT DEFAULT NULL,
    results TEXT DEFAULT NULL,
    quote TEXT DEFAULT NULL,
    content TEXT DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    meta_title VARCHAR(120) DEFAULT NULL,
    meta_desc VARCHAR(200) DEFAULT NULL,
    keywords VARCHAR(255) DEFAULT NULL,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$localeCol = $pdo->query("SHOW COLUMNS FROM case_studies LIKE 'locale'")->fetch();
$check = $pdo->prepare('SELECT id FROM case_studies WHERE slug = ? LIMIT 1');

$insertCols = 'title, slug, client_name, industry, service_line, challenge, approach, results, quote, content, image_path, meta_title, meta_desc, keywords, is_published';
$placeholders = '?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?';
if ($localeCol) {
    $insertCols .= ', locale';
    $placeholders .= ', ?';
}
$insert = $pdo->prepare("INSERT INTO case_studies ({$insertCols}) VALUES ({$placeholders})");

$created = 0;
foreach (case_study_demo_entries() as $cs) {
    $check->execute([$cs['slug']]);
    if ($check->fetchColumn()) {
        echo "Skip (exists): {$cs['slug']}\n";
        continue;
    }

    $params = [
        $cs['title'],
        $cs['slug'],
        $cs['client_name'],
        $cs['industry'],
        $cs['service_line'],
        $cs['challenge'],
        $cs['approach'],
        $cs['results'],
        $cs['quote'],
        $cs['content'],
        $cs['image_path'],
        $cs['meta_title'],
        $cs['meta_desc'],
        $cs['keywords'],
        (int) $cs['is_published'],
    ];
    if ($localeCol) {
        $params[] = $cs['locale'] ?? 'en';
    }

    $insert->execute($params);
    $created++;
    echo "Created: {$cs['slug']}\n";
}

if ($created > 0) {
    seo_ping_after_case_study_change($pdo);
}

echo "Done. {$created} new case study/studies.\n";
