<?php
/**
 * Admin bootstrap — secure session, auth gate, CSRF helpers.
 */
require_once dirname(__DIR__, 2) . '/includes/security.php';

security_bootstrap();
security_prepare_upload_environment();

if (!security_admin_logged_in()) {
    header('Location: login.php');
    exit;
}

/**
 * @param string[] $roles Allowed roles (lowercase). Empty = any logged-in user.
 */
function admin_require_roles(array $roles): void
{
    if ($roles === []) {
        return;
    }

    $role = strtolower((string) ($_SESSION['role'] ?? ''));
    if (!in_array($role, $roles, true)) {
        header('Location: dashboard.php');
        exit;
    }
}

function admin_handle_post_action(callable $handler, string $redirectUrl, string $field): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !isset($_POST[$field])) {
        return;
    }

    security_require_csrf();
    $id = (int) $_POST[$field];
    if ($id > 0) {
        $handler($id);
    }

    header('Location: ' . $redirectUrl);
    exit;
}

function admin_post_button(
    string $action,
    int $id,
    string $field,
    string $labelHtml,
    string $confirm = 'Are you sure?',
    string $class = 'inline'
): string {
    return '<form method="POST" action="' . security_e($action) . '" class="' . security_e($class)
        . '" onsubmit="return confirm(' . json_encode($confirm, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ')">'
        . security_csrf_field()
        . '<input type="hidden" name="' . security_e($field) . '" value="' . (int) $id . '">'
        . '<button type="submit" class="border-0 bg-transparent p-0 cursor-pointer">' . $labelHtml . '</button>'
        . '</form>';
}

function admin_post_body_too_large(): bool
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return false;
    }

    $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

    return $length > 0 && empty($_POST) && empty($_FILES);
}

function admin_fail_post_body_too_large(): void
{
    if (!admin_post_body_too_large()) {
        return;
    }

    http_response_code(413);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Request too large</title></head><body style="font-family:sans-serif;max-width:720px;margin:3rem auto;padding:0 1rem;">';
    echo '<h1>Form too large</h1>';
    echo '<p>The server rejected this save because the article or job description exceeds <code>post_max_size</code>.</p>';
    echo '<p>Try removing large inline images from the editor, saving a shorter draft, or ask hosting to increase PHP <code>post_max_size</code> and <code>upload_max_filesize</code>.</p>';
    echo '<p><a href="javascript:history.back()">Go back</a></p>';
    echo '</body></html>';
    exit;
}

// Reject oversized POST bodies before CSRF (empty $_POST when body was truncated).
admin_fail_post_body_too_large();

// All admin POST requests require a valid CSRF token.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    security_require_csrf();
}
