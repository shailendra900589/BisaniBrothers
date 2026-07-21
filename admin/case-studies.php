<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin', 'writer']);

require '../db.php';
require_once __DIR__ . '/../includes/assets.php';
require_once '../includes/case-study-helpers.php';
require_once '../includes/seo.php';

$msg = '';
$edit = null;

admin_handle_post_action(function (int $id) use ($pdo) {
    $pdo->prepare('DELETE FROM case_studies WHERE id=?')->execute([$id]);
    seo_ping_after_case_study_change($pdo);
}, 'case-studies.php?msg=Deleted', 'delete');
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM case_studies WHERE id=?');
    $s->execute([(int) $_GET['edit']]);
    $edit = $s->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    require_once __DIR__ . '/../includes/admin-schema.php';
    admin_ensure_seo_text_columns($pdo, 'case_studies');

    $id = (int) ($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $slug = case_study_ensure_unique_slug($pdo, case_study_make_slug($title, $id ?: null), $id ?: null);
    $locale = 'en';
    $image_path = $_POST['existing_image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $uploadError = security_validate_upload($_FILES['image'], ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if ($uploadError) {
            $msg = 'Error: ' . $uploadError;
        } else {
            $uploadDir = dirname(__DIR__) . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = security_safe_upload_name((string) $_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                $image_path = 'uploads/' . $fileName;
            }
        }
    }

    if ($msg === '') {
        try {
            $fields = [
                admin_fit_column_text($pdo, 'case_studies', 'title', $title),
                admin_fit_column_text($pdo, 'case_studies', 'slug', $slug),
                admin_fit_column_text($pdo, 'case_studies', 'client_name', trim($_POST['client_name'] ?? '')),
                admin_fit_column_text($pdo, 'case_studies', 'industry', trim($_POST['industry'] ?? '')),
                admin_fit_column_text($pdo, 'case_studies', 'service_line', trim($_POST['service_line'] ?? '')),
                $_POST['challenge'] ?? '',
                $_POST['approach'] ?? '',
                $_POST['results'] ?? '',
                admin_fit_column_text($pdo, 'case_studies', 'quote', trim($_POST['quote'] ?? '')),
                $_POST['content'] ?? '',
                admin_fit_column_text($pdo, 'case_studies', 'image_path', $image_path),
                admin_fit_column_text($pdo, 'case_studies', 'meta_title', trim($_POST['meta_title'] ?? '')),
                admin_fit_column_text($pdo, 'case_studies', 'meta_desc', trim($_POST['meta_desc'] ?? '')),
                admin_fit_column_text($pdo, 'case_studies', 'keywords', trim($_POST['keywords'] ?? '')),
                !empty($_POST['is_published']) ? 1 : 0,
                $locale,
            ];
            if ($id) {
                $pdo->prepare('UPDATE case_studies SET title=?, slug=?, client_name=?, industry=?, service_line=?, challenge=?, approach=?, results=?, quote=?, content=?, image_path=?, meta_title=?, meta_desc=?, keywords=?, is_published=?, locale=? WHERE id=?')
                    ->execute([...$fields, $id]);
            } else {
                $pdo->prepare('INSERT INTO case_studies (title, slug, client_name, industry, service_line, challenge, approach, results, quote, content, image_path, meta_title, meta_desc, keywords, is_published, locale) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute($fields);
            }
            seo_ping_after_case_study_change($pdo);
            header('Location: case-studies.php?msg=Saved');
            exit();
        } catch (PDOException $e) {
            error_log('case-studies save: ' . $e->getMessage());
            $msg = 'Error: Could not save the case study. ' . $e->getMessage();
        }
    }
}

