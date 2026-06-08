<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin', 'marketer']);

require '../db.php';
require_once __DIR__ . '/../includes/assets.php';
require_once __DIR__ . '/../includes/marketing-helpers.php';

$templates = marketing_templates();
$activeCount = (int) $pdo->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status='active'")->fetchColumn();

$selectedIds = [];
if (!empty($_GET['selected'])) {
    $selectedIds = array_values(array_filter(array_map('intval', explode(',', (string) $_GET['selected']))));
}

if (isset($_GET['download']) && $_GET['download'] === 'sample_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="bulk-email-sample.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Name']);
    fputcsv($out, ['client@example.com', 'Client Name']);
    fputcsv($out, ['partner@company.in', 'Partner Co']);
    fclose($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_image' && !empty($_FILES['image']['name'])) {
        $rootPath = dirname(__DIR__);
        $uploadDir = $rootPath . '/uploads/marketing/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            echo json_encode(['ok' => false, 'message' => 'Only JPG, PNG, GIF, WEBP allowed.']);
            exit;
        }
        $filename = 'mkt_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $url = seo_site_url_rtrim() . '/uploads/marketing/' . $filename;
            echo json_encode(['ok' => true, 'url' => $url]);
        } else {
            echo json_encode(['ok' => false, 'message' => 'Upload failed. Check folder permissions.']);
        }
        exit;
    }

    if ($action === 'parse_bulk') {
        $manualRaw = (string) ($_POST['manual_emails'] ?? '');
        $importRaw = (string) ($_POST['import_emails'] ?? '');
        $mode = $_POST['recipient_mode'] ?? 'manual';
        $csvPath = null;

        if (!empty($_FILES['import_csv']['tmp_name']) && is_uploaded_file($_FILES['import_csv']['tmp_name'])) {
            $csvPath = $_FILES['import_csv']['tmp_name'];
        }

        $recipients = marketing_resolve_recipients(
            $pdo,
            $mode === 'import' ? 'import' : 'manual',
            [],
            $manualRaw,
            $importRaw,
            $csvPath
        );
        $emails = array_column($recipients, 'email');

        echo json_encode([
            'ok'     => true,
            'count'  => count($emails),
            'sample' => array_slice($emails, 0, 8),
        ]);
        exit;
    }

    if ($action === 'send_test') {
        $subject = trim($_POST['campaign_subject'] ?? '');
        $bodyHtml = $_POST['body_html'] ?? '';
        $testEmail = filter_var(trim($_POST['test_email'] ?? ''), FILTER_VALIDATE_EMAIL);

        if ($testEmail === false) {
            echo json_encode(['ok' => false, 'message' => 'Enter a valid test email address.']);
            exit;
        }
        if ($subject === '' || trim(strip_tags($bodyHtml)) === '') {
            echo json_encode(['ok' => false, 'message' => 'Subject and email body are required.']);
            exit;
        }

        $testSubject = '[TEST] ' . $subject;
        $ok = marketing_send_to_recipient($testSubject, $bodyHtml, $testEmail);
        $meta = mail_send_meta();

        echo json_encode([
            'ok'      => $ok,
            'message' => $ok
                ? ($meta['message'] ?? ('Test email sent to ' . $testEmail))
                : ($meta['message'] ?? 'Could not send test email. Verify Google SMTP relay on live server.'),
            'method'  => $meta['method'] ?? '',
            'outbox'  => $meta['path'] ?? null,
        ]);
        exit;
    }

    if ($action === 'create_and_send') {
        $title = trim($_POST['campaign_title'] ?? 'Marketing Campaign');
        $subject = trim($_POST['campaign_subject'] ?? '');
        $bodyHtml = $_POST['body_html'] ?? '';
        $templateKey = $_POST['template_key'] ?? 'manual';
        $recipientMode = in_array($_POST['recipient_mode'] ?? '', ['all', 'selected', 'manual', 'import'], true)
            ? $_POST['recipient_mode']
            : 'all';
        $selected = array_values(array_filter(array_map('intval', explode(',', $_POST['selected_ids'] ?? ''))));
        $manualRaw = (string) ($_POST['manual_emails'] ?? '');
        $importRaw = (string) ($_POST['import_emails'] ?? '');
        $addToNewsletter = !empty($_POST['add_to_newsletter']);
        $csvPath = null;

        if (!empty($_FILES['import_csv']['tmp_name']) && is_uploaded_file($_FILES['import_csv']['tmp_name'])) {
            $csvPath = $_FILES['import_csv']['tmp_name'];
        }

        if ($subject === '' || trim(strip_tags($bodyHtml)) === '') {
            echo json_encode(['ok' => false, 'message' => 'Subject and email body are required.']);
            exit;
        }

        $recipients = marketing_resolve_recipients($pdo, $recipientMode, $selected, $manualRaw, $importRaw, $csvPath);
        if ($recipients === []) {
            echo json_encode(['ok' => false, 'message' => 'No valid email addresses found. Add or import emails first.']);
            exit;
        }

        $storedRecipients = ($recipientMode === 'selected')
            ? array_column($recipients, 'id')
            : array_column($recipients, 'email');

        if ($addToNewsletter && in_array($recipientMode, ['manual', 'import'], true)) {
            marketing_subscribe_bulk($pdo, array_column($recipients, 'email'));
        }

        $campaignId = marketing_create_campaign($pdo, [
            'title'            => $title,
            'subject'          => $subject,
            'body_html'        => $bodyHtml,
            'template_key'     => $templateKey,
            'recipient_mode'   => $recipientMode,
            'recipient_emails' => $storedRecipients,
            'total_recipients' => count($recipients),
            'created_by'       => $_SESSION['username'] ?? 'admin',
        ]);

        echo json_encode([
            'ok'          => true,
            'campaign_id' => $campaignId,
            'total'       => count($recipients),
        ]);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Unknown action']);
    exit;
}

