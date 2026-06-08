<?php
/**
 * Add is_orphan column to blogs table.
 * Usage: php scripts/migrate-orphan-blogs.php
 */
require __DIR__ . '/../db.php';

try {
    $cols = $pdo->query("SHOW COLUMNS FROM blogs LIKE 'is_orphan'")->fetch();
    if (!$cols) {
        $pdo->exec('ALTER TABLE blogs ADD COLUMN is_orphan TINYINT(1) NOT NULL DEFAULT 0 AFTER is_published');
        echo "Added blogs.is_orphan column.\n";
    } else {
        echo "Column blogs.is_orphan already exists.\n";
    }
} catch (PDOException $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "Done.\n";
