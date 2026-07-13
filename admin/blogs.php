<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin', 'writer']);

require '../db.php';
require_once __DIR__ . '/../includes/assets.php';
require_once '../includes/blog-helpers.php';
require_once '../includes/seo.php';

blog_admin_ensure_schema($pdo);

$msg = isset($_GET['msg']) ? (string) $_GET['msg'] : '';
$edit_data = null;
$listFilter = $_GET['filter'] ?? 'all';
if (!in_array($listFilter, ['all', 'public', 'orphan'], true)) {
    $listFilter = 'all';
}

admin_handle_post_action(function (int $id) use ($pdo) {
    try {
        $delStmt = $pdo->prepare('SELECT slug FROM blogs WHERE id=?');
        $delStmt->execute([$id]);
        $deletedSlug = $delStmt->fetchColumn();
        $pdo->prepare('DELETE FROM blogs WHERE id=?')->execute([$id]);
        if (is_file(dirname(__DIR__) . '/includes/blog-translate.php')) {
            require_once '../includes/blog-translate.php';
            blog_translate_cache_clear($id);
        }
        if ($deletedSlug) {
            register_shutdown_function(static function () use ($pdo, $deletedSlug): void {
                try {
                    require_once dirname(__DIR__) . '/includes/seo.php';
                    seo_ping_after_blog_change($pdo, (string) $deletedSlug);
                } catch (Throwable $e) {
                    error_log('Deferred blog delete SEO ping failed: ' . $e->getMessage());
                }
            });
        }
    } catch (Throwable $e) {
        error_log('Admin blog delete failed: ' . $e->getMessage());
        header('Location: blogs.php?msg=' . urlencode('Error: Could not delete the article.'));
        exit;
    }
}, 'blogs.php?msg=Deleted', 'delete');

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM blogs WHERE id=?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit_data = $stmt->fetch();
    if ($edit_data === false) {
        $edit_data = null;
    }
}

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
    $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
    $title = trim((string) ($_POST['title'] ?? ''));
    $customSlug = trim((string) ($_POST['custom_slug'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? 'Growth Strategy'));
    $meta_title = trim((string) ($_POST['meta_title'] ?? ''));
    $meta_desc = trim((string) ($_POST['meta_desc'] ?? ''));
    $keywords = trim((string) ($_POST['keywords'] ?? ''));
    $tags = trim((string) ($_POST['tags'] ?? ''));
    $faq_json = trim((string) ($_POST['faq_json'] ?? ''));
    $is_orphan = !empty($_POST['is_orphan']) ? 1 : 0;
    $locale = 'en';
    $oldSlug = null;
    $slug = '';
    $content = '';

    try {
        if (trim($title) === '') {
            throw new RuntimeException('Article title is required.');
        }

        $content = blog_normalize_content($_POST['content'] ?? '');
        if ($customSlug !== '') {
            $slug = blog_normalize_slug($customSlug);
        } else {
            $slug = blog_make_slug($title, $id);
        }

        $slugError = blog_validate_slug($slug);
        if ($slugError) {
            $msg = 'Error: ' . $slugError;
        } else {
            $slug = blog_ensure_unique_slug($pdo, $slug, $id, $locale);
        }

        if ($id) {
            $oldSlugStmt = $pdo->prepare('SELECT slug FROM blogs WHERE id = ?');
            $oldSlugStmt->execute([$id]);
            $oldSlug = $oldSlugStmt->fetchColumn() ?: null;
        }

        if ($slugError === null && $faq_json !== '') {
            $decodedFaq = json_decode($faq_json, true);
            if (!is_array($decodedFaq)) {
                $msg = 'Error: FAQ data is invalid. Please try again.';
            }
        }

        $image_path = (string) ($_POST['existing_image'] ?? '');

        if (!empty($_FILES['image']['name'])) {
            $upload = security_store_upload($_FILES['image'], '', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            if ($upload['ok']) {
                $image_path = $upload['db_path'];
            } else {
                $msg = 'Error: ' . $upload['error'];
            }
        }

        if (strpos($msg, 'Error') === false) {
            if ($keywords === '') {
                $keywords = seo_suggest_blog_keywords([
                    'title'    => $title,
                    'category' => $category,
                    'content'  => $content,
                    'keywords' => '',
                ]);
            }
            if ($tags === '' && $keywords !== '') {
                $tags = implode(', ', array_slice(explode(',', $keywords), 0, 6));
            }
            if ($faq_json === '') {
                $faq_json = null;
            }

            $savedId = blog_admin_save_post($pdo, $id, [
                'title'      => $title,
                'slug'       => $slug,
                'category'   => $category,
                'content'    => $content,
                'image_path' => $image_path,
                'meta_title' => $meta_title,
                'meta_desc'  => $meta_desc,
                'keywords'   => $keywords,
                'tags'       => $tags,
                'faq_json'   => $faq_json,
                'is_orphan'  => $is_orphan,
                'locale'     => $locale,
            ]);

            try {
                if (is_file(dirname(__DIR__) . '/includes/blog-translate.php')) {
                    require_once '../includes/blog-translate.php';
                    blog_translate_cache_clear($savedId);
                }
            } catch (Throwable $e) {
                error_log('Blog translation cache clear failed: ' . $e->getMessage());
            }

            $savedSlug = $slug;
            $successMsg = $id ? 'Blog Updated Successfully' : 'Blog Created Successfully';

            $pingSlugs = [[$savedSlug, (bool) $is_orphan]];
            if ($oldSlug && $oldSlug !== $savedSlug) {
                $pingSlugs[] = [$oldSlug, (bool) $is_orphan];
            }
            register_shutdown_function(static function () use ($pdo, $pingSlugs): void {
                try {
                    require_once dirname(__DIR__) . '/includes/seo.php';
                    foreach ($pingSlugs as [$pingSlug, $orphan]) {
                        seo_ping_after_blog_change($pdo, (string) $pingSlug, $orphan);
                    }
                } catch (Throwable $e) {
                    error_log('Deferred blog SEO ping failed: ' . $e->getMessage());
                }
            });

            $redirect = 'blogs.php?edit=' . $savedId . '&msg=' . urlencode($successMsg);
            if ($listFilter !== 'all') {
                $redirect .= '&filter=' . urlencode($listFilter);
            }
            header('Location: ' . $redirect);
            exit;
        } else {
            $edit_data = array_merge(is_array($edit_data) ? $edit_data : [], [
                'id'          => $id,
                'title'       => $title,
                'slug'        => $slug,
                'category'    => $category,
                'content'     => $content,
                'meta_title'  => $meta_title,
                'meta_desc'   => $meta_desc,
                'keywords'    => $keywords,
                'tags'        => $tags,
                'faq_json'    => $faq_json,
                'is_orphan'   => $is_orphan,
                'locale'      => $locale,
                'image_path'  => $image_path,
            ]);
        }
    } catch (Throwable $e) {
        error_log('Admin blog save failed: ' . $e->getMessage());
        $msg = 'Error: Could not save the article. ' . $e->getMessage();
        $edit_data = array_merge(is_array($edit_data) ? $edit_data : [], [
            'id'          => $id,
            'title'       => $title,
            'slug'        => $slug,
            'category'    => $category,
            'content'     => $content,
            'meta_title'  => $meta_title,
            'meta_desc'   => $meta_desc,
            'keywords'    => $keywords,
            'tags'        => $tags,
            'faq_json'    => $faq_json,
            'is_orphan'   => $is_orphan,
            'locale'      => $locale,
            'image_path'  => (string) ($_POST['existing_image'] ?? ''),
        ]);
    }
}

$blogs = [];
try {
    $blogs = $pdo->query(blog_admin_list_sql($pdo, $listFilter))->fetchAll();
} catch (PDOException $e) {
    error_log('Admin blogs list failed: ' . $e->getMessage());
    if ($msg === '') {
        $msg = 'Error: Could not load blog list. Please check the database connection.';
    }
}

$editPostUrl = '';
$siteBaseUrl = seo_site_url_rtrim();
if (!empty($edit_data['slug'])) {
    $editPostUrl = blog_post_url($edit_data['slug'], null, $edit_data['locale'] ?? 'en');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Blogs | Premium Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
</head>
<body class="admin-blogs bg-slate-50 text-slate-800 flex h-screen overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto relative">
        <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 border-b border-slate-200 px-8 h-20 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-[#173978]">Blog Management</h2>
            <a href="blogs.php" class="bg-[#2fcaf0] text-[#173978] px-5 py-2 rounded-lg font-bold hover:bg-[#173978] hover:text-white transition-all shadow-md text-sm"><i class="fa-solid fa-plus mr-2"></i> Create New</a>
        </header>

        <div class="p-8 max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <div class="lg:col-span-4 flex flex-col gap-4 h-[calc(100vh-140px)]">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col h-full">
                    <div class="p-4 border-b border-slate-100 bg-slate-50">
                        <div class="flex justify-between items-center mb-3">
                            <span class="font-bold text-slate-700 text-sm uppercase">Recent Posts</span>
                            <span class="text-xs bg-[#173978] text-white px-2 py-0.5 rounded-full"><?php echo count($blogs); ?></span>
                        </div>
                        <div class="flex gap-1 text-[10px] font-bold uppercase">
                            <a href="blogs.php" class="px-2 py-1 rounded <?php echo $listFilter === 'all' ? 'bg-[#173978] text-white' : 'bg-white text-slate-500 border'; ?>">All</a>
                            <a href="blogs.php?filter=public" class="px-2 py-1 rounded <?php echo $listFilter === 'public' ? 'bg-[#173978] text-white' : 'bg-white text-slate-500 border'; ?>">Public</a>
                            <a href="blogs.php?filter=orphan" class="px-2 py-1 rounded <?php echo $listFilter === 'orphan' ? 'bg-amber-500 text-white' : 'bg-white text-slate-500 border'; ?>">Orphan</a>
                        </div>
                    </div>
                    <div class="overflow-y-auto flex-1 p-2 space-y-1">
                        <?php foreach ($blogs as $b):
                            $bUrl = blog_post_url((string) ($b['slug'] ?? ''));
                            $isOrphan = !empty($b['is_orphan']);
                            $createdRaw = (string) ($b['created_at'] ?? '');
                            $createdLabel = $createdRaw !== '' ? date('M d', strtotime($createdRaw)) : '—';
                        ?>
                        <div class="p-3 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-all group relative">
                            <h4 class="font-bold text-[#173978] text-sm leading-tight mb-1 truncate pr-8"><?php echo htmlspecialchars($b['title']); ?></h4>
                            <p class="text-[10px] text-slate-400 font-mono truncate mb-1">/<?php echo htmlspecialchars($b['slug']); ?></p>
                            <div class="flex justify-between items-center gap-2">
                                <span class="text-xs text-slate-400"><?php echo htmlspecialchars($createdLabel); ?></span>
                                <?php if ($isOrphan): ?>
                                <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 rounded">Orphan</span>
                                <?php else: ?>
                                <span class="text-xs font-bold text-[#2fcaf0] bg-cyan-50 px-2 rounded"><?php echo htmlspecialchars($b['category']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="absolute top-3 right-3 hidden group-hover:flex gap-1 bg-white shadow-sm p-1 rounded-md border">
                                <button type="button" onclick="copyPostLink('<?php echo htmlspecialchars($bUrl, ENT_QUOTES); ?>')" class="text-slate-500 hover:text-[#173978] px-1" title="Copy link"><i class="fa-solid fa-link"></i></button>
                                <a href="blogs.php?edit=<?php echo $b['id']; ?>" class="text-blue-500 hover:text-blue-700 px-1"><i class="fa-solid fa-pen"></i></a>
                                <?php echo admin_post_button('blogs.php', (int) $b['id'], 'delete', '<span class="text-red-400 hover:text-red-600 px-1"><i class="fa-solid fa-trash"></i></span>', 'Delete this post?'); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-8">
                    <h3 class="text-xl font-bold text-[#173978] mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-pen-nib text-[#2fcaf0]"></i> <?php echo $edit_data ? 'Edit Article' : 'Compose New Article'; ?>
                    </h3>

                    <?php 
                        if($msg || isset($_GET['msg'])) {
                            $message = $msg ?: $_GET['msg'];
                            $color = (strpos($message, 'Error') !== false) ? 'red' : 'green';
                            echo "<div class='mb-6 p-4 bg-$color-50 text-$color-700 rounded-lg border border-$color-200 flex items-center'><i class='fa-solid fa-info-circle mr-3'></i> ".htmlspecialchars($message)."</div>"; 
                        }
                    ?>

                    <form method="POST" enctype="multipart/form-data" onsubmit="prepareSubmission()">
                        <?php echo security_csrf_field(); ?>
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_data['id'] ?? ''); ?>">
                        <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($edit_data['image_path'] ?? ''); ?>">

                        <div class="mb-6">
                            <input type="text" name="title" id="blog-title" value="<?php echo htmlspecialchars($edit_data['title'] ?? ''); ?>" 
                                   class="w-full text-2xl font-bold text-[#173978] placeholder-slate-300 border-0 border-b-2 border-slate-100 focus:border-[#2fcaf0] focus:ring-0 px-0 py-2 transition-all" 
                                   placeholder="Enter Article Headline..." required>
                        </div>

                        <div class="mb-6">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                                Custom URL Slug <span class="font-normal normal-case text-slate-400">(optional — auto from title if empty)</span>
                            </label>
                            <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                                <span class="text-sm text-slate-400 font-mono shrink-0" id="slug-prefix"><?php echo htmlspecialchars($siteBaseUrl); ?>/</span>
                                <input type="text" name="custom_slug" id="blog-custom-slug" value="<?php echo htmlspecialchars($edit_data['slug'] ?? ''); ?>"
                                       class="flex-1 border border-slate-200 rounded-lg px-4 py-2.5 text-sm font-mono focus:ring-2 focus:ring-[#2fcaf0] outline-none"
                                       placeholder="my-custom-article-slug" autocomplete="off" spellcheck="false">
                            </div>
                            <p class="text-xs text-slate-400 mt-2">Lowercase letters, numbers and hyphens only. Leave empty to generate from the headline.</p>
                        </div>

                        <div class="mb-6 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Post URL preview</label>
                            <div class="flex gap-2">
                                <input type="text" id="post-public-url" readonly value="<?php echo htmlspecialchars($editPostUrl); ?>" class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-[#173978] font-mono">
                                <button type="button" onclick="copyPostLink(document.getElementById('post-public-url').value)" class="px-4 py-2 bg-[#173978] text-white text-sm font-bold rounded-lg hover:bg-[#2fcaf0] hover:text-[#173978] transition whitespace-nowrap"><i class="fa-regular fa-copy mr-1"></i> Copy</button>
                                <?php if ($editPostUrl !== ''): ?>
                                <a href="<?php echo htmlspecialchars($editPostUrl); ?>" target="_blank" rel="noopener" class="px-4 py-2 border border-slate-200 text-sm font-bold rounded-lg hover:bg-white transition whitespace-nowrap"><i class="fa-solid fa-external-link"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <input type="hidden" name="locale" value="en">

                        <div class="mb-6 p-4 border rounded-xl <?php echo !empty($edit_data['is_orphan']) ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white'; ?>">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="is_orphan" value="1" class="mt-1 w-4 h-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400" <?php echo !empty($edit_data['is_orphan']) ? 'checked' : ''; ?>>
                                <span>
                                    <span class="block text-sm font-bold text-slate-800">Orphan Post (SEO-only)</span>
                                    <span class="block text-xs text-slate-500 mt-1">Indexed in sitemap, RSS, IndexNow &amp; <code class="text-[11px]">/orphan-index</code> — hidden from blog listing, related posts &amp; internal links. Direct URL only.</span>
                                </span>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Category</label>
                                <div class="relative">
                                    <select name="category" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-[#2fcaf0] outline-none appearance-none">
                                        <?php 
                                            $cats = ['Growth Strategy', 'Staffing', 'FinTech', 'BTL Activation'];
                                            $current_cat = $edit_data['category'] ?? '';
                                            foreach($cats as $cat) {
                                                $selected = ($current_cat == $cat) ? 'selected' : '';
                                                echo "<option value='$cat' $selected>$cat</option>";
                                            }
                                        ?>
                                    </select>
                                    <div class="absolute right-3 top-3 text-slate-400 pointer-events-none"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Cover Image</label>
                                <input type="file" id="blog-cover-image" name="image" accept="image/jpeg,image/png,image/gif,image/webp" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                                <p id="cover-image-status" class="text-xs mt-2 <?php echo !empty($edit_data['image_path']) ? 'text-green-600' : 'text-slate-400'; ?>">
                                    <?php if (!empty($edit_data['image_path'])): ?>
                                        <i class="fa-solid fa-check"></i> Cover image saved
                                    <?php else: ?>
                                        Select an image — it uploads automatically before you save the article.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <div class="mb-8">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Article Content</label>
                            <p class="text-xs text-slate-400 mb-3">
                                CKEditor 4 (free) — headings, <strong>tables</strong>, lists, links, images, colors, paste from Word &amp; HTML source.
                            </p>
                            <textarea id="blog-content-editor" name="content" class="blog-ckeditor-field"><?php
                                $editorContent = $edit_data['content'] ?? '';
                                if (trim(strip_tags($editorContent)) === '') {
                                    $editorContent = '<p>Start typing your story...</p>';
                                }
                                echo htmlspecialchars($editorContent, ENT_QUOTES, 'UTF-8');
                            ?></textarea>
                        </div>

                        <div class="bg-slate-50 rounded-xl p-6 border border-slate-100 mb-8">
                            <h4 class="font-bold text-sm text-[#173978] mb-4 flex items-center"><i class="fa-brands fa-google mr-2"></i> SEO & Tags</h4>
                            <div class="space-y-3">
                                <input type="text" name="meta_title" placeholder="SEO Title (60 chars)" value="<?php echo htmlspecialchars($edit_data['meta_title'] ?? ''); ?>" class="w-full border border-slate-200 rounded-lg px-4 py-2 text-sm focus:border-blue-500 outline-none">
                                <input type="text" name="meta_desc" placeholder="Meta Description (160 chars)" value="<?php echo htmlspecialchars($edit_data['meta_desc'] ?? ''); ?>" class="w-full border border-slate-200 rounded-lg px-4 py-2 text-sm focus:border-blue-500 outline-none">
                                <input type="text" name="keywords" placeholder="SEO Keywords (comma separated)" value="<?php echo htmlspecialchars($edit_data['keywords'] ?? ''); ?>" class="w-full border border-slate-200 rounded-lg px-4 py-2 text-sm focus:border-blue-500 outline-none">
                                <input type="text" name="tags" placeholder="Display Tags (e.g. FinTech, Loans, Lucknow)" value="<?php echo htmlspecialchars($edit_data['tags'] ?? ''); ?>" class="w-full border border-slate-200 rounded-lg px-4 py-2 text-sm focus:border-blue-500 outline-none">
                                <p class="text-xs text-slate-400">Tags appear on the blog page. Leave keywords/tags empty to auto-generate on save.</p>
                            </div>
                        </div>

                        <div class="bg-slate-50 rounded-xl p-6 border border-slate-100 mb-8">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="font-bold text-sm text-[#173978] flex items-center"><i class="fa-solid fa-circle-question mr-2"></i> FAQ Section</h4>
                                <button type="button" onclick="addFaqRow()" class="text-xs font-bold bg-white border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-[#173978] hover:text-white transition"><i class="fa-solid fa-plus mr-1"></i> Add FAQ</button>
                            </div>
                            <div id="faq-list" class="space-y-3"></div>
                            <input type="hidden" name="faq_json" id="faq-json" value="">
                            <p class="text-xs text-slate-400 mt-3">Add questions & answers — they will appear in a professional accordion on the blog page (with SEO schema).</p>
                        </div>

                        <div class="flex justify-end gap-4">
                            <a href="blogs.php" class="px-6 py-3 rounded-lg font-bold text-slate-500 hover:bg-slate-100 transition">Cancel</a>
                            <button type="submit" class="bg-gradient-to-r from-[#173978] to-blue-800 text-white px-8 py-3 rounded-xl font-bold hover:shadow-lg hover:shadow-blue-900/30 transition-all transform hover:-translate-y-0.5">
                                <?php echo $edit_data ? 'Update Article' : 'Publish Article'; ?>
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php
    bb_ckeditor_admin_scripts('js/blog-editor.js', [
        'BB_CKEDITOR_CSS' => bb_admin_script('css/ckeditor-blog-content.css'),
    ]);
    ?>
    <script>
        const initialFaq = <?php echo json_encode(blog_parse_faq($edit_data['faq_json'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?>;
        const BB_SITE_BASE = <?php echo json_encode($siteBaseUrl); ?>;

        function slugify(str) {
            return String(str || '').toLowerCase().trim()
                .replace(/[^a-z0-9-]+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
        }

        function blogLocalePath(slug) {
            const clean = slugify(slug);
            if (!clean) {
                return '';
            }
            return encodeURIComponent(clean).replace(/%2F/g, '/');
        }

        function updateSlugPreview() {
            const slugInput = document.getElementById('blog-custom-slug');
            const titleInput = document.getElementById('blog-title');
            const preview = document.getElementById('post-public-url');
            if (!slugInput || !preview) {
                return;
            }

            let slug = slugInput.value.trim();
            if (!slug && titleInput) {
                slug = titleInput.value.trim();
            }

            const path = blogLocalePath(slug);
            preview.value = path ? (BB_SITE_BASE + '/' + path) : '';
        }

        (function initBlogSlugPreview() {
            const slugInput = document.getElementById('blog-custom-slug');
            const titleInput = document.getElementById('blog-title');
            if (!slugInput) {
                return;
            }

            slugInput.addEventListener('input', updateSlugPreview);
            if (titleInput) {
                titleInput.addEventListener('input', updateSlugPreview);
            }
            updateSlugPreview();
        })();

        function faqRowHtml(question = '', answer = '') {
            return `
                <div class="faq-row bg-white border border-slate-200 rounded-lg p-4">
                    <div class="flex justify-between items-start gap-3 mb-2">
                        <label class="text-xs font-bold text-slate-400 uppercase">Question</label>
                        <button type="button" onclick="this.closest('.faq-row').remove()" class="text-red-400 hover:text-red-600 text-xs font-bold">Remove</button>
                    </div>
                    <input type="text" class="faq-q w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-3" placeholder="e.g. How fast is loan approval?" value="${escapeHtml(question)}">
                    <label class="text-xs font-bold text-slate-400 uppercase block mb-2">Answer</label>
                    <textarea class="faq-a w-full border border-slate-200 rounded-lg px-3 py-2 text-sm min-h-[90px]" placeholder="Write a clear, helpful answer...">${escapeHtml(answer)}</textarea>
                </div>`;
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function addFaqRow(question = '', answer = '') {
            document.getElementById('faq-list').insertAdjacentHTML('beforeend', faqRowHtml(question, answer));
        }

        function serializeFaq() {
            const rows = document.querySelectorAll('#faq-list .faq-row');
            const data = [];
            rows.forEach(row => {
                const q = row.querySelector('.faq-q').value.trim();
                const a = row.querySelector('.faq-a').value.trim();
                if (q && a) data.push({ question: q, answer: a });
            });
            document.getElementById('faq-json').value = JSON.stringify(data);
        }

        function prepareSubmission() {
            if (typeof window.bbSyncBlogEditor === 'function') {
                window.bbSyncBlogEditor();
            }
            serializeFaq();
            if (window.bbCoverImageUploading) {
                alert('Please wait — cover image is still uploading.');
                return false;
            }
            return true;
        }

        (function () {
            const input = document.getElementById('blog-cover-image');
            const status = document.getElementById('cover-image-status');
            const existing = document.querySelector('input[name="existing_image"]');
            const csrf = document.querySelector('input[name="_csrf"]');
            if (!input || !status || !existing || !csrf) {
                return;
            }

            input.addEventListener('change', function () {
                const file = input.files && input.files[0];
                if (!file) {
                    return;
                }

                window.bbCoverImageUploading = true;
                status.className = 'text-xs mt-2 text-blue-600';
                status.textContent = 'Uploading cover image…';

                const fd = new FormData();
                fd.append('image', file);
                fd.append('_csrf', csrf.value);

                fetch('upload-blog-image.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        window.bbCoverImageUploading = false;
                        if (data.ok && data.path) {
                            existing.value = data.path;
                            input.value = '';
                            status.className = 'text-xs mt-2 text-green-600';
                            status.innerHTML = '<i class="fa-solid fa-check"></i> Cover image uploaded — save the article to apply.';
                            return;
                        }
                        status.className = 'text-xs mt-2 text-red-600';
                        status.textContent = data.error || 'Upload failed. Try a smaller JPG/WebP image.';
                    })
                    .catch(function () {
                        window.bbCoverImageUploading = false;
                        status.className = 'text-xs mt-2 text-red-600';
                        status.textContent = 'Upload failed. Check your connection and try again.';
                    });
            });
        })();

        function copyPostLink(url) {
            if (!url) return;
            navigator.clipboard.writeText(url).then(function () {
                alert('Link copied to clipboard!');
            }).catch(function () {
                prompt('Copy this link:', url);
            });
        }

        if (initialFaq.length) {
            initialFaq.forEach(item => addFaqRow(item.question, item.answer));
        } else {
            addFaqRow();
        }
    </script>
</body>
</html>