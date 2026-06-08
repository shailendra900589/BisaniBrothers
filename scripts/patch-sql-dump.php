<?php
/**
 * Patch bisanibrothers_2026.sql with current blog keywords/meta_desc from DB
 * and append SEO updates before COMMIT as fallback.
 */
$sqlFile = dirname(__DIR__) . '/bisanibrothers_2026.sql';
$updatesFile = __DIR__ . '/update-blog-keywords.sql';

require_once dirname(__DIR__) . '/db.php';

if (!is_file($sqlFile)) {
    fwrite(STDERR, "SQL dump not found: {$sqlFile}\n");
    exit(1);
}

$content = file_get_contents($sqlFile);

// --- 1. Replace blogs INSERT section with fresh export from DB ---
$rows = $pdo->query('SELECT * FROM blogs ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);

$insertLines = [];
$insertLines[] = '';
$insertLines[] = '--';
$insertLines[] = '-- Dumping data for table `blogs` (SEO keywords patched ' . date('Y-m-d') . ')';
$insertLines[] = '--';
$insertLines[] = '';

foreach ($rows as $row) {
    $cols = ['id', 'title', 'slug', 'category', 'content', 'image_path', 'meta_title', 'meta_desc', 'keywords', 'tags', 'faq_json', 'created_at', 'is_published'];
    $values = [];
    foreach ($cols as $col) {
        $val = $row[$col];
        if ($val === null) {
            $values[] = 'NULL';
        } elseif (in_array($col, ['id', 'is_published'], true)) {
            $values[] = (string) (int) $val;
        } else {
            $values[] = "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $val) . "'";
        }
    }
    $insertLines[] = 'INSERT INTO `blogs` (`' . implode('`, `', $cols) . '`) VALUES (' . implode(', ', $values) . ');';
}

$newBlogSection = implode("\n", $insertLines) . "\n";

// Match from first blogs INSERT through last blogs INSERT line (before next table section)
$pattern = '/(--\r?\n-- Dumping data for table `blogs`[^\r\n]*\r?\n--\r?\n)(.*?)(\r?\n--\r?\n-- Table structure for table `categories`)/s';
if (!preg_match($pattern, $content)) {
    fwrite(STDERR, "Could not locate blogs section in SQL dump.\n");
    exit(1);
}

$content = preg_replace($pattern, '$1' . $newBlogSection . '$3', $content, 1);

// --- 2. Ensure SEO UPDATE block exists before COMMIT (idempotent re-import safety) ---
$updateBlock = "\n--\n-- SEO: blog keywords & meta descriptions\n--\n";
$updateBlock .= file_get_contents($updatesFile);
$updateBlock .= "\n";

$marker = '-- SEO: blog keywords & meta descriptions';
if (strpos($content, $marker) === false) {
    $content = str_replace("\nCOMMIT;\n", "\n" . $updateBlock . "COMMIT;\n", $content);
}

file_put_contents($sqlFile, $content);

echo 'Patched ' . $sqlFile . ' with ' . count($rows) . " blog rows.\n";
