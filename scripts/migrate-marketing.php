<?php
/**
 * Marketing campaigns tables.
 * Usage: php scripts/migrate-marketing.php
 */
require_once dirname(__DIR__) . '/db.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body_html MEDIUMTEXT NOT NULL,
    template_key VARCHAR(50) DEFAULT 'manual',
    recipient_mode ENUM('all','selected') NOT NULL DEFAULT 'all',
    recipient_emails TEXT,
    total_recipients INT DEFAULT 0,
    sent_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    status ENUM('draft','sending','sent','failed') DEFAULT 'draft',
    created_by VARCHAR(100) DEFAULT NULL,
    sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS marketing_campaign_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    status ENUM('sent','failed') NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_campaign (campaign_id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

try {
    $pdo->exec("ALTER TABLE marketing_campaigns MODIFY recipient_mode VARCHAR(20) NOT NULL DEFAULT 'all'");
} catch (PDOException $e) {
    // Column may already be VARCHAR
}

echo "Marketing migration complete.\n";
