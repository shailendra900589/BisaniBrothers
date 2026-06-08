<?php

/**
 * Shared POST handler for general enquiry forms (not partner/careers/business).
 */
function enquiry_clean_input(string $data): string
{
    require_once __DIR__ . '/enquiry-helpers.php';

    return enquiry_normalize_field($data);
}

function enquiry_is_general_form_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
        && (string) ($_POST['bb_enquiry'] ?? '') === '1';
}

function enquiry_redirect_base(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    return ($path !== null && $path !== '') ? $path : '/index.php';
}

function enquiry_process_general_form(PDO $pdo, string $sourcePage): void
{
    if (!enquiry_is_general_form_post()) {
        return;
    }

    require_once __DIR__ . '/security.php';
    security_start_session();
    if (!security_verify_csrf($_POST['_csrf'] ?? null)) {
        $base = enquiry_redirect_base();
        $sep = str_contains($base, '?') ? '&' : '?';
        header('Location: ' . $base . $sep . 'status=error');
        exit;
    }

    if (!empty($_POST['website_check'])) {
        die('Spam detected.');
    }

    $allData = implode(' ', $_POST);
    if (preg_match('/http|https|www\.|ftp/i', $allData)) {
        $base = enquiry_redirect_base();
        echo "<script>alert('Links are not allowed in this form. Please remove any URLs.');window.location.href="
            . json_encode($base) . ";</script>";
        exit;
    }

    require_once __DIR__ . '/enquiry-helpers.php';

    $data = [
        'name'        => enquiry_clean_input($_POST['name'] ?? ''),
        'company'     => enquiry_clean_input($_POST['company'] ?? ''),
        'phone'       => enquiry_clean_input($_POST['phone'] ?? ''),
        'email'       => enquiry_clean_input($_POST['email'] ?? ''),
        'subject'     => enquiry_clean_input($_POST['subject'] ?? ''),
        'message'     => enquiry_clean_input($_POST['message'] ?? ''),
        'source_page' => $sourcePage,
    ];

    $result = enquiry_submit_general($pdo, $data);
    $base = enquiry_redirect_base();
    $sep = str_contains($base, '?') ? '&' : '?';
    header('Location: ' . $base . $sep . 'status=' . urlencode($result['status']));
    exit;
}

function enquiry_status_alert(string $status): ?array
{
    require_once __DIR__ . '/locale.php';
    locale_init();

    return match ($status) {
        'success' => [
            'type'  => 'success',
            'title' => t('form.enquiry_success_title', 'Message Sent!'),
            'desc'  => t('form.enquiry_success_desc', 'We have received your enquiry and will contact you shortly.'),
        ],
        'duplicate' => [
            'type'  => 'warning',
            'title' => t('form.enquiry_duplicate_title', 'Already Submitted'),
            'desc'  => t('form.enquiry_duplicate_desc', 'This email or mobile number is already registered with us. Our team will contact you soon.'),
        ],
        'invalid' => [
            'type'  => 'error',
            'title' => t('form.enquiry_invalid_title', 'Incomplete Form'),
            'desc'  => t('form.enquiry_invalid_desc', 'Please fill in your name, valid email, 10-digit mobile, and message.'),
        ],
        'error' => [
            'type'  => 'error',
            'title' => t('form.enquiry_error_title', 'Submission Failed'),
            'desc'  => t('form.enquiry_error_desc', 'Something went wrong. Please try again in a moment.'),
        ],
        default => null,
    };
}

function enquiry_render_status_alerts(): void
{
    $status = trim((string) ($_GET['status'] ?? ''));
    if ($status === '') {
        return;
    }

    $alert = enquiry_status_alert($status);
    if ($alert === null) {
        return;
    }

    $colors = match ($alert['type']) {
        'success' => 'bg-green-100 border-green-500 text-green-700',
        'warning' => 'bg-amber-100 border-amber-500 text-amber-800',
        default   => 'bg-red-100 border-red-500 text-red-700',
    };
    $icon = match ($alert['type']) {
        'success' => 'fa-circle-check',
        'warning' => 'fa-circle-exclamation',
        default   => 'fa-circle-xmark',
    };
    ?>
    <div id="enquiry-status-alert" class="fixed top-24 left-1/2 transform -translate-x-1/2 z-50 <?php echo $colors; ?> border-l-4 p-4 rounded shadow-2xl flex items-center w-[90%] sm:w-auto max-w-lg bb-alert-min-w-280">
        <i class="fa-solid <?php echo $icon; ?> text-2xl mr-3 shrink-0"></i>
        <div class="min-w-0">
            <p class="font-bold"><?php echo htmlspecialchars($alert['title']); ?></p>
            <p class="text-sm"><?php echo htmlspecialchars($alert['desc']); ?></p>
        </div>
        <button type="button" onclick="document.getElementById('enquiry-status-alert').remove()" class="ml-auto pl-4 font-bold opacity-70 hover:opacity-100 shrink-0" aria-label="Close">✕</button>
    </div>
    <script>setTimeout(function(){var el=document.getElementById('enquiry-status-alert');if(el)el.remove();},8000);</script>
    <?php
}

function enquiry_form_hidden_fields(): void
{
    require_once __DIR__ . '/security.php';
    ?>
    <input type="hidden" name="bb_enquiry" value="1">
    <?php echo security_csrf_field(); ?>
    <?php
}

function enquiry_render_form_validation_script(): void
{
    static $rendered = false;
    if ($rendered) {
        return;
    }
    $rendered = true;
    require_once __DIR__ . '/assets.php';
    ?>
    <script src="assets/js/enquiry-form.js?v=<?php echo bb_asset_version('assets/js/enquiry-form.js'); ?>" defer></script>
    <?php
}
