<?php
$root = dirname(__DIR__);
$cssDir = $root . '/assets/css';
if (!is_dir($cssDir)) {
    mkdir($cssDir, 0755, true);
}

function extract_style_block(string $file): string
{
    $content = file_get_contents($file);
    if (preg_match('/<style[^>]*>(.*?)<\/style>/s', $content, $m)) {
        return trim($m[1]);
    }
    return '';
}

function extract_all_style_blocks(string $file): string
{
    $content = file_get_contents($file);
    preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $content, $matches);
    $blocks = array_map('trim', $matches[1] ?? []);
    return implode("\n\n", array_filter($blocks));
}

// Blog CSS
file_put_contents($cssDir . '/blog.css', extract_style_block($root . '/includes/blog-styles.php') . "\n");

// Main styles.css sections
$styles = [];
$styles[] = "/* Bisani Brothers — Global Site Styles */\n";
$styles[] = extract_style_block($root . '/includes/header.php');
$styles[] = extract_style_block($root . '/includes/footer.php');
$styles[] = extract_style_block($root . '/index.php');
$styles[] = extract_all_style_blocks($root . '/index.php'); // includes 2nd and 3rd blocks - wait extract_style_block only gets first

// Fix index - get all blocks
$indexContent = file_get_contents($root . '/index.php');
preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $indexContent, $indexMatches);
foreach ($indexMatches[1] as $block) {
    $styles[] = trim($block);
}

$styles[] = extract_style_block($root . '/about.php');
$styles[] = extract_style_block($root . '/growth-partner.php');
$styles[] = extract_style_block($root . '/sales-growth.php');
$styles[] = extract_style_block($root . '/btl-atl.php');
$styles[] = extract_style_block($root . '/ev-infrastructure.php');

// Global overflow fix (shared across service pages)
$styles[] = <<<'CSS'
/* Service pages — prevent horizontal scroll */
html, body {
    overflow-x: hidden;
    width: 100%;
    max-width: 100vw;
}
CSS;

$merged = implode("\n\n", array_unique(array_filter($styles)));
file_put_contents($cssDir . '/styles.css', $merged . "\n");

// Admin CSS
$admin = [];
$admin[] = "/* Bisani Brothers — Admin Panel Styles */\n";
$admin[] = "body { font-family: 'Outfit', sans-serif; }\n";
$admin[] = extract_style_block($root . '/admin/blogs.php');
$admin[] = ".nav-active { background: #2fcaf0; color: #173978; font-weight: bold; }";
file_put_contents($cssDir . '/admin.css', implode("\n\n", array_unique(array_filter($admin))) . "\n");

echo "Created:\n";
echo "  blog.css   " . filesize($cssDir . '/blog.css') . " bytes\n";
echo "  styles.css " . filesize($cssDir . '/styles.css') . " bytes\n";
echo "  admin.css  " . filesize($cssDir . '/admin.css') . " bytes\n";
