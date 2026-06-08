<?php
/**
 * Migration: case studies, lead status, industry support.
 * Usage: php scripts/migrate-phase2-features.php
 */
require_once dirname(__DIR__) . '/db.php';

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

$cols = $pdo->query("SHOW COLUMNS FROM enquiries LIKE 'status'")->fetch();
if (!$cols) {
    $pdo->exec("ALTER TABLE enquiries ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'new' AFTER source_page");
    echo "Added enquiries.status\n";
}

$cols2 = $pdo->query("SHOW COLUMNS FROM contact_enquiries LIKE 'status'")->fetch();
if (!$cols2) {
    $pdo->exec("ALTER TABLE contact_enquiries ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'new' AFTER message");
    echo "Added contact_enquiries.status\n";
}

$count = (int) $pdo->query('SELECT COUNT(*) FROM case_studies')->fetchColumn();
if ($count === 0) {
    $pdo->exec("INSERT INTO case_studies (title, slug, client_name, industry, service_line, challenge, approach, results, quote, content, meta_title, meta_desc, is_published) VALUES
    ('Scaling Merchant Onboarding Across Tier 2 Cities', 'scaling-merchant-onboarding-tier-2-cities', 'Leading FinTech Partner', 'FinTech', 'Sales & Growth',
     'A digital payments company needed faster merchant activation across multiple Tier 2 cities without building permanent local offices.',
     'Bisani Brothers deployed structured field teams, distributor partnerships, and daily onboarding targets with centralized reporting.',
     '3x increase in daily merchant activations, 40% reduction in onboarding turnaround time, and consistent quality across 8 cities within 90 days.',
     'Their on-ground execution model helped us scale where internal teams struggled to maintain speed and quality.',
     '<p>This engagement demonstrates how partner-led field execution accelerates FinTech rollout in underserved markets.</p>',
     'FinTech Merchant Onboarding Case Study', 'How Bisani Brothers scaled merchant onboarding across Tier 2 India with structured field teams.', 1),
    ('Bulk Field Hiring for NBFC Collection Operations', 'bulk-field-hiring-nbfc-collection', 'NBFC Operations Partner', 'BFSI / NBFC', 'Staffing Solutions',
     'An NBFC required rapid deployment of collection executives across Uttar Pradesh with training and retention support.',
     'We handled end-to-end hiring, onboarding, compliance documentation, and field deployment with weekly performance reviews.',
     '200+ executives deployed in 6 weeks, 85% retention in first quarter, and measurable improvement in collection efficiency.',
     'BBPL delivered trained field staff faster than any agency we worked with before.',
     '<p>Structured staffing and onboarding enabled consistent collection performance at scale.</p>',
     'NBFC Staffing Case Study India', 'Bulk hiring and field workforce deployment for NBFC collection operations by Bisani Brothers.', 1)");
    echo "Seeded 2 sample case studies.\n";
}

echo "Phase 2 migration complete.\n";
