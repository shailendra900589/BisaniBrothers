<?php
/**
 * Create tables for testimonials, newsletter, site FAQs.
 * Usage: php scripts/migrate-site-features.php
 */
require_once dirname(__DIR__) . '/db.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    role_title VARCHAR(120) DEFAULT NULL,
    company VARCHAR(120) DEFAULT NULL,
    quote TEXT NOT NULL,
    service_line VARCHAR(80) DEFAULT NULL,
    rating TINYINT DEFAULT 5,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    status ENUM('active','unsubscribed') DEFAULT 'active',
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS site_faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(80) DEFAULT 'General',
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$faqCount = (int) $pdo->query('SELECT COUNT(*) FROM site_faqs')->fetchColumn();
if ($faqCount === 0) {
    $faqs = [
        ['General', 'What services does Bisani Brothers provide?', 'We provide sales execution, staffing solutions, market research, BTL/ATL activation, lending & collection support, and EV infrastructure rollout across India.'],
        ['General', 'Which industries do you serve?', 'We work with FinTech, BFSI, NBFC, retail, lending, EV, education, and other service-based industries requiring on-ground execution.'],
        ['Staffing', 'Do you provide bulk hiring and field workforce deployment?', 'Yes. We deploy trained field teams for sales, collections, merchant onboarding, and operational roles with structured onboarding and performance tracking.'],
        ['FinTech', 'Can you help with merchant onboarding and QR/POS rollout?', 'Yes. We manage partner-led merchant onboarding, distributor networks, and multi-city rollout for digital payment and lending products.'],
        ['Careers', 'How can I apply for a job?', 'Visit our Careers page to view open roles and apply online with your resume, or email hr@bisanibrother.com mentioning the role.'],
        ['Partners', 'How do I become a growth partner?', 'Visit Partner With Us or Growth Partner pages to register. Our team reviews applications and connects with suitable candidates.'],
        ['General', 'Where are your offices located?', 'Our headquarters is in Lucknow (Indira Nagar). We operate with field teams across multiple states including UP, Bihar, Delhi NCR, and Tamil Nadu.'],
        ['General', 'How quickly can you start a project?', 'Depending on scope and city coverage, we can mobilize teams within days to a few weeks after requirement alignment and onboarding.'],
    ];
    $stmt = $pdo->prepare('INSERT INTO site_faqs (category, question, answer, sort_order) VALUES (?, ?, ?, ?)');
    foreach ($faqs as $i => $f) {
        $stmt->execute([$f[0], $f[1], $f[2], $i + 1]);
    }
    echo "Seeded " . count($faqs) . " FAQs.\n";
}

$testCount = (int) $pdo->query('SELECT COUNT(*) FROM testimonials')->fetchColumn();
if ($testCount === 0) {
    $tests = [
        ['Operations Head', 'FinTech Client', 'Bisani Brothers helped us scale merchant onboarding across Tier 2 cities with disciplined field execution and daily reporting.', 'FinTech', 5],
        ['HR Manager', 'NBFC Partner', 'Their staffing model gave us reliable field sales teams within weeks. Culture fit and retention were noticeably better.', 'Staffing', 5],
        ['Marketing Lead', 'Consumer Brand', 'BTL activations were structured, measurable, and delivered strong on-ground visibility for our product launch.', 'BTL Activation', 5],
    ];
    $stmt = $pdo->prepare('INSERT INTO testimonials (name, role_title, company, quote, service_line, rating, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($tests as $i => $t) {
        $stmt->execute(['Client Partner', $t[0], $t[1], $t[2], $t[3], $t[4], $i + 1]);
    }
    echo "Seeded " . count($tests) . " testimonials.\n";
}

echo "Site features migration complete.\n";
