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
