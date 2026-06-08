<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin', 'marketer']);

require '../db.php';
require_once __DIR__ . '/../includes/assets.php';

admin_handle_post_action(function (int $id) use ($pdo) {
    $pdo->prepare('DELETE FROM newsletter_subscribers WHERE id=?')->execute([$id]);
}, 'newsletter.php?msg=Deleted', 'delete');
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="newsletter-subscribers.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Status', 'Subscribed At']);
    $rows = $pdo->query("SELECT email, status, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC");
    while ($r = $rows->fetch(PDO::FETCH_NUM)) { fputcsv($out, $r); }
    fclose($out);
    exit();
}

$subs = $pdo->query('SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC')->fetchAll();
$activeCount = (int) $pdo->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status='active'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Newsletter | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden">
<?php include 'includes/sidebar.php'; ?>
<main class="flex-1 overflow-y-auto">
    <header class="bg-white border-b px-8 h-20 flex items-center justify-between sticky top-0 z-10">
        <div>
            <h2 class="text-2xl font-bold text-[#173978]">Newsletter Subscribers</h2>
            <p class="text-xs text-slate-500"><?php echo $activeCount; ?> active · Select & send campaigns</p>
        </div>
        <div class="flex gap-2">
            <button type="button" id="btn-send-selected" class="text-sm font-bold bg-[#2fcaf0] text-[#173978] px-4 py-2 rounded-lg hover:bg-[#173978] hover:text-white disabled:opacity-40" disabled>
                <i class="fa-solid fa-paper-plane mr-1"></i> Campaign to Selected
            </button>
            <a href="marketing-campaigns.php" class="text-sm font-bold bg-[#173978] text-white px-4 py-2 rounded-lg hover:bg-[#2fcaf0] hover:text-[#173978]">
                <i class="fa-solid fa-bullhorn mr-1"></i> Marketing Campaigns
            </a>
            <a href="newsletter.php?export=csv" class="text-sm font-bold border border-slate-200 px-4 py-2 rounded-lg hover:bg-slate-50"><i class="fa-solid fa-download mr-1"></i> Export</a>
        </div>
    </header>
    <div class="p-8 max-w-5xl mx-auto">
        <?php if (isset($_GET['msg'])): ?><div class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg text-sm"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
        <div class="bg-white rounded-2xl border overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left">
                    <tr>
                        <th class="p-4 w-10"><input type="checkbox" id="check-all" class="rounded"></th>
                        <th class="p-4 font-bold text-slate-500">Email</th>
                        <th class="p-4 font-bold text-slate-500">Status</th>
                        <th class="p-4 font-bold text-slate-500">Date</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($subs as $s): ?>
                <tr class="border-t border-slate-100 <?php echo $s['status'] !== 'active' ? 'opacity-60' : ''; ?>">
                    <td class="p-4">
                        <?php if ($s['status'] === 'active'): ?>
                        <input type="checkbox" class="sub-check rounded" value="<?php echo (int) $s['id']; ?>">
                        <?php endif; ?>
                    </td>
                    <td class="p-4 font-medium text-[#173978]"><?php echo htmlspecialchars($s['email']); ?></td>
                    <td class="p-4"><span class="text-xs font-bold px-2 py-0.5 rounded <?php echo $s['status'] === 'active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'; ?>"><?php echo htmlspecialchars($s['status']); ?></span></td>
                    <td class="p-4 text-slate-500"><?php echo date('M d, Y', strtotime($s['subscribed_at'])); ?></td>
                    <td class="p-4 text-right"><?php echo admin_post_button('newsletter.php', (int) $s['id'], 'delete', '<span class="text-red-400 hover:text-red-600"><i class="fa-solid fa-trash"></i></span>', 'Remove subscriber?'); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$subs): ?><tr><td colspan="5" class="p-8 text-center text-slate-400">No subscribers yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script>
(function(){
    var checks = document.querySelectorAll('.sub-check');
    var all = document.getElementById('check-all');
    var btn = document.getElementById('btn-send-selected');
    function updateBtn(){
        var n = document.querySelectorAll('.sub-check:checked').length;
        btn.disabled = n === 0;
        btn.innerHTML = n ? '<i class="fa-solid fa-paper-plane mr-1"></i> Campaign to ' + n + ' Selected' : '<i class="fa-solid fa-paper-plane mr-1"></i> Campaign to Selected';
    }
    checks.forEach(function(c){ c.addEventListener('change', updateBtn); });
    if(all) all.addEventListener('change', function(){
        checks.forEach(function(c){ c.checked = all.checked; });
        updateBtn();
    });
    btn.addEventListener('click', function(){
        var ids = Array.from(document.querySelectorAll('.sub-check:checked')).map(function(c){ return c.value; });
        if(ids.length) window.location.href = 'marketing-campaigns.php?selected=' + ids.join(',');
    });
})();
</script>
</body>
</html>
