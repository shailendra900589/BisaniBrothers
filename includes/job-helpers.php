<?php

function job_has_column(PDO $pdo, string $column): bool
{
    if (!isset($GLOBALS['_job_col_cache']) || !is_array($GLOBALS['_job_col_cache'])) {
        $GLOBALS['_job_col_cache'] = [];
    }
    $cache = &$GLOBALS['_job_col_cache'];
    if (array_key_exists($column, $cache)) {
        return $cache[$column];
    }
    try {
        $cache[$column] = (bool) $pdo->query("SHOW COLUMNS FROM jobs LIKE " . $pdo->quote($column))->fetch();
    } catch (PDOException $e) {
        $cache[$column] = false;
    }

    return $cache[$column];
}

function job_reset_column_cache(): void
{
    unset($GLOBALS['_job_col_cache']);
}

function job_work_mode_label(?string $mode): string
{
    return match (strtolower((string) $mode)) {
        'remote'  => 'Remote',
        'hybrid'  => 'Hybrid',
        default   => 'On-site',
    };
}

function job_schema_job_location_type(?string $mode): ?string
{
    return strtolower((string) $mode) === 'remote' ? 'TELECOMMUTE' : null;
}

function job_education_options(): array
{
    return [
        'noRequirements'               => 'No Minimum Requirement',
        'highSchool'                   => 'High School',
        'professionalCertificate'      => 'Professional Certificate',
        'associateDegree'              => 'Associate Degree',
        'bachelorDegree'               => "Bachelor's Degree",
        'postGraduateDegree'           => "Master's / Post Graduate",
    ];
}

function job_normalize_education(?string $value): string
{
    $value = (string) $value;
    $map = [
        'HighSchool'        => 'highSchool',
        'highschool'        => 'highSchool',
        'BachelorDegree'    => 'bachelorDegree',
        'bachelordegree'    => 'bachelorDegree',
        'PostGraduateDegree'=> 'postGraduateDegree',
    ];
    return $map[$value] ?? ($value !== '' ? $value : 'highSchool');
}

function job_make_slug(string $title, ?string $location = null, ?int $id = null): string
{
    $base = trim($title);
    if ($location !== null && trim($location) !== '') {
        $base .= ' ' . trim($location);
    }
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $base), '-'));
    if (strlen($slug) > 90) {
        $slug = rtrim(substr($slug, 0, 90), '-');
    }
    return $slug !== '' ? $slug : 'job-' . ($id ?? time());
}

function job_ensure_unique_slug(PDO $pdo, string $slug, ?int $excludeId = null, ?string $locale = null): string
{
    require_once __DIR__ . '/locale.php';
    $locale = $locale ?? locale_current();
    $hasLocale = job_has_locale_column($pdo);
    $candidate = $slug;
    $n = 2;
    while (true) {
        if ($excludeId) {
            if ($hasLocale) {
                $stmt = $pdo->prepare('SELECT id FROM jobs WHERE slug = ? AND locale = ? AND id != ? LIMIT 1');
                $stmt->execute([$candidate, $locale, $excludeId]);
            } else {
                $stmt = $pdo->prepare('SELECT id FROM jobs WHERE slug = ? AND id != ? LIMIT 1');
                $stmt->execute([$candidate, $excludeId]);
            }
        } else {
            if ($hasLocale) {
                $stmt = $pdo->prepare('SELECT id FROM jobs WHERE slug = ? AND locale = ? LIMIT 1');
                $stmt->execute([$candidate, $locale]);
            } else {
                $stmt = $pdo->prepare('SELECT id FROM jobs WHERE slug = ? LIMIT 1');
                $stmt->execute([$candidate]);
            }
        }
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $candidate = $slug . '-' . $n;
        $n++;
    }
}

function job_has_locale_column(PDO $pdo): bool
{
    return job_has_column($pdo, 'locale');
}

/**
 * @return string[]
 */
function job_admin_list_columns(PDO $pdo): array
{
    $cols = ['id', 'title', 'slug', 'location', 'type', 'status', 'posted_date'];
    foreach (['department', 'work_mode'] as $optional) {
        if (job_has_column($pdo, $optional)) {
            $cols[] = $optional;
        }
    }

    return $cols;
}