$list = $pdo->query('SELECT id, title, slug, industry, is_published, created_at FROM case_studies ORDER BY created_at DESC')->fetchAll();
$editUrl = !empty($edit['slug']) ? case_study_post_url($edit['slug']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Case Studies | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden">
<?php include 'includes/sidebar.php'; ?>
<main class="flex-1 overflow-y-auto">
    <header class="bg-white border-b px-8 h-20 flex items-center justify-between sticky top-0 z-10">
        <h2 class="text-2xl font-bold text-[#173978]">Case Studies</h2>
        <a href="case-studies.php" class="text-sm font-bold bg-[#2fcaf0] text-[#173978] px-4 py-2 rounded-lg">+ New</a>
    </header>
    <div class="p-8 max-w-7xl mx-auto grid lg:grid-cols-12 gap-8">
        <div class="lg:col-span-4 space-y-2 max-h-[75vh] overflow-y-auto">
            <?php if (isset($_GET['msg'])): ?><div class="p-3 bg-green-50 text-green-700 rounded-lg text-sm mb-2"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
            <?php foreach ($list as $c): ?>
            <div class="bg-white p-3 rounded-xl border flex justify-between gap-2">
                <div>
                    <p class="font-bold text-[#173978] text-sm"><?php echo htmlspecialchars($c['title']); ?></p>
                    <p class="text-xs text-slate-400"><?php echo htmlspecialchars($c['industry'] ?? ''); ?> · <?php echo $c['is_published'] ? 'Published' : 'Draft'; ?></p>
                </div>
                <div class="flex gap-1 shrink-0">
                    <a href="case-studies.php?edit=<?php echo $c['id']; ?>" class="text-blue-500 px-1"><i class="fa-solid fa-pen"></i></a>
                    <?php echo admin_post_button('case-studies.php', (int) $c['id'], 'delete', '<span class="text-red-400 px-1"><i class="fa-solid fa-trash"></i></span>', 'Delete?'); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="lg:col-span-8 bg-white rounded-2xl border p-8">
            <h3 class="font-bold text-[#173978] mb-6"><?php echo $edit ? 'Edit' : 'New'; ?> Case Study</h3>
            <?php if ($msg): ?><div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
            <?php if ($editUrl): ?>
            <div class="mb-4 p-3 bg-slate-50 rounded-lg text-sm font-mono flex gap-2 items-center">
                <span class="flex-1 truncate"><?php echo htmlspecialchars($editUrl); ?></span>
                <a href="<?php echo htmlspecialchars($editUrl); ?>" target="_blank" class="text-[#173978]"><i class="fa-solid fa-external-link"></i></a>
            </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <?php echo security_csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int) ($edit['id'] ?? 0); ?>">
                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($edit['image_path'] ?? ''); ?>">
                <input type="text" name="title" placeholder="Case study title" value="<?php echo htmlspecialchars($edit['title'] ?? ''); ?>" class="w-full border rounded-lg px-4 py-3 font-bold text-[#173978]" required>
                <input type="hidden" name="locale" value="en">
                <div class="grid grid-cols-3 gap-4">
                    <input type="text" name="client_name" placeholder="Client (optional)" value="<?php echo htmlspecialchars($edit['client_name'] ?? ''); ?>" class="border rounded-lg px-3 py-2 text-sm">
                    <input type="text" name="industry" placeholder="Industry" value="<?php echo htmlspecialchars($edit['industry'] ?? ''); ?>" class="border rounded-lg px-3 py-2 text-sm">
                    <input type="text" name="service_line" placeholder="Service line" value="<?php echo htmlspecialchars($edit['service_line'] ?? ''); ?>" class="border rounded-lg px-3 py-2 text-sm">
                </div>
                <div><label class="text-xs font-bold text-slate-400 uppercase">Challenge</label><textarea name="challenge" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm"><?php echo htmlspecialchars($edit['challenge'] ?? ''); ?></textarea></div>
                <div><label class="text-xs font-bold text-slate-400 uppercase">Approach</label><textarea name="approach" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm"><?php echo htmlspecialchars($edit['approach'] ?? ''); ?></textarea></div>
                <div><label class="text-xs font-bold text-slate-400 uppercase">Results</label><textarea name="results" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm"><?php echo htmlspecialchars($edit['results'] ?? ''); ?></textarea></div>
                <input type="text" name="quote" placeholder="Client quote (optional)" value="<?php echo htmlspecialchars($edit['quote'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 text-sm">
                <div><label class="text-xs font-bold text-slate-400 uppercase">Additional content (HTML ok)</label><textarea name="content" rows="4" class="w-full border rounded-lg px-3 py-2 text-sm"><?php echo htmlspecialchars($edit['content'] ?? ''); ?></textarea></div>
                <input type="file" name="image" accept="image/*" class="text-sm">
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" name="meta_title" placeholder="SEO title" value="<?php echo htmlspecialchars($edit['meta_title'] ?? ''); ?>" class="border rounded-lg px-3 py-2 text-sm">
                    <input type="text" name="keywords" placeholder="Keywords" value="<?php echo htmlspecialchars($edit['keywords'] ?? ''); ?>" class="border rounded-lg px-3 py-2 text-sm">
                </div>
                <textarea name="meta_desc" rows="2" placeholder="Meta description" class="w-full border rounded-lg px-3 py-2 text-sm"><?php echo htmlspecialchars($edit['meta_desc'] ?? ''); ?></textarea>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_published" value="1" <?php echo empty($edit) || !empty($edit['is_published']) ? 'checked' : ''; ?>> Published</label>
                <button type="submit" class="bg-[#173978] text-white px-8 py-3 rounded-xl font-bold hover:bg-[#2fcaf0] hover:text-[#173978]">Save Case Study</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
