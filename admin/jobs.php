<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin', 'hr']);

require '../db.php';
require_once __DIR__ . '/../includes/assets.php';
require_once '../includes/job-helpers.php';

job_admin_ensure_schema($pdo);

$msg = isset($_GET['msg']) ? (string) $_GET['msg'] : '';
$edit_data = null;

admin_handle_post_action(function (int $id) use ($pdo) {
    try {
        $pdo->prepare('DELETE FROM jobs WHERE id=?')->execute([$id]);
        register_shutdown_function(static function () use ($pdo): void {
            try {
                require_once dirname(__DIR__) . '/includes/seo.php';
                seo_ping_after_job_change($pdo);
            } catch (Throwable $e) {
                error_log('Deferred job delete SEO ping failed: ' . $e->getMessage());
            }
        });
    } catch (Throwable $e) {
        error_log('Admin job delete failed: ' . $e->getMessage());
        header('Location: jobs.php?msg=' . urlencode('Error: Could not delete the job.'));
        exit;
    }
}, 'jobs.php?msg=Deleted', 'delete');

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id=?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit_data = $stmt->fetch();
    if ($edit_data === false) {
        $edit_data = null;
    }
}

// --- CREATE / UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
    $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
    $title = trim((string) ($_POST['title'] ?? ''));
    $location = trim((string) ($_POST['location'] ?? ''));
    $department = trim((string) ($_POST['department'] ?? ''));
    $type = trim((string) ($_POST['type'] ?? 'Full-time'));
    $work_mode = in_array($_POST['work_mode'] ?? '', ['on-site', 'hybrid', 'remote'], true) ? $_POST['work_mode'] : 'on-site';
    $vacancies = max(1, (int) ($_POST['vacancies'] ?? 1));
    $description = (string) ($_POST['description'] ?? '');
    $min_salary = $_POST['min_salary'] !== '' && $_POST['min_salary'] !== null ? (int) $_POST['min_salary'] : null;
    $max_salary = $_POST['max_salary'] !== '' && $_POST['max_salary'] !== null ? (int) $_POST['max_salary'] : null;
    $education = job_normalize_education($_POST['education'] ?? 'highSchool');
    $experience_months = (int) ($_POST['experience_months'] ?? 0);
    $apply_email = trim((string) ($_POST['apply_email'] ?? ''));
    $meta_title = trim((string) ($_POST['meta_title'] ?? ''));
    $meta_desc = trim((string) ($_POST['meta_desc'] ?? ''));
    $keywords = trim((string) ($_POST['keywords'] ?? ''));
    $status = !empty($_POST['status']) ? 1 : 0;
    $posted_date = trim((string) ($_POST['posted_date'] ?? ''));
    if ($posted_date === '' || !strtotime($posted_date)) {
        $posted_date = date('Y-m-d');
    } else {
        $posted_date = date('Y-m-d', strtotime($posted_date));
    }
    $locale = 'en';
    $slug = '';

    try {
        if ($title === '') {
            throw new RuntimeException('Job title is required.');
        }
        if ($location === '') {
            throw new RuntimeException('Job location is required.');
        }

        $customSlug = trim((string) ($_POST['custom_slug'] ?? ''));
        if ($customSlug !== '') {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $customSlug), '-'));
        } else {
            $slug = job_make_slug($title, $location, $id);
        }
        $slug = job_ensure_unique_slug($pdo, $slug, $id, $locale);

        $savedId = job_admin_save_post($pdo, $id, [
            'title'             => $title,
            'slug'              => $slug,
            'location'          => $location,
            'department'        => $department,
            'type'              => $type,
            'work_mode'         => $work_mode,
            'vacancies'         => $vacancies,
            'description'       => $description,
            'min_salary'        => $min_salary,
            'max_salary'        => $max_salary,
            'education'         => $education,
            'experience_months' => $experience_months,
            'apply_email'       => $apply_email,
            'meta_title'        => $meta_title,
            'meta_desc'         => $meta_desc,
            'keywords'          => $keywords,
            'status'            => $status,
            'posted_date'       => $posted_date,
            'locale'            => $locale,
        ]);

        register_shutdown_function(static function () use ($pdo): void {
            try {
                require_once dirname(__DIR__) . '/includes/seo.php';
                seo_ping_after_job_change($pdo);
            } catch (Throwable $e) {
                error_log('Deferred job SEO ping failed: ' . $e->getMessage());
            }
        });

        $successMsg = $id ? 'Job Updated Successfully' : 'Job Posted Successfully';
        header('Location: jobs.php?edit=' . $savedId . '&msg=' . urlencode($successMsg));
        exit;
    } catch (Throwable $e) {
        error_log('Admin job save failed: ' . $e->getMessage());
        $msg = 'Error: Could not save the job. ' . $e->getMessage();
        $edit_data = array_merge(is_array($edit_data) ? $edit_data : [], [
            'id'                => $id,
            'title'             => $title,
            'slug'              => $slug,
            'location'          => $location,
            'department'        => $department,
            'type'              => $type,
            'work_mode'         => $work_mode,
            'vacancies'         => $vacancies,
            'description'       => $description,
            'min_salary'        => $min_salary,
            'max_salary'        => $max_salary,
            'education'         => $education,
            'experience_months' => $experience_months,
            'apply_email'       => $apply_email,
            'meta_title'        => $meta_title,
            'meta_desc'         => $meta_desc,
            'keywords'          => $keywords,
            'status'            => $status,
            'posted_date'       => $posted_date,
            'locale'            => $locale,
        ]);
    }
}

