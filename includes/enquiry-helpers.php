<?php

require_once __DIR__ . '/mail-helpers.php';

function enquiry_statuses(): array
{
    return [
        'new'       => 'New',
        'contacted' => 'Contacted',
        'closed'    => 'Closed',
    ];
}

function enquiry_status_badge_class(string $status): string
{
    return match ($status) {
        'contacted' => 'bg-blue-50 text-blue-700 border-blue-100',
        'closed'    => 'bg-green-50 text-green-700 border-green-100',
        default     => 'bg-amber-50 text-amber-700 border-amber-100',
    };
}

function enquiry_normalize_field(string $value): string
{
    $value = stripslashes(trim($value));
    $value = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00AD}\x{2060}]/u', '', $value) ?? $value;
    $value = str_replace("\xc2\xa0", ' ', $value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

    return trim(strip_tags($value));
}

function enquiry_normalize_phone(string $phone): string
{
    return preg_replace('/\D+/', '', $phone);
}

function enquiry_phone_last10(string $phone): string
{
    $digits = enquiry_normalize_phone($phone);
    return strlen($digits) >= 10 ? substr($digits, -10) : $digits;
}

function enquiry_is_blank_submission(array $data, array $requiredKeys): bool
{
    foreach ($requiredKeys as $key) {
        if (enquiry_normalize_field((string) ($data[$key] ?? '')) !== '') {
            return false;
        }
    }

    return true;
}

function enquiry_validate_general(array $data): ?string
{
    $name = enquiry_normalize_field($data['name'] ?? '');
    $email = enquiry_normalize_field($data['email'] ?? '');
    $phone = enquiry_normalize_field($data['phone'] ?? '');
    $message = enquiry_normalize_field($data['message'] ?? '');

    if ($name === '' || mb_strlen($name) < 2) {
        return 'Please enter your full name.';
    }
    if (!preg_match('/^[\p{L}\p{M}\s.\'-]+$/u', $name)) {
        return 'Please enter a valid name.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }
    if (enquiry_phone_last10($phone) === '' || strlen(enquiry_phone_last10($phone)) < 10) {
        return 'Please enter a valid 10-digit mobile number.';
    }
    if ($message === '' || mb_strlen($message) < 5) {
        return 'Please describe your requirements (at least 5 characters).';
    }
    if (preg_match('/^\s*([.?!\-_*#@]|(.)\2{4,})+\s*$/u', $message)) {
        return 'Please enter a meaningful message.';
    }

    return null;
}

function enquiry_validate_business(array $data): ?string
{
    $name = enquiry_normalize_field($data['name'] ?? '');
    $email = enquiry_normalize_field($data['email'] ?? '');
    $phone = enquiry_normalize_field($data['phone'] ?? '');
    $message = enquiry_normalize_field($data['message'] ?? '');

    if ($name === '' || mb_strlen($name) < 2) {
        return 'Please enter your full name.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }
    if (enquiry_phone_last10($phone) === '' || strlen(enquiry_phone_last10($phone)) < 10) {
        return 'Please enter a valid 10-digit mobile number.';
    }
    if ($message === '' || mb_strlen($message) < 5) {
        return 'Please describe your business requirements (at least 5 characters).';
    }

    return null;
}

function enquiry_general_exists(PDO $pdo, string $email, string $phone): bool
{
    $email = strtolower(trim($email));
    $phone10 = enquiry_phone_last10($phone);

    if ($email !== '') {
        $stmt = $pdo->prepare('SELECT id FROM enquiries WHERE LOWER(TRIM(email)) = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            return true;
        }
    }

    if (strlen($phone10) >= 10) {
        $stmt = $pdo->query('SELECT phone FROM enquiries WHERE phone IS NOT NULL AND TRIM(phone) != ""');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (enquiry_phone_last10($row['phone'] ?? '') === $phone10) {
                return true;
            }
        }
    }

    return false;
}

function newsletter_subscribe_from_enquiry(PDO $pdo, string $email): void
{
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO newsletter_subscribers (email, status) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE status = ?'
        );
        $stmt->execute([$email, 'active', 'active']);
    } catch (PDOException $e) {
        error_log('Newsletter subscribe from enquiry failed: ' . $e->getMessage());
    }
}