$tab = ($_GET['tab'] ?? 'compose') === 'history' ? 'history' : 'compose';
$campaigns = marketing_fetch_campaigns($pdo);
$defaultVars = [
    'HEADLINE'   => 'Scale Your Business with Bisani Brothers',
    'PREHEADER'  => 'Execution, staffing & growth solutions across India.',
    'BODY'       => '<p>We help businesses grow through on-ground sales execution, trained field teams, and data-driven market insights.</p><ul><li>Sales & Growth Solutions</li><li>Staffing & Deployment</li><li>Market Research & BTL Activation</li></ul>',
    'IMAGE_URL'  => seo_site_url_rtrim() . '/assets/images/logos.png',
    'IMAGE_LINK' => seo_site_url_rtrim() . '/',
    'CTA_TEXT'   => 'Get Started Today',
    'CTA_URL'    => seo_site_url_rtrim() . '/contact',
    'OFFER_CODE' => 'BBPL2026',
];
$templatesJson = [];
foreach ($templates as $key => $tpl) {
    $templatesJson[$key] = ['label' => $tpl['label'], 'subject' => $tpl['subject'], 'html' => $tpl['html']];
}
$isLocalMail = mail_is_local_env();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Marketing Campaigns | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden">
<?php include 'includes/sidebar.php'; ?>
<main class="flex-1 overflow-y-auto">
    <header class="bg-white border-b px-8 h-20 flex items-center justify-between sticky top-0 z-10">
        <div>
            <h2 class="text-2xl font-bold text-[#173978]">Marketing Campaigns</h2>
            <p class="text-xs text-slate-500">Send via marketing@bisanibrother.com · <?php echo $activeCount; ?> active subscribers</p>
        </div>
        <div class="flex gap-2">
            <a href="marketing-campaigns.php?tab=compose" class="text-sm font-bold px-4 py-2 rounded-lg <?php echo $tab === 'compose' ? 'bg-[#173978] text-white' : 'bg-slate-100 text-slate-600'; ?>">Compose</a>
            <a href="marketing-campaigns.php?tab=history" class="text-sm font-bold px-4 py-2 rounded-lg <?php echo $tab === 'history' ? 'bg-[#173978] text-white' : 'bg-slate-100 text-slate-600'; ?>">History</a>
            <a href="newsletter.php" class="text-sm font-bold bg-[#2fcaf0] text-[#173978] px-4 py-2 rounded-lg hover:bg-[#173978] hover:text-white"><i class="fa-solid fa-users mr-1"></i> Subscribers</a>
            <?php if ($isLocalMail): ?>
            <a href="mail-outbox.php" class="text-sm font-bold border border-amber-300 bg-amber-50 text-amber-900 px-4 py-2 rounded-lg"><i class="fa-solid fa-inbox mr-1"></i> Mail Outbox</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="p-8 max-w-6xl mx-auto">
        <?php if (isset($_GET['msg'])): ?>
        <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg text-sm border border-green-100"><?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>
        <?php if ($isLocalMail && $tab === 'compose'): ?>
        <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-950">
            <i class="fa-solid fa-laptop-code mr-1"></i>
            <strong>Localhost mode:</strong> Google SMTP relay does not work on XAMPP.
            Test emails are saved to <a href="mail-outbox.php" class="font-bold underline">Mail Outbox</a> so you can preview them.
            On live server, real emails send automatically.
        </div>
        <?php endif; ?>

        <?php if ($tab === 'history'): ?>
        <div class="bg-white rounded-2xl border overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left">
                    <tr>
                        <th class="p-4 font-bold text-slate-500">Campaign</th>
                        <th class="p-4 font-bold text-slate-500">Recipients</th>
                        <th class="p-4 font-bold text-slate-500">Sent / Failed</th>
                        <th class="p-4 font-bold text-slate-500">Status</th>
                        <th class="p-4 font-bold text-slate-500">Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($campaigns as $c): ?>
                <tr class="border-t border-slate-100">
                    <td class="p-4">
                        <p class="font-bold text-[#173978]"><?php echo htmlspecialchars($c['title']); ?></p>
                        <p class="text-xs text-slate-500"><?php echo htmlspecialchars($c['subject']); ?></p>
                    </td>
                    <td class="p-4"><?php echo (int) $c['total_recipients']; ?> <span class="text-xs text-slate-400">(<?php echo marketing_recipient_mode_label($c['recipient_mode']); ?>)</span></td>
                    <td class="p-4"><span class="text-green-600 font-bold"><?php echo (int) $c['sent_count']; ?></span> / <span class="text-red-500"><?php echo (int) $c['failed_count']; ?></span></td>
                    <td class="p-4"><span class="text-xs font-bold px-2 py-1 rounded uppercase <?php echo $c['status'] === 'sent' ? 'bg-green-50 text-green-700' : ($c['status'] === 'failed' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700'); ?>"><?php echo htmlspecialchars($c['status']); ?></span></td>
                    <td class="p-4 text-slate-500"><?php echo date('M d, Y H:i', strtotime($c['sent_at'] ?: $c['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$campaigns): ?><tr><td colspan="5" class="p-8 text-center text-slate-400">No campaigns sent yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>

        <form id="campaign-form" class="grid grid-cols-1 lg:grid-cols-12 gap-6" enctype="multipart/form-data" method="POST">
            <?php echo security_csrf_field(); ?>
            <input type="hidden" name="action" value="create_and_send">
            <input type="hidden" name="selected_ids" value="<?php echo htmlspecialchars(implode(',', $selectedIds)); ?>">

            <div class="lg:col-span-4 space-y-4">
                <div class="bg-white rounded-2xl border p-5 space-y-4">
                    <h3 class="font-bold text-[#173978]"><i class="fa-solid fa-wand-magic-sparkles mr-2 text-[#2fcaf0]"></i>Template & Settings</h3>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase">Template</label>
                        <select id="template-key" name="template_key" class="w-full mt-1 border rounded-lg p-2.5 text-sm">
                            <?php foreach ($templates as $key => $tpl): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($tpl['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase">Campaign Title (internal)</label>
                        <input type="text" name="campaign_title" class="w-full mt-1 border rounded-lg p-2.5 text-sm" placeholder="March Offer Blast" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase">Email Subject</label>
                        <input type="text" id="campaign-subject" name="campaign_subject" class="w-full mt-1 border rounded-lg p-2.5 text-sm" value="<?php echo htmlspecialchars($templates['business_insights']['subject']); ?>" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase">Recipients</label>
                        <div class="mt-2 space-y-2 text-sm">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="recipient_mode" value="all" class="recipient-mode-radio" <?php echo $selectedIds === [] ? 'checked' : ''; ?>>
                                <span>All active subscribers (<?php echo $activeCount; ?>)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="recipient_mode" value="selected" class="recipient-mode-radio" <?php echo $selectedIds !== [] ? 'checked' : ''; ?>>
                                <span>Selected subscribers (<?php echo count($selectedIds); ?>)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="recipient_mode" value="manual" class="recipient-mode-radio">
                                <span>Type / paste emails manually</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="recipient_mode" value="import" class="recipient-mode-radio">
                                <span>Import bulk list (CSV / paste)</span>
                            </label>
                        </div>
                        <?php if ($selectedIds !== []): ?>
                        <p class="text-xs text-[#2fcaf0] mt-2"><i class="fa-solid fa-check mr-1"></i>Pre-selected from Newsletter page</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="panel-manual" class="hidden bg-white rounded-2xl border p-5 space-y-3">
                    <h3 class="font-bold text-[#173978] text-sm uppercase"><i class="fa-solid fa-keyboard mr-1"></i> Manual Email List</h3>
                    <p class="text-xs text-slate-500">One email per line, or comma / semicolon separated.</p>
                    <textarea id="manual-emails" name="manual_emails" rows="8" class="w-full border rounded-lg p-3 text-sm font-mono" placeholder="client1@company.com&#10;client2@company.com&#10;sales@partner.in"></textarea>
                    <button type="button" id="btn-count-manual" class="text-sm font-bold text-[#173978] bg-slate-100 hover:bg-[#2fcaf0]/30 px-4 py-2 rounded-lg w-full">
                        <i class="fa-solid fa-calculator mr-1"></i> Count Valid Emails
                    </button>
                    <p id="manual-count-msg" class="text-xs text-slate-500 hidden"></p>
                </div>

                <div id="panel-import" class="hidden bg-white rounded-2xl border p-5 space-y-3">
                    <h3 class="font-bold text-[#173978] text-sm uppercase"><i class="fa-solid fa-file-csv mr-1"></i> Bulk Import</h3>
                    <div>
                        <label class="text-xs text-slate-500 block mb-1">Upload CSV / TXT file</label>
                        <input type="file" id="import-csv" name="import_csv" accept=".csv,.txt,.xls,.xlsx" class="w-full text-sm">
                        <p class="text-xs text-slate-400 mt-1">CSV with Email column, or any column containing emails.</p>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 block mb-1">Or paste list / CSV content</label>
                        <textarea id="import-emails" name="import_emails" rows="6" class="w-full border rounded-lg p-3 text-sm font-mono" placeholder="Email,Name&#10;john@company.com,John&#10;jane@company.com,Jane"></textarea>
                    </div>
                    <button type="button" id="btn-count-import" class="text-sm font-bold text-[#173978] bg-slate-100 hover:bg-[#2fcaf0]/30 px-4 py-2 rounded-lg w-full">
                        <i class="fa-solid fa-calculator mr-1"></i> Count Imported Emails
                    </button>
                    <p id="import-count-msg" class="text-xs text-slate-500 hidden"></p>
                    <a href="marketing-campaigns.php?download=sample_csv" class="text-xs text-[#173978] underline block">Download sample CSV template</a>
                </div>

                <div id="panel-bulk-options" class="hidden bg-amber-50 rounded-2xl border border-amber-100 p-4">
                    <label class="flex items-start gap-2 cursor-pointer text-sm">
                        <input type="checkbox" name="add_to_newsletter" value="1" class="mt-1 rounded">
                        <span><strong>Also add to Newsletter subscribers</strong><br><span class="text-xs text-slate-600">Imported/manual emails will be saved for future campaigns.</span></span>
                    </label>
                </div>

                <div class="bg-white rounded-2xl border p-5 space-y-3">
                    <h3 class="font-bold text-[#173978] text-sm uppercase">Quick Fields</h3>
                    <div><label class="text-xs text-slate-500">Headline</label><input id="fld-headline" type="text" class="w-full border rounded-lg p-2 text-sm" value="<?php echo htmlspecialchars($defaultVars['HEADLINE']); ?>"></div>
                    <div><label class="text-xs text-slate-500">Preheader / Subtitle</label><input id="fld-preheader" type="text" class="w-full border rounded-lg p-2 text-sm" value="<?php echo htmlspecialchars($defaultVars['PREHEADER']); ?>"></div>
                    <div><label class="text-xs text-slate-500">Hero Image URL</label><input id="fld-image-url" type="url" class="w-full border rounded-lg p-2 text-sm" value="<?php echo htmlspecialchars($defaultVars['IMAGE_URL']); ?>"></div>
                    <div><label class="text-xs text-slate-500">Image Click Link</label><input id="fld-image-link" type="url" class="w-full border rounded-lg p-2 text-sm" value="<?php echo htmlspecialchars($defaultVars['IMAGE_LINK']); ?>"></div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="text-xs text-slate-500">CTA Text</label><input id="fld-cta-text" type="text" class="w-full border rounded-lg p-2 text-sm" value="<?php echo htmlspecialchars($defaultVars['CTA_TEXT']); ?>"></div>
                        <div><label class="text-xs text-slate-500">Offer Code</label><input id="fld-offer-code" type="text" class="w-full border rounded-lg p-2 text-sm" value="<?php echo htmlspecialchars($defaultVars['OFFER_CODE']); ?>"></div>
                    </div>
                    <div><label class="text-xs text-slate-500">CTA Button Link</label><input id="fld-cta-url" type="url" class="w-full border rounded-lg p-2 text-sm" value="<?php echo htmlspecialchars($defaultVars['CTA_URL']); ?>"></div>
                </div>

                <div class="bg-white rounded-2xl border p-5">
                    <h3 class="font-bold text-[#173978] text-sm uppercase mb-3">Upload Image</h3>
                    <input type="file" id="mkt-image-file" accept="image/*" class="w-full text-sm mb-2">
                    <button type="button" id="btn-upload-image" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2 rounded-lg text-sm"><i class="fa-solid fa-upload mr-1"></i> Upload & Use</button>
                </div>
            </div>

            <div class="lg:col-span-8 space-y-4">
                <div class="bg-white rounded-2xl border p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-bold text-[#173978]"><i class="fa-solid fa-envelope-open-text mr-2"></i>Email Content</h3>
                        <button type="button" id="btn-preview" class="text-sm font-bold text-[#173978] bg-[#2fcaf0]/20 px-3 py-1.5 rounded-lg hover:bg-[#2fcaf0]"><i class="fa-solid fa-eye mr-1"></i> Preview</button>
                    </div>
                    <textarea id="marketing-editor" name="body_html" class="w-full"><?php
                        echo htmlspecialchars(marketing_apply_placeholders($templates['business_insights']['html'], $defaultVars));
                    ?></textarea>
                    <p class="text-xs text-slate-400 mt-2">Use the editor to add images, links, offers, tables &amp; formatting. Templates are fully editable.</p>
                </div>

                <div id="send-progress-wrap" class="hidden bg-white rounded-2xl border p-5">
                    <p id="send-progress-label" class="text-sm font-bold text-[#173978] mb-2">Preparing send...</p>
                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                        <div id="send-progress-bar" class="bg-[#2fcaf0] h-3 rounded-full transition-all duration-300" style="width:0%"></div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border p-5 flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
                    <div class="flex-1">
                        <label class="text-xs font-bold text-slate-500 uppercase">Send test to</label>
                        <input type="email" id="test-email" class="w-full mt-1 border rounded-lg p-2.5 text-sm" value="marketing@bisanibrother.com" placeholder="your@email.com">
                        <p class="text-xs text-slate-400 mt-1">Subject will be prefixed with [TEST]. Only this address receives the email.</p>
                    </div>
                    <button type="button" id="btn-send-test" class="shrink-0 bg-slate-100 hover:bg-[#2fcaf0] text-[#173978] font-bold px-6 py-3 rounded-xl transition-all">
                        <i class="fa-solid fa-vial mr-2"></i> Send Test Email
                    </button>
                </div>

                <button type="submit" id="btn-send-campaign" class="w-full bg-[#173978] hover:bg-[#122c5e] text-white font-bold py-4 rounded-xl text-lg shadow-lg transition-all">
                    <i class="fa-solid fa-paper-plane mr-2"></i> Send Campaign via marketing@bisanibrother.com
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</main>

<div id="preview-modal" class="hidden fixed inset-0 bg-black/60 z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[90vh] flex flex-col shadow-2xl">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="font-bold text-[#173978]">Email Preview</h3>
            <button type="button" id="btn-close-preview" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
        </div>
        <iframe id="preview-frame" class="flex-1 w-full min-h-[500px] border-0"></iframe>
    </div>
</div>

<script>
window.BB_MARKETING_TEMPLATES = <?php echo json_encode($templatesJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?>;
window.BB_MARKETING_DEFAULTS = <?php echo json_encode($defaultVars, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?>;
window.BB_CSRF_TOKEN = <?php echo json_encode(security_csrf_token(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
window.BB_MARKETING_PREVIEW_WRAP = function(body, email) {
    return <?php echo json_encode(marketing_wrap_email('__BODY__', 'preview@bisanibrother.com'), JSON_HEX_TAG); ?>.replace('__BODY__', body);
};
</script>
<?php bb_ckeditor_admin_scripts('js/marketing-editor.js'); ?>
</body>
</html>
