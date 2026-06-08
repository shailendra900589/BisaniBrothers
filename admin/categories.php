<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin']);

require '../db.php';
require_once __DIR__ . '/../includes/assets.php';

admin_handle_post_action(function (int $id) use ($pdo) {
    $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
}, 'categories.php', 'delete');
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pdo->prepare("INSERT INTO categories (name, type) VALUES (?, ?)")->execute([$_POST['name'], $_POST['type']]);
}
$cats = $pdo->query("SELECT * FROM categories ORDER BY type, name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Categories | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto relative">
        <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 border-b border-slate-200 px-8 h-20 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-[#173978]">Category Management</h2>
        </header>

        <div class="p-8 max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="font-bold text-[#173978] mb-4">Existing Categories</h3>
                <ul class="space-y-2">
                    <?php foreach($cats as $c): ?>
                    <li class="flex justify-between items-center border-b pb-2 last:border-0">
                        <span class="font-medium text-slate-700"><?php echo $c['name']; ?> 
                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded ml-2 <?php echo $c['type']=='blog'?'bg-blue-50 text-blue-600':'bg-orange-50 text-orange-600'; ?>">
                                <?php echo $c['type']; ?>
                            </span>
                        </span>
                        <?php echo admin_post_button('categories.php', (int) $c['id'], 'delete', '<span class="text-red-400 hover:text-red-600"><i class="fa-solid fa-trash"></i></span>', 'Delete category?'); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 h-fit">
                <h3 class="font-bold text-[#173978] mb-4">Add New Category</h3>
                <form method="POST" class="space-y-4">
                    <?php echo security_csrf_field(); ?>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Name</label>
                        <input type="text" name="name" class="w-full border rounded-lg px-3 py-2 text-sm focus:border-[#2fcaf0] outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Type</label>
                        <select name="type" class="w-full border rounded-lg px-3 py-2 text-sm bg-white focus:border-[#2fcaf0] outline-none">
                            <option value="blog">Blog Category</option>
                            <option value="job">Job Type</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-[#173978] text-white font-bold py-2.5 rounded-lg hover:bg-[#2fcaf0] transition">Add Category</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>