/**
 * Validate, dedupe, save general enquiry, notify admin, auto-subscribe newsletter.
 *
 * @return array{status: string, message: string}
 */
function enquiry_submit_business(PDO $pdo, array $data): array
{
    $invalid = enquiry_validate_business($data);
    if ($invalid !== null) {
        return ['status' => 'invalid', 'message' => $invalid];
    }

    if (!enquiry_save_business($pdo, $data)) {
        return ['status' => 'error', 'message' => 'Unable to save enquiry. Please try again.'];
    }

    return ['status' => 'success', 'message' => 'Enquiry submitted successfully.'];
}

function enquiry_submit_general(PDO $pdo, array $data): array
{
    if (enquiry_is_blank_submission($data, ['name', 'email', 'phone', 'message'])) {
        return ['status' => 'invalid', 'message' => 'Please fill in all required fields.'];
    }

    $invalid = enquiry_validate_general($data);
    if ($invalid !== null) {
        return ['status' => 'invalid', 'message' => $invalid];
    }

    $email = strtolower(enquiry_normalize_field($data['email']));
    $phone = enquiry_normalize_field($data['phone']);

    if (enquiry_general_exists($pdo, $email, $phone)) {
        return [
            'status'  => 'duplicate',
            'message' => 'This email or mobile number has already been submitted.',
        ];
    }

    if (!enquiry_save_general($pdo, $data)) {
        return ['status' => 'error', 'message' => 'Unable to save enquiry. Please try again.'];
    }

    newsletter_subscribe_from_enquiry($pdo, $email);

    return ['status' => 'success', 'message' => 'Enquiry submitted successfully.'];
}

function enquiry_save_general(PDO $pdo, array $data): bool
{
    if (enquiry_validate_general($data) !== null) {
        error_log('enquiry_save_general blocked: validation failed');
        return false;
    }

    $created_at = date('Y-m-d H:i:s');
    $name = enquiry_normalize_field($data['name'] ?? '');
    $email = strtolower(enquiry_normalize_field($data['email'] ?? ''));
    $phone = enquiry_normalize_field($data['phone'] ?? '');
    $message = enquiry_normalize_field($data['message'] ?? '');
    $company = enquiry_normalize_field($data['company'] ?? '') ?: null;
    $subject = enquiry_normalize_field($data['subject'] ?? '') ?: null;
    $source = enquiry_normalize_field($data['source_page'] ?? 'Website') ?: 'Website';

    try {
        $sql = 'INSERT INTO enquiries (name, company, phone, email, subject, message, source_page, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([$name, $company, $phone, $email, $subject, $message, $source, 'new', $created_at]);
    } catch (PDOException $e) {
        try {
            $sql = 'INSERT INTO enquiries (name, company, phone, email, message, source_page, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)';
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([$name, $company, $phone, $email, $message, $source, $created_at]);
        } catch (PDOException $e2) {
            error_log('enquiry_save_general: ' . $e2->getMessage());
            return false;
        }
    }

    if ($ok) {
        mail_enquiry_notification('General', [
            'type'        => 'General Enquiry',
            'source'      => $source,
            'name'        => $name,
            'email'       => $email,
            'phone'       => $phone,
            'company'     => $company ?? '',
            'subject'     => $subject ?? '',
            'message'     => $message,
        ]);
    }

    return (bool) $ok;
}

