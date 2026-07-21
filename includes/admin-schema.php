<?php
/**
 * Admin DB helpers — widen SEO text columns and fit values to live schema.
 */

function admin_allowed_tables(): array
{
    return ['blogs', 'jobs', 'case_studies'];
}

function admin_assert_table(string $table): void
{
    if (!in_array($table, admin_allowed_tables(), true)) {
        throw new InvalidArgumentException('Unsupported admin table: ' . $table);
    }
}

function admin_column_exists($pdo, string $table, string $column): bool
{
    admin_assert_table($table);

    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $pdo->quote($column));

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

function admin_column_max_chars($pdo, string $table, string $column): ?int
{
    admin_assert_table($table);

    if (!admin_column_exists($pdo, $table, $column)) {
        return null;
    }

    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $pdo->quote($column));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $type = strtolower((string) ($row['Type'] ?? ''));
        if (preg_match('/varchar\((\d+)\)/', $type, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/(?:tiny|medium|long)?text/', $type)) {
            return null;
        }

        return null;
    } catch (PDOException $e) {
        return null;
    }
}

function admin_fit_column_text($pdo, string $table, string $column, mixed $value): mixed
{
    if ($value === null) {
        return null;
    }

    $text = trim((string) $value);
    if ($text === '') {
        return '';
    }

    $max = admin_column_max_chars($pdo, $table, $column);
    if ($max === null || mb_strlen($text) <= $max) {
        return $text;
    }

    if ($max <= 3) {
        return mb_substr($text, 0, $max);
    }

    return rtrim(mb_substr($text, 0, $max - 3)) . '...';
}

/**
 * Widen common SEO/meta columns when the live DB still uses short VARCHAR limits.
 *
 * @return array<string, string> column => action taken
 */
function admin_ensure_seo_text_columns($pdo, string $table): array
{
    admin_assert_table($table);

    $targets = [
        'keywords'   => 1000,
        'tags'       => 1000,
        'meta_desc'  => 500,
        'meta_title' => 255,
    ];

    $actions = [];

    foreach ($targets as $column => $minChars) {
        if (!admin_column_exists($pdo, $table, $column)) {
            continue;
        }

        $current = admin_column_max_chars($pdo, $table, $column);
        if ($current !== null && $current >= $minChars) {
            continue;
        }

        $definition = $minChars >= 1000
            ? 'VARCHAR(1000) DEFAULT NULL'
            : 'VARCHAR(' . $minChars . ') DEFAULT NULL';

        try {
            $pdo->exec("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$definition}");
            $actions[$column] = 'widened to ' . $minChars;
        } catch (PDOException $e) {
            error_log("admin_ensure_seo_text_columns({$table}.{$column}): " . $e->getMessage());
            $actions[$column] = 'failed: ' . $e->getMessage();
        }
    }

    return $actions;
}

/**
 * @param array<string, mixed> $payload
 * @return array<string, mixed>
 */
function admin_fit_row_to_table($pdo, string $table, array $payload, array $columns): array
{
    foreach ($columns as $column) {
        if (!array_key_exists($column, $payload)) {
            continue;
        }
        if (!is_string($payload[$column]) && $payload[$column] !== null) {
            continue;
        }
        $payload[$column] = admin_fit_column_text($pdo, $table, $column, $payload[$column]);
    }

    return $payload;
}
