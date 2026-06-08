<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin', 'writer']);

require '../db.php';
require_once __DIR__ . '/../includes/assets.php';

admin_handle_post_action(function (int $id) use ($pdo) {
    $pdo->prepare('DELETE FROM site_faqs WHERE id=?')->execute([$id]);
}, 'faqs-admin.php?msg=Deleted', 'delete');
$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM site_faqs WHERE id=?');
    $s->execute([(int) $_GET['edit']]);
    $edit = $s->fetch();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $fields = [trim($_POST['category'] ?? 'General'), trim($_POST['question'] ?? ''), trim($_POST['answer'] ?? ''), (int) ($_POST['sort_order'] ?? 0), !empty($_POST['is_active']) ? 1 : 0];
    if ($id) {
        $pdo->prepare('UPDATE site_faqs SET category=?, question=?, answer=?, sort_order=?, is_active=? WHERE id=?')->execute([...$fields, $id]);
    } else {
        $pdo->prepare('INSERT INTO site_faqs (category, question, answer, sort_order, is_active) VALUES (?,?,?,?,?)')->execute($fields);
    }
    header('Location: faqs-admin.php?msg=Saved'); exit();
}
$list = $pdo->query('SELECT * FROM site_faqs ORDER BY sort_order ASC, id ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage FAQs | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden">
<?php include 'includes/sidebar.php'; ?>
<main class="flex-1 overflow-y-auto">
    <header class="bg-white border-b px-8 h-20 flex items-center justify-between sticky top-0">
        <h2 class="text-2xl font-bold text-[#173978]">Site FAQs</h2>
        <a href="faqs-admin.php" class="text-sm font-bold bg-[#2fcaf0] text-[#173978] px-4 py-2 rounded-lg">+ Add FAQ</a>
    </header>
    <div class="p-8 max-w-6xl mx-auto grid lg:grid-cols-12 gap-8">
        <div class="lg:col-span-5 space-y-2 max-h-[70vh] overflow-y-auto">
            <?php foreach ($list as $f): ?>
            <div class="bg-white p-3 rounded-xl border text-sm flex justify-between gap-2">
                <div><span class="text-[10px] font-bold text-[#2fcaf0] uppercase"><?php echo htmlspecialchars($f['category']); ?></span><p class="font-semibold text-[#173978] line-clamp-2"><?php echo htmlspecialchars($f['question']); ?></p></div>
                <div class="shrink-0 flex gap-1"><a href="faqs-admin.php?edit=<?php echo $f['id']; ?>" class="text-blue-500"><i class="fa-solid fa-pen"></i></a><?php echo admin_post_button('faqs-admin.php', (int) $f['id'], 'delete', '<span class="text-red-400"><i class="fa-solid fa-trash"></i></span>', 'Delete?'); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="lg:col-span-7 bg-white rounded-2xl border p-8">
            <form method="POST" class="space-y-4">
                <?php echo security_csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int) ($edit['id'] ?? 0); ?>">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="text-xs font-bold text-slate-400 uppercase">Category</label><input name="category" value="<?php echo htmlspecialchars($edit['category'] ?? 'General'); ?>" class="w-full border rounded-lg px-3 py-2 text-sm"></div>
                    <div><label class="text-xs font-bold text-slate-400 uppercase">Sort</label><input type="number" name="sort_order" value="<?php echo (int) ($edit['sort_order'] ?? 0); ?>" class="w-full border rounded-lg px-3 py-2 text-sm"></div>
                </div>
                <div><label class="text-xs font-bold text-slate-400 uppercase">Question</label><input name="question" value="<?php echo htmlspecialchars($edit['question'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 text-sm" required></div>
                <div><label class="text-xs font-bold text-slate-400 uppercase">Answer</label><textarea name="answer" rows="5" class="w-full border rounded-lg px-3 py-2 text-sm" required><?php echo htmlspecialchars($edit['answer'] ?? ''); ?></textarea></div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" <?php echo empty($edit) || !empty($edit['is_active']) ? 'checked' : ''; ?>> Active on /faqs</label>
                <button type="submit" class="bg-[#173978] text-white px-6 py-2.5 rounded-lg font-bold">Save FAQ</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
