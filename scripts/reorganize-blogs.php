<?php
/**
 * One-time: normalize all blog HTML content in the database.
 * Usage: php scripts/reorganize-blogs.php
 */
require __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/blog-helpers.php';

$blogs = $pdo->query('SELECT id, title, content FROM blogs ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$updated = 0;

foreach ($blogs as $blog) {
    $normalized = blog_normalize_content($blog['content'] ?? '');
    if ($normalized === ($blog['content'] ?? '')) {
        echo "Skip ID {$blog['id']}: {$blog['title']}\n";
        continue;
    }
    $stmt = $pdo->prepare('UPDATE blogs SET content = ? WHERE id = ?');
    $stmt->execute([$normalized, $blog['id']]);
    $updated++;
    echo "Updated ID {$blog['id']}: {$blog['title']}\n";
}

echo "\nDone. {$updated} of " . count($blogs) . " posts reorganized.\n";
