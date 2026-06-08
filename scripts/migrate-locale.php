<?php
/**
 * Add locale column to dynamic content tables for multilingual support.
 * Usage: php scripts/migrate-locale.php
 */
require_once dirname(__DIR__) . '/db.php';

$tables = [
    'blogs'         => ['after' => 'slug', 'unique' => ['slug', 'locale']],
    'jobs'          => ['after' => 'slug', 'unique' => ['slug', 'locale']],
    'case_studies'  => ['after' => 'slug', 'unique' => ['slug', 'locale']],
    'site_faqs'     => ['after' => 'category', 'unique' => null],
    'testimonials'  => ['after' => 'name', 'unique' => null],
];

foreach ($tables as $table => $cfg) {
    try {
        $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
    } catch (PDOException $e) {
        echo "Skip {$table} (table missing)\n";
        continue;
    }

    $col = $pdo->query("SHOW COLUMNS FROM {$table} LIKE 'locale'")->fetch();
    if (!$col) {
        $after = $cfg['after'];
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN locale VARCHAR(5) NOT NULL DEFAULT 'en' AFTER {$after}");
        $pdo->exec("UPDATE {$table} SET locale = 'en' WHERE locale = '' OR locale IS NULL");
        echo "Added {$table}.locale\n";
    }

    if (!empty($cfg['unique'])) {
        $indexes = $pdo->query("SHOW INDEX FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
        $slugOnly = false;
        $composite = false;
        foreach ($indexes as $idx) {
            if ($idx['Column_name'] === 'slug' && (int) $idx['Non_unique'] === 0) {
                if ($idx['Key_name'] === 'slug' || $idx['Seq_in_index'] == 1) {
                    $slugOnly = $idx['Key_name'];
                }
            }
            if ($idx['Key_name'] === 'uk_' . $table . '_slug_locale') {
                $composite = true;
            }
        }
        if ($slugOnly && !$composite) {
            try {
                $pdo->exec("ALTER TABLE {$table} DROP INDEX {$slugOnly}");
                echo "Dropped old unique on {$table}.slug ({$slugOnly})\n";
            } catch (PDOException $e) {
                try {
                    $pdo->exec("ALTER TABLE {$table} DROP INDEX slug");
                } catch (PDOException $e2) {
                }
            }
        }
        if (!$composite) {
            try {
                $pdo->exec("ALTER TABLE {$table} ADD UNIQUE KEY uk_{$table}_slug_locale (slug, locale)");
                echo "Added unique (slug, locale) on {$table}\n";
            } catch (PDOException $e) {
                echo "Note: unique on {$table}: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "Locale migration complete.\n";
