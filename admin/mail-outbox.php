<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin', 'marketer']);

require_once __DIR__ . '/../includes/assets.php';
require_once __DIR__ . '/../includes/mail-local.php';

$dir = mail_outbox_dir();
$files = glob($dir . '/*.html') ?: [];
usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

$view = basename((string) ($_GET['view'] ?? ''));
$viewPath = $dir . '/' . $view;
if ($view !== '' && is_file($viewPath) && str_starts_with(realpath($viewPath), realpath($dir))) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($viewPath);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['delete_file'])) {
    $del = basename((string) $_POST['delete_file']);
    @unlink($dir . '/' . $del);
    @unlink($dir . '/' . $del . '.json');
    header('Location: mail-outbox.php?msg=Deleted');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mail Outbox | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden">
<?php include 'includes/sidebar.php'; ?>
<main class="flex-1 overflow-y-auto">
    <header class="bg-white border-b px-8 h-20 flex items-center justify-between sticky top-0 z-10">
        <div>
            <h2 class="text-2xl font-bold text-[#173978]">Local Mail Outbox</h2>
            <p class="text-xs text-slate-500">Emails saved on localhost when SMTP is unavailable</p>
        </div>
        <a href="marketing-campaigns.php" class="text-sm font-bold bg-[#173978] text-white px-4 py-2 rounded-lg">← Campaigns</a>
    </header>
    <div class="p-8 max-w-4xl mx-auto">
        <?php if (isset($_GET['msg'])): ?><div class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg text-sm"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
        <div class="mb-4 p-4 bg-amber-50 border border-amber-100 rounded-xl text-sm text-amber-900">
            <i class="fa-solid fa-laptop-code mr-1"></i>
            On <strong>localhost</strong>, test emails are saved here instead of being sent over the internet.
            On live server, real SMTP is used automatically.
        </div>
        <div class="bg-white rounded-2xl border overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left">
                    <tr>
                        <th class="p-4 font-bold text-slate-500">Saved At</th>
                        <th class="p-4 font-bold text-slate-500">Subject</th>
                        <th class="p-4 font-bold text-slate-500">To</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($files as $file):
                    $name = basename($file);
                    $metaFile = $file . '.json';
                    $meta = is_file($metaFile) ? json_decode((string) file_get_contents($metaFile), true) : [];
                ?>
                <tr class="border-t border-slate-100">
                    <td class="p-4 text-slate-500"><?php echo date('M d, Y H:i:s', filemtime($file)); ?></td>
                    <td class="p-4 font-medium text-[#173978]"><?php echo htmlspecialchars($meta['subject'] ?? $name); ?></td>
                    <td class="p-4 text-slate-600"><?php echo htmlspecialchars(implode(', ', (array) ($meta['to'] ?? []))); ?></td>
                    <td class="p-4 text-right whitespace-nowrap">
                        <a href="mail-outbox.php?view=<?php echo urlencode($name); ?>" target="_blank" class="text-[#173978] font-bold mr-3">Preview</a>
                        <form method="POST" action="mail-outbox.php" class="inline" onsubmit="return confirm('Delete?')">
                            <?php echo security_csrf_field(); ?>
                            <input type="hidden" name="delete_file" value="<?php echo security_e($name); ?>">
                            <button type="submit" class="text-red-400 border-0 bg-transparent cursor-pointer p-0">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$files): ?>
                <tr><td colspan="4" class="p-8 text-center text-slate-400">No saved emails yet. Send a test from Email Campaigns.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
