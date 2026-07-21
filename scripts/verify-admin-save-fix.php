<?php
/**
 * Verify admin schema helpers (no DB required for unit checks).
 * Usage: php scripts/verify-admin-save-fix.php
 */
require_once dirname(__DIR__) . '/includes/admin-schema.php';

$passed = 0;
$failed = 0;

function assert_eq(string $label, mixed $expected, mixed $actual): void
{
    global $passed, $failed;
    if ($expected === $actual) {
        echo "PASS: {$label}\n";
        $passed++;
        return;
    }
    echo "FAIL: {$label}\n";
    echo "  expected: " . var_export($expected, true) . "\n";
    echo "  actual:   " . var_export($actual, true) . "\n";
    $failed++;
}

// --- admin_fit_column_text with mocked PDO ---
class VerifyStmt
{
    public function __construct(private readonly mixed $row) {}

    public function fetch($mode = null): mixed
    {
        return $this->row;
    }
}

class VerifyPdo
{
    public function __construct(private array $columnTypes) {}

    public function query(string $query): VerifyStmt
    {
        if (preg_match("/SHOW COLUMNS FROM `(\w+)` LIKE '(\w+)'/", $query, $m)) {
            $key = $m[1] . '.' . $m[2];
            $type = $this->columnTypes[$key] ?? null;

            return new VerifyStmt($type === null ? false : ['Type' => $type]);
        }

        throw new RuntimeException('Unexpected query: ' . $query);
    }

    public function quote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}

$pdo255 = new VerifyPdo([
    'blogs.keywords' => 'varchar(255)',
    'blogs.meta_title' => 'varchar(255)',
]);
$longKeywords = str_repeat('keyword phrase, ', 40);
$fitted = admin_fit_column_text($pdo255, 'blogs', 'keywords', $longKeywords);
assert_eq('fits long keywords into varchar(255)', 255, mb_strlen((string) $fitted));
assert_eq('fitted keywords end with ellipsis', '...', mb_substr((string) $fitted, -3));

$pdo1000 = new VerifyPdo(['blogs.keywords' => 'varchar(1000)']);
$medium = mb_substr($longKeywords, 0, 500);
assert_eq('leaves medium keywords unchanged on varchar(1000)', $medium, admin_fit_column_text($pdo1000, 'blogs', 'keywords', $medium));

$row = ['meta_title' => str_repeat('x', 300), 'keywords' => 'ok'];
$fittedRow = admin_fit_row_to_table($pdo255, 'blogs', $row, ['meta_title', 'keywords']);
assert_eq('admin_fit_row_to_table truncates meta_title', 255, mb_strlen((string) $fittedRow['meta_title']));
assert_eq('admin_fit_row_to_table keeps short keywords', 'ok', $fittedRow['keywords']);

// seo_suggest_blog_keywords length cap
require_once dirname(__DIR__) . '/includes/seo.php';
$suggested = seo_suggest_blog_keywords([
    'title' => 'How FinTech Sales Growth Works With BTL Activation and Staffing in Tier 2 Cities',
    'category' => 'FinTech',
    'content' => str_repeat('<p>merchant onboarding lending NBFC staffing BTL activation EV infrastructure survey market research distributor network tier 2 tier 3</p>', 20),
    'keywords' => '',
]);
assert_eq('seo_suggest_blog_keywords stays under 980 chars', true, mb_strlen($suggested) <= 980);

echo "\n";
if ($failed > 0) {
    echo "Verification FAILED: {$failed} failure(s), {$passed} passed.\n";
    exit(1);
}

echo "Verification OK: {$passed} checks passed.\n";
exit(0);