function job_admin_list_sql(PDO $pdo): string
{
    return 'SELECT ' . implode(', ', job_admin_list_columns($pdo)) . ' FROM jobs ORDER BY id DESC';
}

function job_admin_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $alters = [
        'department' => "ALTER TABLE jobs ADD COLUMN department VARCHAR(100) DEFAULT NULL",
        'work_mode'  => "ALTER TABLE jobs ADD COLUMN work_mode VARCHAR(20) NOT NULL DEFAULT 'on-site'",
        'vacancies'  => "ALTER TABLE jobs ADD COLUMN vacancies INT NOT NULL DEFAULT 1",
        'apply_email'=> "ALTER TABLE jobs ADD COLUMN apply_email VARCHAR(255) DEFAULT NULL",
        'meta_title' => "ALTER TABLE jobs ADD COLUMN meta_title VARCHAR(120) DEFAULT NULL",
        'meta_desc'  => "ALTER TABLE jobs ADD COLUMN meta_desc VARCHAR(255) DEFAULT NULL",
        'keywords'   => "ALTER TABLE jobs ADD COLUMN keywords VARCHAR(255) DEFAULT NULL",
        'locale'     => "ALTER TABLE jobs ADD COLUMN locale VARCHAR(5) NOT NULL DEFAULT 'en'",
    ];

    foreach ($alters as $col => $sql) {
        if (job_has_column($pdo, $col)) {
            continue;
        }
        try {
            $pdo->exec($sql);
            job_reset_column_cache();
        } catch (PDOException $e) {
            error_log('job_admin_ensure_schema(' . $col . '): ' . $e->getMessage());
        }
    }
}

/**
 * @param array<string, mixed> $data
 */
function job_admin_save_post(PDO $pdo, ?int $id, array $data): int
{
    job_admin_ensure_schema($pdo);

    $payload = [
        'title'             => (string) ($data['title'] ?? ''),
        'slug'              => (string) ($data['slug'] ?? ''),
        'location'          => (string) ($data['location'] ?? ''),
        'department'        => ($data['department'] ?? '') !== '' ? (string) $data['department'] : null,
        'type'              => (string) ($data['type'] ?? 'Full-time'),
        'work_mode'         => (string) ($data['work_mode'] ?? 'on-site'),
        'vacancies'         => max(1, (int) ($data['vacancies'] ?? 1)),
        'description'       => (string) ($data['description'] ?? ''),
        'min_salary'        => $data['min_salary'] ?? null,
        'max_salary'        => $data['max_salary'] ?? null,
        'education'         => (string) ($data['education'] ?? 'highSchool'),
        'experience_months' => (int) ($data['experience_months'] ?? 0),
        'apply_email'       => ($data['apply_email'] ?? '') !== '' ? (string) $data['apply_email'] : null,
        'meta_title'        => ($data['meta_title'] ?? '') !== '' ? (string) $data['meta_title'] : null,
        'meta_desc'         => ($data['meta_desc'] ?? '') !== '' ? (string) $data['meta_desc'] : null,
        'keywords'          => ($data['keywords'] ?? '') !== '' ? (string) $data['keywords'] : null,
        'status'            => !empty($data['status']) ? 1 : 0,
        'posted_date'       => (string) ($data['posted_date'] ?? date('Y-m-d')),
        'locale'            => (string) ($data['locale'] ?? 'en'),
    ];

    $coreCols = ['title', 'slug', 'location', 'type', 'description', 'status', 'posted_date'];
    $optionalCols = [
        'department', 'work_mode', 'vacancies', 'min_salary', 'max_salary',
        'education', 'experience_months', 'apply_email', 'meta_title', 'meta_desc', 'keywords', 'locale',
    ];

    $cols = $coreCols;
    foreach ($optionalCols as $col) {
        if (job_has_column($pdo, $col)) {
            $cols[] = $col;
        }
    }

    $values = [];
    foreach ($cols as $col) {
        $values[] = $payload[$col];
    }

    if ($id) {
        $assignments = implode('=?, ', $cols) . '=?';
        $stmt = $pdo->prepare("UPDATE jobs SET {$assignments} WHERE id=?");
        $values[] = $id;
        $stmt->execute($values);

        return $id;
    }

    $placeholders = implode(', ', array_fill(0, count($cols), '?'));
    $colList = implode(', ', $cols);
    $stmt = $pdo->prepare("INSERT INTO jobs ({$colList}) VALUES ({$placeholders})");
    $stmt->execute($values);

    return (int) $pdo->lastInsertId();
}