function enquiry_save_business(PDO $pdo, array $data): bool
{
    if (enquiry_validate_business($data) !== null) {
        error_log('enquiry_save_business blocked: validation failed');
        return false;
    }

    $created_at = date('Y-m-d H:i:s');
    $name = enquiry_normalize_field($data['name'] ?? '');
    $email = strtolower(enquiry_normalize_field($data['email'] ?? ''));
    $phone = enquiry_normalize_field($data['phone'] ?? '');
    $industry = enquiry_normalize_field($data['industry'] ?? '');
    $services = enquiry_normalize_field($data['services'] ?? '');
    $message = enquiry_normalize_field($data['message'] ?? '');

    try {
        $sql = 'INSERT INTO contact_enquiries (name, email, phone, industry, services, message, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            $name,
            $email,
            $phone,
            $industry,
            $services,
            $message,
            'new',
            $created_at,
        ]);
    } catch (PDOException $e) {
        try {
            $sql = 'INSERT INTO contact_enquiries (name, email, phone, industry, services, message, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)';
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                $name,
                $email,
                $phone,
                $industry,
                $services,
                $message,
                $created_at,
            ]);
        } catch (PDOException $e2) {
            return false;
        }
    }
    if ($ok) {
        mail_enquiry_notification('Business', [
            'type'     => 'Business Enquiry',
            'source'   => 'Why Work With Us',
            'name'     => $name,
            'email'    => $email,
            'phone'    => $phone,
            'industry' => $industry,
            'services' => $services,
            'message'  => $message,
        ]);
    }
    return (bool) $ok;
}

function enquiry_update_status(PDO $pdo, string $type, int $id, string $status): bool
{
    if (!array_key_exists($status, enquiry_statuses())) {
        return false;
    }
    $table = ($type === 'Business') ? 'contact_enquiries' : 'enquiries';
    try {
        $stmt = $pdo->prepare("UPDATE {$table} SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    } catch (PDOException $e) {
        return false;
    }
}

function enquiry_fetch_all(PDO $pdo, ?string $statusFilter = null): array
{
    $all = enquiry_fetch_merged($pdo);

    if ($statusFilter && array_key_exists($statusFilter, enquiry_statuses())) {
        $all = array_values(array_filter($all, fn($r) => ($r['status'] ?? 'new') === $statusFilter));
    }
    return $all;
}

function enquiry_fetch_merged(PDO $pdo): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $generalSql = "SELECT id, name, email, phone, subject, message, source_page, created_at, 'General' as type";
    try {
        $pdo->query('SELECT status FROM enquiries LIMIT 1');
        $generalSql .= ', COALESCE(status, \'new\') as status';
    } catch (PDOException $e) {
        $generalSql .= ", 'new' as status";
    }
    $generalSql .= ' FROM enquiries ORDER BY created_at DESC';
    $general = $pdo->query($generalSql)->fetchAll(PDO::FETCH_ASSOC);

    $bizSql = "SELECT id, name, email, phone, industry as subject, message, created_at, 'Business' as type";
    try {
        $pdo->query('SELECT status FROM contact_enquiries LIMIT 1');
        $bizSql .= ", COALESCE(status, 'new') as status, 'Why Work With Us' as source_page";
    } catch (PDOException $e) {
        $bizSql .= ", 'new' as status, 'Why Work With Us' as source_page";
    }
    $bizSql .= ' FROM contact_enquiries ORDER BY created_at DESC';
    $business = $pdo->query($bizSql)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($general as &$row) {
        if (!isset($row['source_page'])) {
            $row['source_page'] = 'General';
        }
    }
    unset($row);

    $all = array_merge($general, $business);
    usort($all, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

    $cache = $all;
    return $cache;
}

function enquiry_status_counts(PDO $pdo): array
{
    $counts = ['all' => 0, 'new' => 0, 'contacted' => 0, 'closed' => 0];
    foreach (enquiry_fetch_merged($pdo) as $row) {
        $counts['all']++;
        $s = $row['status'] ?? 'new';
        if (isset($counts[$s])) {
            $counts[$s]++;
        }
    }
    return $counts;
}

function enquiry_filter_params(): array
{
    return [
        'filter'    => trim((string) ($_GET['filter'] ?? 'all')),
        'q'         => trim((string) ($_GET['q'] ?? '')),
        'type'      => trim((string) ($_GET['type'] ?? '')),
        'source'    => trim((string) ($_GET['source'] ?? '')),
        'date_from' => trim((string) ($_GET['date_from'] ?? '')),
        'date_to'   => trim((string) ($_GET['date_to'] ?? '')),
        'page'      => max(1, (int) ($_GET['page'] ?? 1)),
    ];
}

function enquiry_build_query(array $params, array $override = []): string
{
    $merged = array_merge($params, $override);
    $out = [];
    foreach ($merged as $key => $val) {
        if ($val === '' || $val === null || ($key === 'filter' && $val === 'all') || ($key === 'page' && (int) $val <= 1)) {
            continue;
        }
        $out[$key] = $val;
    }
    return $out === [] ? '' : '?' . http_build_query($out);
}

function enquiry_apply_filters(array $rows, array $params): array
{
    $statusFilter = ($params['filter'] !== 'all' && array_key_exists($params['filter'], enquiry_statuses()))
        ? $params['filter']
        : null;
    $q = strtolower($params['q']);
    $type = $params['type'];
    $source = strtolower($params['source']);
    $dateFrom = $params['date_from'] !== '' && strtotime($params['date_from']) ? strtotime($params['date_from'] . ' 00:00:00') : null;
    $dateTo = $params['date_to'] !== '' && strtotime($params['date_to']) ? strtotime($params['date_to'] . ' 23:59:59') : null;

    return array_values(array_filter($rows, function ($row) use ($statusFilter, $q, $type, $source, $dateFrom, $dateTo) {
        if ($statusFilter && ($row['status'] ?? 'new') !== $statusFilter) {
            return false;
        }
        if ($type !== '' && ($row['type'] ?? '') !== $type) {
            return false;
        }
        if ($source !== '' && !str_contains(strtolower((string) ($row['source_page'] ?? '')), $source)) {
            return false;
        }
        if ($dateFrom !== null && strtotime($row['created_at']) < $dateFrom) {
            return false;
        }
        if ($dateTo !== null && strtotime($row['created_at']) > $dateTo) {
            return false;
        }
        if ($q !== '') {
            $haystack = strtolower(implode(' ', [
                $row['name'] ?? '',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                $row['subject'] ?? '',
                $row['message'] ?? '',
                $row['source_page'] ?? '',
            ]));
            if (!str_contains($haystack, $q)) {
                return false;
            }
        }
        return true;
    }));
}

function enquiry_paginate(array $rows, int $page, int $perPage = 25): array
{
    $total = count($rows);
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $totalPages);
    $offset = ($page - 1) * $perPage;

    return [
        'rows'        => array_slice($rows, $offset, $perPage),
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => $totalPages,
        'from'        => $total === 0 ? 0 : $offset + 1,
        'to'          => min($offset + $perPage, $total),
    ];
}