$jobs = [];
try {
    $jobs = $pdo->query(job_admin_list_sql($pdo))->fetchAll();
} catch (PDOException $e) {
    error_log('Admin jobs list failed: ' . $e->getMessage());
    if ($msg === '') {
        $msg = 'Error: Could not load job list. Please check the database connection.';
    }
}

$editJobUrl = '';
$validThroughPreview = '';
if (!empty($edit_data['slug'])) {
    $editJobUrl = job_post_url($edit_data['slug']);
    $validThroughPreview = job_schema_valid_through($edit_data);
}
$educationOptions = job_education_options();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Jobs | Bisani Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
</head>
<body class="admin-jobs bg-slate-50 text-slate-800 flex h-screen overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto relative">
        <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 border-b border-slate-200 px-8 h-20 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-[#173978]">Careers Management</h2>
            <a href="jobs.php" class="bg-[#2fcaf0] text-[#173978] px-5 py-2 rounded-lg font-bold hover:bg-[#173978] hover:text-white transition-all shadow-md text-sm"><i class="fa-solid fa-plus mr-2"></i> Post Job</a>
        </header>

        <div class="p-8 max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <div class="lg:col-span-4 flex flex-col gap-4 h-[calc(100vh-140px)]">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col h-full">
                    <div class="p-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <span class="font-bold text-slate-700 text-sm uppercase">Active Openings</span>
                        <span class="text-xs bg-orange-500 text-white px-2 py-0.5 rounded-full"><?php echo count($jobs); ?></span>
                    </div>
                    <div class="overflow-y-auto flex-1 p-2 space-y-1">
                        <?php foreach($jobs as $j):
                            $jSlug = $j['slug'] ?? job_make_slug($j['title'], $j['location'] ?? null, (int) $j['id']);
                            $jUrl = job_post_url($jSlug);
                        ?>
                        <div class="p-3 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-all group relative">
                            <h4 class="font-bold text-[#173978] text-sm leading-tight mb-1 pr-8"><?php echo htmlspecialchars($j['title']); ?></h4>
                            <p class="text-xs text-slate-500 mb-2"><i class="fa-solid fa-location-dot mr-1"></i> <?php echo htmlspecialchars($j['location']); ?></p>
                            <span class="text-[10px] font-bold text-orange-600 bg-orange-50 border border-orange-200 px-2 py-1 rounded uppercase"><?php echo htmlspecialchars($j['type']); ?></span>
                            <?php if (empty($j['status'])): ?><span class="ml-1 text-[10px] font-bold text-red-600 bg-red-50 border border-red-200 px-2 py-1 rounded uppercase">Closed</span><?php endif; ?>
                            
                            <div class="absolute top-3 right-3 hidden group-hover:flex gap-1 bg-white shadow-sm p-1 rounded-md border">
                                <button type="button" onclick="copyJobLink('<?php echo htmlspecialchars($jUrl, ENT_QUOTES); ?>')" class="text-slate-500 hover:text-[#173978] px-1" title="Copy link"><i class="fa-solid fa-link"></i></button>
                                <a href="jobs.php?edit=<?php echo $j['id']; ?>" class="text-blue-500 hover:text-blue-700 px-1"><i class="fa-solid fa-pen"></i></a>
                                <?php echo admin_post_button('jobs.php', (int) $j['id'], 'delete', '<span class="text-red-400 hover:text-red-600 px-1"><i class="fa-solid fa-trash"></i></span>', 'Delete this job?'); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-8">
                    <h3 class="text-xl font-bold text-[#173978] mb-6 flex items-center gap-2 border-b pb-4">
                        <i class="fa-solid fa-briefcase text-[#2fcaf0]"></i> <?php echo $edit_data ? 'Edit Job Posting' : 'Post New Job'; ?>
                    </h3>

                    <?php
                    if ($msg || isset($_GET['msg'])) {
                        $message = $msg ?: (string) ($_GET['msg'] ?? '');
                        $isError = stripos($message, 'error') !== false;
                        $color = $isError ? 'red' : 'green';
                        echo "<div class='mb-6 p-4 bg-{$color}-50 text-{$color}-700 rounded-lg border border-{$color}-200 flex items-center'><i class='fa-solid fa-" . ($isError ? 'circle-exclamation' : 'check-circle') . " mr-3'></i> " . security_e($message) . "</div>";
                    }
                    ?>

                    <form method="POST" id="job-form">
                        <?php echo security_csrf_field(); ?>
                        <input type="hidden" name="id" value="<?php echo $edit_data['id'] ?? ''; ?>">

                        <input type="hidden" name="locale" value="en">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Job Status</label>
                                <label class="flex items-center gap-3 h-[42px] px-4 border border-slate-200 rounded-lg bg-slate-50 cursor-pointer">
                                    <input type="checkbox" name="status" value="1" class="rounded" <?php echo !isset($edit_data['status']) || !empty($edit_data['status']) ? 'checked' : ''; ?>>
                                    <span class="text-sm font-bold text-[#173978]">Active / Accepting Applications</span>
                                </label>
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Job Title</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($edit_data['title'] ?? ''); ?>" class="w-full text-base font-bold text-[#173978] border border-slate-200 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#2fcaf0]" placeholder="e.g. Business Development Executive" required>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Department</label>
                                <input type="text" name="department" value="<?php echo htmlspecialchars($edit_data['department'] ?? ''); ?>" class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm" placeholder="e.g. FinTech, Loan, Sales">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Vacancies / Openings</label>
                                <input type="number" name="vacancies" min="1" value="<?php echo (int) ($edit_data['vacancies'] ?? 1); ?>" class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm">
                            </div>
                        </div>

                        <?php if ($editJobUrl !== ''): ?>
                        <div class="mb-5 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Job URL (copy &amp; share — auto-indexed)</label>
                            <div class="flex gap-2">
                                <input type="text" id="job-public-url" readonly value="<?php echo htmlspecialchars($editJobUrl); ?>" class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-[#173978] font-mono">
                                <button type="button" onclick="copyJobLink(document.getElementById('job-public-url').value)" class="px-4 py-2 bg-[#173978] text-white text-sm font-bold rounded-lg hover:bg-[#2fcaf0] hover:text-[#173978] transition whitespace-nowrap"><i class="fa-regular fa-copy mr-1"></i> Copy</button>
                                <a href="<?php echo htmlspecialchars($editJobUrl); ?>" target="_blank" rel="noopener" class="px-4 py-2 border border-slate-200 text-sm font-bold rounded-lg hover:bg-white transition whitespace-nowrap"><i class="fa-solid fa-external-link"></i></a>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-2">Google validThrough (auto): <strong><?php echo htmlspecialchars($validThroughPreview); ?></strong> · Indexed in sitemap, RSS &amp; IndexNow on save.</p>
                        </div>
                        <?php endif; ?>

                        <div class="mb-5">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Custom URL Slug <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                            <input type="text" name="custom_slug" value="<?php echo htmlspecialchars($edit_data['slug'] ?? ''); ?>" class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm font-mono" placeholder="business-development-executive-lucknow">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Location</label>
                                <input type="text" name="location" value="<?php echo htmlspecialchars($edit_data['location'] ?? ''); ?>" class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2fcaf0]" placeholder="e.g. Mumbai, India" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Work Mode</label>
                                <select name="work_mode" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-sm">
                                    <?php foreach (['on-site' => 'On-site (Field/Office)', 'hybrid' => 'Hybrid', 'remote' => 'Remote'] as $val => $label): ?>
                                    <option value="<?php echo $val; ?>" <?php echo (($edit_data['work_mode'] ?? 'on-site') === $val) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Employment Type</label>
                                <select name="type" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2fcaf0]">
                                    <?php foreach (['Full-time', 'Part-time', 'Contract', 'Internship'] as $empType): ?>
                                    <option value="<?php echo $empType; ?>" <?php echo (isset($edit_data['type']) && $edit_data['type'] == $empType) ? 'selected' : ''; ?>><?php echo $empType; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Posted Date</label>
                                <input type="date" name="posted_date" value="<?php echo htmlspecialchars($edit_data['posted_date'] ?? date('Y-m-d')); ?>" class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Apply / HR Email</label>
                                <input type="email" name="apply_email" value="<?php echo htmlspecialchars($edit_data['apply_email'] ?? 'hr@bisanibrother.com'); ?>" class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm" placeholder="hr@bisanibrother.com">
                            </div>
                        </div>

                        <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-5 mb-6">
                            <h4 class="text-xs font-bold text-[#173978] uppercase mb-4 tracking-wide"><i class="fa-brands fa-google text-[#2fcaf0] mr-1"></i> Google Jobs Optimization</h4>
                            
                            <div class="grid grid-cols-2 gap-5 mb-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">Min Salary (INR/Month)</label>
                                    <input type="number" name="min_salary" value="<?php echo htmlspecialchars((string) ($edit_data['min_salary'] ?? '')); ?>" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="e.g. 15000">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">Max Salary (INR/Month)</label>
                                    <input type="number" name="max_salary" value="<?php echo htmlspecialchars((string) ($edit_data['max_salary'] ?? '')); ?>" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="e.g. 35000">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">Education Required</label>
                                    <select name="education" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                                        <?php
                                        $curEdu = job_normalize_education($edit_data['education'] ?? 'highSchool');
                                        foreach ($educationOptions as $val => $label):
                                        ?>
                                        <option value="<?php echo htmlspecialchars($val); ?>" <?php echo $curEdu === $val ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">Experience Needed (Months)</label>
                                    <input type="number" name="experience_months" min="0" value="<?php echo (int) ($edit_data['experience_months'] ?? 0); ?>" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="e.g. 6">
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 mb-6">
                            <h4 class="text-xs font-bold text-[#173978] uppercase mb-4 tracking-wide"><i class="fa-solid fa-magnifying-glass mr-1"></i> SEO Meta (Google Search)</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">Meta Title</label>
                                    <input type="text" name="meta_title" maxlength="120" value="<?php echo htmlspecialchars($edit_data['meta_title'] ?? ''); ?>" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="<?php echo htmlspecialchars(($edit_data['title'] ?? 'Job Title') . ' | Careers | Bisani Brothers'); ?>">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">Meta Description</label>
                                    <textarea name="meta_desc" rows="2" maxlength="255" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Short summary for Google search results..."><?php echo htmlspecialchars($edit_data['meta_desc'] ?? ''); ?></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1">Keywords</label>
                                    <input type="text" name="keywords" value="<?php echo htmlspecialchars($edit_data['keywords'] ?? ''); ?>" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="sales jobs lucknow, field executive, BDE">
                                </div>
                            </div>
                        </div>

                        <div class="mb-8">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Job Description</label>
                            <textarea id="job-content-editor" class="w-full"><?php echo $edit_data['description'] ?? '<p>Write job responsibilities, eligibility, salary &amp; benefits here...</p>'; ?></textarea>
                            <textarea name="description" id="job-description-hidden" class="hidden"></textarea>
                            <p class="text-[11px] text-slate-400 mt-2">Rich editor — add headings, lists, bold, links &amp; images for full job details.</p>
                        </div>

                        <div class="flex justify-end gap-4 pt-4 border-t border-slate-100">
                            <a href="jobs.php" class="px-6 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-100 transition border border-transparent">Cancel</a>
                            <button type="submit" class="bg-[#173978] text-white px-8 py-3 rounded-xl font-bold hover:bg-[#2fcaf0] hover:text-[#173978] shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5">
                                <?php echo $edit_data ? 'Update Job Details' : 'Publish Job Post'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <?php bb_ckeditor_admin_scripts('js/job-editor.js'); ?>
    <script>
        function copyJobLink(url) {
            if (!url) return;
            navigator.clipboard.writeText(url).then(function () {
                alert('Job link copied to clipboard!');
            }).catch(function () {
                prompt('Copy this link:', url);
            });
        }
    </script>
</body>
</html>