function job_sql_locale(string $alias = '', ?string $locale = null): string
{
    require_once __DIR__ . '/locale.php';
    $locale = $locale ?? locale_current();
    $p = $alias !== '' ? $alias . '.' : '';
    return "{$p}locale = '" . str_replace("'", "''", $locale) . "'";
}

function job_post_url(?string $slug, ?string $base = null, ?string $locale = null): string
{
    $slug = trim((string) ($slug ?? ''));
    if ($base === null) {
        require_once __DIR__ . '/seo.php';
        $base = seo_site_url_rtrim();
    }
    if ($slug === '') {
        return rtrim($base, '/') . '/careers';
    }
    require_once __DIR__ . '/locale.php';

    return seo_locale_absolute('jobs/' . rawurlencode($slug), $base, $locale);
}

function job_fetch_by_slug(PDO $pdo, string $slug, ?string $locale = null): ?array
{
    require_once __DIR__ . '/locale.php';
    $locale = $locale ?? locale_current();
    if (job_has_locale_column($pdo)) {
        $stmt = $pdo->prepare('SELECT * FROM jobs WHERE slug = ? AND locale = ? AND status = 1 LIMIT 1');
        $stmt->execute([$slug, $locale]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$job && $locale !== LOCALE_DEFAULT) {
            $stmt->execute([$slug, LOCALE_DEFAULT]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($job) {
                $job['_locale_fallback'] = true;
            }
        }
        return $job ?: null;
    }
    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE slug = ? AND status = 1 LIMIT 1');
    $stmt->execute([$slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function job_sql_active(string $alias = ''): string
{
    $p = $alias !== '' ? $alias . '.' : '';
    return "{$p}status = 1";
}

function job_employment_type(?string $type): string
{
    $type = strtolower((string) $type);
    if (str_contains($type, 'part')) {
        return 'PART_TIME';
    }
    if (str_contains($type, 'contract')) {
        return 'CONTRACTOR';
    }
    if (str_contains($type, 'intern')) {
        return 'INTERN';
    }
    return 'FULL_TIME';
}

function job_fetch_active(PDO $pdo): array
{
    try {
        $localeSql = job_has_locale_column($pdo) ? ' AND ' . job_sql_locale() : '';
        return $pdo->query('SELECT * FROM jobs WHERE status = 1' . $localeSql . ' ORDER BY posted_date DESC')->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Rolling validThrough for Google Jobs — always N days ahead while job stays active.
 */
function job_schema_valid_through(array $job): string
{
    require_once __DIR__ . '/seo-config.php';
    $days = defined('SEO_JOB_VALID_DAYS') ? (int) SEO_JOB_VALID_DAYS : 90;
    $days = max(30, min(180, $days));
    $ts = strtotime('+' . $days . ' days');
    return date('Y-m-d', $ts) . 'T23:59:59+05:30';
}

function job_seo_refresh_state_path(): string
{
    $dir = dirname(__DIR__) . '/lang/cache';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir . '/job-seo-refresh.json';
}

function job_seo_last_refresh(): ?array
{
    $path = job_seo_refresh_state_path();
    if (!is_file($path)) {
        return null;
    }
    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : null;
}

function job_seo_save_refresh(array $result): void
{
    file_put_contents(
        job_seo_refresh_state_path(),
        json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
}

/**
 * Collect all job-related URLs and ping IndexNow + Bing sitemap.
 *
 * @return array{refreshed_at: string, job_count: int, url_count: int, urls: string[]}
 */
function job_refresh_seo_signals(PDO $pdo): array
{
    require_once __DIR__ . '/seo.php';
    $base = seo_site_url_rtrim();
    $urls = [
        $base . '/careers',
        $base . '/jobs-index',
        $base . '/sitemap.xml',
        $base . '/rss.xml',
        $base . '/llms.txt',
    ];
    $jobCount = 0;

    try {
        $jobs = $pdo->query('SELECT slug, title, location FROM jobs WHERE status = 1');
        while ($row = $jobs->fetch(PDO::FETCH_ASSOC)) {
            $slug = $row['slug'] ?? job_make_slug($row['title'], $row['location'] ?? null);
            $urls[] = job_post_url($slug, $base);
            $jobCount++;
        }
    } catch (PDOException $e) {
    }

    $urls = array_values(array_unique($urls));
    seo_submit_indexnow($urls);
    seo_ping_bing_sitemap();

    $result = [
        'refreshed_at' => date('c'),
        'job_count'    => $jobCount,
        'url_count'    => count($urls),
        'urls'         => $urls,
    ];
    job_seo_save_refresh($result);

    return $result;
}

/**
 * Cron-friendly refresh — skips if refreshed within SEO_JOB_REFRESH_DAYS.
 */
function job_maybe_refresh_seo_signals(PDO $pdo): ?array
{
    require_once __DIR__ . '/seo-config.php';
    $intervalDays = defined('SEO_JOB_REFRESH_DAYS') ? (int) SEO_JOB_REFRESH_DAYS : 7;
    if ($intervalDays <= 0) {
        return null;
    }

    $last = job_seo_last_refresh();
    if ($last && !empty($last['refreshed_at'])) {
        $lastTs = strtotime($last['refreshed_at']);
        if ($lastTs !== false && $lastTs > strtotime('-' . $intervalDays . ' days')) {
            return null;
        }
    }

    return job_refresh_seo_signals($pdo);
}

function seo_job_posting_schema(array $job, string $base): array
{
    $jobId = (int) $job['id'];
    $slug = $job['slug'] ?? job_make_slug($job['title'], $job['location'] ?? null, $jobId);
    $datePosted = date('Y-m-d', strtotime($job['posted_date']));
    $validThrough = job_schema_valid_through($job);
    $minSal = isset($job['min_salary']) ? (int) $job['min_salary'] : 15000;
    $maxSal = isset($job['max_salary']) ? (int) $job['max_salary'] : 35000;
    $eduReq = $job['education'] ?? 'bachelorDegree';
    $expReq = isset($job['experience_months']) ? (int) $job['experience_months'] : 6;

    $schema = [
        '@context' => 'https://schema.org/',
        '@type' => 'JobPosting',
        'title' => $job['title'],
        'description' => strip_tags($job['description'] ?? ''),
        'identifier' => [
            '@type' => 'PropertyValue',
            'name' => 'Bisani Brothers',
            'value' => 'BBPL-JOB-' . $jobId,
        ],
        'datePosted' => $datePosted,
        'validThrough' => $validThrough,
        'employmentType' => job_employment_type($job['type'] ?? ''),
        'hiringOrganization' => [
            '@type' => 'Organization',
            'name' => 'Bisani Brothers Private Limited',
            'sameAs' => rtrim($base, '/'),
            'logo' => rtrim($base, '/') . '/assets/images/logos.png',
        ],
        'jobLocation' => [
            '@type' => 'Place',
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $job['location'] ?? 'India',
                'addressRegion' => 'Uttar Pradesh',
                'addressCountry' => 'IN',
            ],
        ],
        'url' => job_post_url($slug, $base),
        'baseSalary' => [
            '@type' => 'MonetaryAmount',
            'currency' => 'INR',
            'value' => [
                '@type' => 'QuantitativeValue',
                'minValue' => $minSal,
                'maxValue' => $maxSal,
                'unitText' => 'MONTH',
            ],
        ],
        'educationRequirements' => [
            '@type' => 'EducationalOccupationalCredential',
            'credentialCategory' => job_normalize_education($eduReq),
        ],
        'experienceRequirements' => [
            '@type' => 'OccupationalExperienceRequirements',
            'monthsOfExperience' => $expReq,
        ],
    ];

    if (!empty($job['vacancies']) && (int) $job['vacancies'] > 0) {
        $schema['totalJobOpenings'] = (int) $job['vacancies'];
    }
    if (!empty($job['department'])) {
        $schema['occupationalCategory'] = $job['department'];
    }
    if (!empty($job['apply_email']) && filter_var($job['apply_email'], FILTER_VALIDATE_EMAIL)) {
        $schema['applicationContact'] = [
            '@type' => 'ContactPoint',
            'email' => $job['apply_email'],
            'contactType' => 'HR',
        ];
    }
    $locationType = job_schema_job_location_type($job['work_mode'] ?? null);
    if ($locationType) {
        $schema['jobLocationType'] = $locationType;
    }

    return $schema;
}