function enquiry_source_options(PDO $pdo): array
{
    $sources = [];
    foreach (enquiry_fetch_merged($pdo) as $row) {
        $src = trim((string) ($row['source_page'] ?? ''));
        if ($src !== '') {
            $sources[$src] = $src;
        }
    }
    ksort($sources);
    return array_values($sources);
}

function enquiry_is_valid_row(array $row): bool
{
    $name = enquiry_normalize_field($row['name'] ?? '');
    $email = enquiry_normalize_field($row['email'] ?? '');
    $message = enquiry_normalize_field($row['message'] ?? '');

    return $name !== ''
        && mb_strlen($name) >= 2
        && filter_var($email, FILTER_VALIDATE_EMAIL)
        && $message !== ''
        && mb_strlen($message) >= 5;
}

function enquiry_dashboard_stats(PDO $pdo): array
{
    $rows = array_values(array_filter(enquiry_fetch_merged($pdo), 'enquiry_is_valid_row'));
    $stats = [
        'total'     => count($rows),
        'new'       => 0,
        'contacted' => 0,
        'closed'    => 0,
        'general'   => 0,
        'business'  => 0,
        'recent'    => array_slice($rows, 0, 5),
    ];

    foreach ($rows as $row) {
        $status = $row['status'] ?? 'new';
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
        if (($row['type'] ?? '') === 'Business') {
            $stats['business']++;
        } else {
            $stats['general']++;
        }
    }

    return $stats;
}
