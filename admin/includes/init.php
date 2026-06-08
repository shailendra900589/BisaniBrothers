<?php
/**
 * Admin bootstrap — secure session, auth gate, CSRF helpers.
 */
require_once dirname(__DIR__, 2) . '/includes/security.php';

security_bootstrap();

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

// All admin POST requests require a valid CSRF token.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    security_require_csrf();
}
