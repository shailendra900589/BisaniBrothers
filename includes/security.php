<?php
/**
 * Bisani Brothers — shared security helpers (sessions, CSRF, headers, uploads).
 */

function security_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function security_is_local(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';

    return (bool) preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $host)
        || (php_sapi_name() === 'cli' && !getenv('BISANI_LIVE'));
}

function security_bootstrap(): void
{
    security_start_session();
    security_send_headers();
}

function security_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => security_is_https(),
        'samesite' => 'Strict',
    ]);
    session_start();
}

function security_send_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 1; mode=block');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    if (security_is_https() && !security_is_local()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function security_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_logged_in']);
}

function security_csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function security_csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="'
        . htmlspecialchars(security_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function security_verify_csrf(?string $token = null): bool
{
    $token = $token ?? ($_POST['_csrf'] ?? '');

    return is_string($token)
        && $token !== ''
        && hash_equals(security_csrf_token(), $token);
}

function security_require_csrf(): void
{
    if (!security_verify_csrf()) {
        http_response_code(403);
        exit('Invalid security token. Please refresh the page and try again.');
    }
}

function security_regenerate_session(): void
{
    session_regenerate_id(true);
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}

function security_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

function security_e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function security_json_attr(mixed $value): string
{
    return htmlspecialchars(json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
}

function security_login_allowed(): bool
{
    $attempts = $_SESSION['_login_attempts'] ?? ['count' => 0, 'until' => 0];
    if ((int) ($attempts['until'] ?? 0) > time()) {
        return false;
    }

    return true;
}

function security_login_failed(): void
{
    $attempts = $_SESSION['_login_attempts'] ?? ['count' => 0, 'until' => 0];
    $attempts['count'] = (int) ($attempts['count'] ?? 0) + 1;

    if ($attempts['count'] >= 5) {
        $attempts['until'] = time() + 900;
        $attempts['count'] = 0;
    }

    $_SESSION['_login_attempts'] = $attempts;
}

function security_login_succeeded(): void
{
    unset($_SESSION['_login_attempts']);
}

function security_login_lockout_message(): string
{
    $until = (int) (($_SESSION['_login_attempts']['until'] ?? 0));
    $mins = max(1, (int) ceil(($until - time()) / 60));

    return "Too many failed attempts. Please try again in {$mins} minute(s).";
}

/**
 * Strip dangerous markup from rich HTML (popups, CKEditor output).
 */
function security_sanitize_rich_html(string $html): string
{
    $html = preg_replace('#<(script|iframe|object|embed|form|meta|link|base)[^>]*>.*?</\1>#is', '', $html) ?? $html;
    $html = preg_replace('#<(script|iframe|object|embed|form|meta|link|base)[^>]*/?>#i', '', $html) ?? $html;
    $html = preg_replace('/\s(on\w+)\s*=\s*(["\']).*?\2/i', '', $html) ?? $html;
    $html = preg_replace('/\s(on\w+)\s*=\s*[^\s>]+/i', '', $html) ?? $html;
    $html = preg_replace('/javascript\s*:/i', '', $html) ?? $html;

    return trim($html);
}

/**
 * @param array<string, mixed> $file $_FILES entry
 * @param string[] $allowedExtensions lowercase, no dot
 */
function security_validate_upload(array $file, array $allowedExtensions, int $maxBytes = 5242880): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return 'File upload failed.';
    }

    if (($file['size'] ?? 0) > $maxBytes) {
        return 'File is too large.';
    }

    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if ($ext === '' || !in_array($ext, $allowedExtensions, true)) {
        return 'File type not allowed.';
    }

    return null;
}

function security_safe_upload_name(string $originalName, string $prefix = ''): string
{
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'bin';

    return $prefix . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
}

require_once __DIR__ . '/upload-storage.php';

function security_project_root(): string
{
    return upload_storage_project_root();
}

function security_upload_root_dir(): string
{
    $active = upload_storage_active_root();

    return $active ?? (security_project_root() . DIRECTORY_SEPARATOR . 'uploads');
}

/**
 * Ensure uploads directory exists and is writable (primary or Plesk tmp fallback).
 *
 * @return array{ok: bool, path: string, error: string}
 */
function security_ensure_upload_dir(string $subDir = ''): array
{
    $result = upload_storage_ensure_dir($subDir);

    return [
        'ok'    => $result['ok'],
        'path'  => $result['path'],
        'error' => $result['error'],
    ];
}

/**
 * Store an uploaded file under /uploads/{subDir}.
 *
 * @param array<string, mixed> $file $_FILES entry
 * @param string[] $allowedExtensions
 * @return array{ok: bool, error: string, path: ?string, db_path: ?string}
 */
function security_store_upload(array $file, string $subDir = '', array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'], int $maxBytes = 5242880): array
{
    $validation = security_validate_upload($file, $allowedExtensions, $maxBytes);
    if ($validation !== null) {
        return ['ok' => false, 'error' => $validation, 'path' => null, 'db_path' => null];
    }

    $ensure = security_ensure_upload_dir($subDir);
    if (!$ensure['ok']) {
        return ['ok' => false, 'error' => $ensure['error'], 'path' => null, 'db_path' => null];
    }

    $fileName = security_safe_upload_name((string) ($file['name'] ?? 'upload.bin'));
    $targetPath = rtrim($ensure['path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
    $subPath = trim(str_replace('\\', '/', $subDir), '/');
    $dbPath = $subPath === '' ? 'uploads/' . $fileName : 'uploads/' . $subPath . '/' . $fileName;
    $tmp = (string) ($file['tmp_name'] ?? '');

    if ($tmp !== '' && is_uploaded_file($tmp) && @move_uploaded_file($tmp, $targetPath)) {
        upload_storage_mirror_to_primary($targetPath, $dbPath);

        return ['ok' => true, 'error' => '', 'path' => $targetPath, 'db_path' => $dbPath];
    }

    if ($tmp !== '' && is_readable($tmp) && @copy($tmp, $targetPath)) {
        @unlink($tmp);
        upload_storage_mirror_to_primary($targetPath, $dbPath);

        return ['ok' => true, 'error' => '', 'path' => $targetPath, 'db_path' => $dbPath];
    }

    $phpError = error_get_last();
    $detail = is_array($phpError) ? ($phpError['message'] ?? '') : '';

    return [
        'ok'      => false,
        'error'   => 'Failed to save uploaded file.'
            . ($detail !== '' ? ' ' . $detail : '')
            . ' Folder: ' . $ensure['path'],
        'path'    => null,
        'db_path' => null,
    ];
}
