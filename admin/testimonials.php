<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin', 'marketer']);

require '../db.php';
require_once __DIR__ . '/../includes/assets.php';

$msg = '';
$edit = null;

admin_handle_post_action(function (int $id) use ($pdo) {
    $pdo->prepare('DELETE FROM testimonials WHERE id=?')->execute([$id]);
}, 'testimonials.php?msg=Deleted', 'delete');
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM testimonials WHERE id=?');
    $s->execute([(int) $_GET['edit']]);
    $edit = $s->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $data = [
        trim($_POST['name'] ?? ''),
        trim($_POST['role_title'] ?? ''),
        trim($_POST['company'] ?? ''),
        trim($_POST['quote'] ?? ''),
        trim($_POST['service_line'] ?? ''),
        max(1, min(5, (int) ($_POST['rating'] ?? 5))),
        !empty($_POST['is_active']) ? 1 : 0,
        (int) ($_POST['sort_order'] ?? 0),
    ];
    if ($id) {
        $pdo->prepare('UPDATE testimonials SET name=?, role_title=?, company=?, quote=?, service_line=?, rating=?, is_active=?, sort_order=? WHERE id=?')
            ->execute([...$data, $id]);
        $msg = 'Updated';
    } else {
        $pdo->prepare('INSERT INTO testimonials (name, role_title, company, quote, service_line, rating, is_active, sort_order) VALUES (?,?,?,?,?,?,?,?)')
            ->execute($data);
        $msg = 'Added';
    }
    header('Location: testimonials.php?msg=' . urlencode($msg)); exit();
}

$list = $pdo->query('SELECT * FROM testimonials ORDER BY sort_order ASC, id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Testimonials | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden">
<?php include 'includes/sidebar.php'; ?>
<main class="flex-1 overflow-y-auto">
    <header class="bg-white border-b px-8 h-20 flex items-center justify-between sticky top-0 z-10">
        <h2 class="text-2xl font-bold text-[#173978]">Testimonials</h2>
        <a href="testimonials.php" class="text-sm font-bold bg-[#2fcaf0] text-[#173978] px-4 py-2 rounded-lg">+ Add</a>
    </header>
    <div class="p-8 max-w-6xl mx-auto grid lg:grid-cols-12 gap-8">
        <div class="lg:col-span-5 space-y-2">
            <?php if (isset($_GET['msg'])): ?><div class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg text-sm"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
            <?php foreach ($list as $t): ?>
            <div class="bg-white p-4 rounded-xl border border-slate-100 flex justify-between gap-2">
                <div>
                    <p class="font-bold text-[#173978] text-sm"><?php echo htmlspecialchars($t['company']); ?></p>
                    <p class="text-xs text-slate-500 line-clamp-2"><?php echo htmlspecialchars($t['quote']); ?></p>
                </div>
                <div class="flex gap-1 shrink-0">
                    <a href="testimonials.php?edit=<?php echo $t['id']; ?>" class="text-blue-500 px-1"><i class="fa-solid fa-pen"></i></a>
                    <?php echo admin_post_button('testimonials.php', (int) $t['id'], 'delete', '<span class="text-red-400 px-1"><i class="fa-solid fa-trash"></i></span>', 'Delete?'); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="lg:col-span-7 bg-white rounded-2xl border p-8">
            <h3 class="font-bold text-[#173978] mb-6"><?php echo $edit ? 'Edit' : 'Add'; ?> Testimonial</h3>
            <form method="POST" class="space-y-4">
                <?php echo security_csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int) ($edit['id'] ?? 0); ?>">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="text-xs font-bold text-slate-400 uppercase">Name / Label</label><input name="name" value="<?php echo htmlspecialchars($edit['name'] ?? 'Client Partner'); ?>" class="w-full border rounded-lg px-3 py-2 text-sm" required></div>
                    <div><label class="text-xs font-bold text-slate-400 uppercase">Role</label><input name="role_title" value="<?php echo htmlspecialchars($edit['role_title'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 text-sm"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="text-xs font-bold text-slate-400 uppercase">Company</label><input name="company" value="<?php echo htmlspecialchars($edit['company'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 text-sm" required></div>
                    <div><label class="text-xs font-bold text-slate-400 uppercase">Service Line</label><input name="service_line" value="<?php echo htmlspecialchars($edit['service_line'] ?? ''); ?>" placeholder="FinTech, Staffing…" class="w-full border rounded-lg px-3 py-2 text-sm"></div>
                </div>
                <div><label class="text-xs font-bold text-slate-400 uppercase">Quote</label><textarea name="quote" rows="4" class="w-full border rounded-lg px-3 py-2 text-sm" required><?php echo htmlspecialchars($edit['quote'] ?? ''); ?></textarea></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="text-xs font-bold text-slate-400 uppercase">Rating (1-5)</label><input type="number" name="rating" min="1" max="5" value="<?php echo (int) ($edit['rating'] ?? 5); ?>" class="w-full border rounded-lg px-3 py-2 text-sm"></div>
                    <div><label class="text-xs font-bold text-slate-400 uppercase">Sort Order</label><input type="number" name="sort_order" value="<?php echo (int) ($edit['sort_order'] ?? 0); ?>" class="w-full border rounded-lg px-3 py-2 text-sm"></div>
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" <?php echo empty($edit) || !empty($edit['is_active']) ? 'checked' : ''; ?>> Show on homepage</label>
                <button type="submit" class="bg-[#173978] text-white px-6 py-2.5 rounded-lg font-bold hover:bg-[#2fcaf0] hover:text-[#173978]">Save</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
