<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin', 'marketer']);

require '../db.php';
require_once __DIR__ . '/../includes/assets.php';

$msg = '';
$edit_data = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['launch_id'])) {
    $launchId = (int) $_POST['launch_id'];
    $pdo->query('UPDATE popups SET is_active = 0');
    $pdo->prepare('UPDATE popups SET is_active = 1 WHERE id=?')->execute([$launchId]);
    header('Location: popups.php?msg=Launched');
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['stop_id'])) {
    $pdo->prepare('UPDATE popups SET is_active = 0 WHERE id=?')->execute([(int) $_POST['stop_id']]);
    header('Location: popups.php?msg=Stopped');
    exit;
}
admin_handle_post_action(function (int $id) use ($pdo) {
    $pdo->prepare('DELETE FROM popups WHERE id=?')->execute([$id]);
}, 'popups.php?msg=Deleted', 'delete');

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM popups WHERE id=?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit_data = $stmt->fetch();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && !isset($_POST['launch_id'])
    && !isset($_POST['stop_id'])
    && !isset($_POST['delete'])) {
    $id = $_POST['id'] ?? '';
    $title = $_POST['title'] ?? '';
    $content = security_sanitize_rich_html($_POST['content'] ?? '');
    $btn_text = $_POST['btn_text'] ?? '';
    $btn_link = $_POST['btn_link'] ?? '';
    $image_path = $_POST['existing_image'] ?? '';

    if (!empty($_FILES['image']['name'])) {
        $uploadError = security_validate_upload($_FILES['image'], ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if ($uploadError) {
            $msg = 'Error: ' . $uploadError;
        } else {
            $fileName = security_safe_upload_name((string) $_FILES['image']['name']);
            $target = dirname(__DIR__) . '/uploads/' . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $image_path = 'uploads/' . $fileName;
            } else {
                $msg = 'Error: Failed to upload image.';
            }
        }
    }

    if (strpos((string) $msg, 'Error') === false) {
        if ($id) {
            $sql = 'UPDATE popups SET title=?, content=?, image_path=?, btn_text=?, btn_link=? WHERE id=?';
            $pdo->prepare($sql)->execute([$title, $content, $image_path, $btn_text, $btn_link, $id]);
            $msg = 'Popup Updated';
        } else {
            $sql = 'INSERT INTO popups (title, content, image_path, btn_text, btn_link, is_active) VALUES (?, ?, ?, ?, ?, 0)';
            $pdo->prepare($sql)->execute([$title, $content, $image_path, $btn_text, $btn_link]);
            $msg = 'Popup Created';
        }
    }
}
$popups = $pdo->query('SELECT * FROM popups ORDER BY id DESC')->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Popups | Bisani Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
</head>
<body class="admin-popups bg-slate-50 text-slate-800 flex h-screen overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto relative">
        <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 border-b border-slate-200 px-8 h-20 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-[#173978]">Site Pop-up Manager</h2>
            <a href="popups.php" class="bg-[#2fcaf0] text-[#173978] px-5 py-2 rounded-lg font-bold hover:bg-[#173978] hover:text-white transition-all shadow-md text-sm"><i class="fa-solid fa-plus mr-2"></i> New Campaign</a>
        </header>

        <div class="p-8 max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <div class="lg:col-span-4 flex flex-col gap-4 h-[calc(100vh-140px)]">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col h-full">
                    <div class="p-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <span class="font-bold text-slate-700 text-sm uppercase">Saved Campaigns</span>
                        <span class="text-xs bg-[#173978] text-white px-2 py-0.5 rounded-full"><?php echo count($popups); ?></span>
                    </div>
                    <div class="overflow-y-auto flex-1 p-2 space-y-1">
                        <?php foreach($popups as $p): ?>
                        <div class="p-4 rounded-xl border <?php echo $p['is_active'] ? 'border-green-300 bg-green-50' : 'border-transparent hover:bg-slate-50'; ?> transition-all group relative">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-bold text-[#173978] text-sm truncate"><?php echo $p['title']; ?></h4>
                                <?php if($p['is_active']): ?>
                                    <span class="text-[10px] font-bold text-green-700 bg-white px-2 py-0.5 rounded shadow-sm animate-pulse">LIVE</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex gap-2">
                                <?php if ($p['is_active']): ?>
                                    <form method="POST" action="popups.php" class="flex-1">
                                        <?php echo security_csrf_field(); ?>
                                        <input type="hidden" name="stop_id" value="<?php echo (int) $p['id']; ?>">
                                        <button type="submit" class="w-full text-center bg-white border border-slate-200 text-orange-500 text-xs font-bold py-1.5 rounded hover:bg-orange-50">STOP</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="popups.php" class="flex-1">
                                        <?php echo security_csrf_field(); ?>
                                        <input type="hidden" name="launch_id" value="<?php echo (int) $p['id']; ?>">
                                        <button type="submit" class="w-full text-center bg-[#173978] text-white text-xs font-bold py-1.5 rounded hover:bg-[#2fcaf0] hover:text-[#173978]">LAUNCH</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <div class="absolute top-2 right-2 hidden group-hover:flex gap-1 bg-white shadow-sm p-1 rounded border">
                                <a href="popups.php?edit=<?php echo (int) $p['id']; ?>" class="text-blue-500 px-1"><i class="fa-solid fa-pen"></i></a>
                                <?php echo admin_post_button('popups.php', (int) $p['id'], 'delete', '<span class="text-red-400 px-1"><i class="fa-solid fa-trash"></i></span>', 'Delete this campaign?'); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-8">
                    <h3 class="text-xl font-bold text-[#173978] mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-bullhorn text-[#2fcaf0]"></i> <?php echo $edit_data ? 'Edit Campaign' : 'Create New Campaign'; ?>
                    </h3>

                    <?php if ($msg || isset($_GET['msg'])) echo "<div class='mb-6 p-4 bg-green-50 text-green-700 rounded-lg border border-green-200'>" . security_e($msg ?: ($_GET['msg'] ?? '')) . "</div>"; ?>

                    <form method="POST" enctype="multipart/form-data" onsubmit="document.getElementById('hidden-content').value = document.getElementById('editor').innerHTML;">
                        <?php echo security_csrf_field(); ?>
                        <input type="hidden" name="id" value="<?php echo $edit_data['id'] ?? ''; ?>">
                        <input type="hidden" name="existing_image" value="<?php echo $edit_data['image_path'] ?? ''; ?>">

                        <div class="mb-6">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Headline</label>
                            <input type="text" name="title" value="<?php echo $edit_data['title'] ?? ''; ?>" class="w-full text-lg font-bold text-[#173978] border-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-[#2fcaf0]" placeholder="e.g. We are expanding to Delhi!" required>
                        </div>

                        <div class="mb-6">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Popup Message</label>
                            <div class="toolbar">
                                <button type="button" onmousedown="event.preventDefault(); document.execCommand('bold');"><i class="fa-solid fa-bold"></i></button>
                                <button type="button" onmousedown="event.preventDefault(); document.execCommand('italic');"><i class="fa-solid fa-italic"></i></button>
                            </div>
                            <div id="editor" class="custom-editor" contenteditable="true">
                                <?php echo $edit_data['content'] ?? '<p>Write your announcement here...</p>'; ?>
                            </div>
                            <textarea name="content" id="hidden-content" class="hidden"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Button Label</label>
                                <input type="text" name="btn_text" value="<?php echo $edit_data['btn_text'] ?? ''; ?>" class="w-full border-slate-200 rounded-lg px-4 py-2.5 text-sm" placeholder="e.g. Contact Us">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Button Link</label>
                                <input type="text" name="btn_link" value="<?php echo $edit_data['btn_link'] ?? ''; ?>" class="w-full border-slate-200 rounded-lg px-4 py-2.5 text-sm" placeholder="e.g. contact.php">
                            </div>
                        </div>

                        <div class="mb-8">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Banner Image</label>
                            <input type="file" name="image" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                            <?php if(isset($edit_data['image_path']) && $edit_data['image_path']): ?>
                                <p class="text-xs text-blue-500 mt-2">Currently: <?php echo basename($edit_data['image_path']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="flex justify-end gap-4">
                            <a href="popups.php" class="px-6 py-3 rounded-lg font-bold text-slate-500 hover:bg-slate-100 transition">Cancel</a>
                            <button type="submit" class="bg-gradient-to-r from-[#173978] to-blue-800 text-white px-8 py-3 rounded-xl font-bold hover:shadow-lg transition-all">
                                <?php echo $edit_data ? 'Update Campaign' : 'Save Campaign'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>