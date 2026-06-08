<?php
session_start();
require 'db.php';
require_once 'includes/seo.php';
require_once 'includes/job-helpers.php';

$status_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['website_check'])) {
        die('Spam detected.');
    }
    $text_data = ($_POST['name'] ?? '') . ' ' . ($_POST['email'] ?? '') . ' ' . ($_POST['phone'] ?? '');
    if (preg_match('/http|www\.|ftp/i', $text_data)) {
        $status_msg = 'Links are not allowed in the application form.';
    } else {
        $job_id = (int) ($_POST['job_id'] ?? 0);
        $name = htmlspecialchars(stripslashes(trim($_POST['name'] ?? '')));
        $email = htmlspecialchars(stripslashes(trim($_POST['email'] ?? '')));
        $phone = htmlspecialchars(stripslashes(trim($_POST['phone'] ?? '')));
        $applied_at = date('Y-m-d H:i:s');

        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === 0) {
            $target_dir = 'uploads/resumes/';
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $fileType = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'doc', 'docx'];
            if (!in_array($fileType, $allowed, true)) {
                $status_msg = 'Only PDF and DOC files are allowed.';
            } else {
                $newFileName = 'cv_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileType;
                $target_file = $target_dir . $newFileName;
                if (move_uploaded_file($_FILES['resume']['tmp_name'], $target_file)) {
                    try {
                        $sql = 'INSERT INTO applications (job_id, applicant_name, email, phone, resume_path, applied_at) VALUES (?, ?, ?, ?, ?, ?)';
                        $stmt = $pdo->prepare($sql);
                        if ($stmt->execute([$job_id, $name, $email, $phone, $target_file, $applied_at])) {
                            header('Location: ' . $_SERVER['REQUEST_URI'] . '?status=success');
                            exit();
                        }
                        $status_msg = 'Could not save application.';
                    } catch (PDOException $e) {
                        $status_msg = 'Database error. Please try again.';
                    }
                } else {
                    $status_msg = 'File upload failed.';
                }
            }
        } else {
            $status_msg = 'Please attach your resume.';
        }
    }
}

$slug = trim($_GET['slug'] ?? '');
$job = null;
if ($slug !== '') {
    $job = job_fetch_by_slug($pdo, $slug);
}

if (!$job) {
    http_response_code(404);
    $pageTitle = 'Job Not Found | Bisani Brothers';
    $pageDesc = 'This job opening is no longer available or may have been filled.';
    include '404.php';
    exit();
}

$base = seo_site_url_rtrim();
$jobUrl = job_post_url($job['slug'], $base);
$pageTitle = trim($job['meta_title'] ?? '') !== ''
    ? htmlspecialchars($job['meta_title'])
    : htmlspecialchars($job['title']) . ' — ' . htmlspecialchars($job['location'] ?? 'India') . ' | Careers';
$pageDesc = trim($job['meta_desc'] ?? '') !== ''
    ? seo_strip_text($job['meta_desc'], 160)
    : seo_strip_text($job['description'] ?? '', 160);
if (!empty($job['keywords'])) {
    $pageKeywords = $job['keywords'];
}
$pageSchemas = [
    seo_job_posting_schema($job, $base),
    seo_breadcrumb_schema([
        ['name' => 'Home', 'url' => $base . '/'],
        ['name' => 'Careers', 'url' => $base . '/careers'],
        ['name' => $job['title'], 'url' => $jobUrl],
    ]),
];

include 'includes/header.php';
?>

<?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
<div class="fixed top-24 left-1/2 transform -translate-x-1/2 z-50 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-2xl flex items-center" style="min-width: 300px;">
    <i class="fa-solid fa-check-circle text-2xl mr-3"></i>
    <div>
        <p class="font-bold">Application Submitted!</p>
        <p class="text-sm">We have received your resume successfully.</p>
    </div>
</div>
<script>setTimeout(function(){ document.querySelector('.fixed.top-24')?.remove(); }, 6000);</script>
<?php endif; ?>

<section class="relative w-full py-20 md:py-28 bg-[#173978] text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="text-sm text-blue-200 mb-4" aria-label="Breadcrumb">
            <a href="./" class="hover:text-white">Home</a>
            <span class="mx-2 opacity-50">/</span>
            <a href="careers" class="hover:text-white">Careers</a>
        </nav>
        <h1 class="text-3xl md:text-4xl font-extrabold mb-4"><?php echo htmlspecialchars($job['title']); ?></h1>
        <div class="flex flex-wrap gap-4 text-blue-100 text-sm font-medium">
            <?php if (!empty($job['location'])): ?>
            <span><i class="fa-solid fa-location-dot text-[#2fcaf0] mr-1"></i> <?php echo htmlspecialchars($job['location']); ?></span>
            <?php endif; ?>
            <?php if (!empty($job['type'])): ?>
            <span><i class="fa-solid fa-briefcase text-[#2fcaf0] mr-1"></i> <?php echo htmlspecialchars($job['type']); ?></span>
            <?php endif; ?>
            <?php if (!empty($job['work_mode'])): ?>
            <span><i class="fa-solid fa-building text-[#2fcaf0] mr-1"></i> <?php echo htmlspecialchars(job_work_mode_label($job['work_mode'])); ?></span>
            <?php endif; ?>
            <?php if (!empty($job['department'])): ?>
            <span><i class="fa-solid fa-sitemap text-[#2fcaf0] mr-1"></i> <?php echo htmlspecialchars($job['department']); ?></span>
            <?php endif; ?>
            <?php if (!empty($job['vacancies']) && (int) $job['vacancies'] > 1): ?>
            <span><i class="fa-solid fa-users text-[#2fcaf0] mr-1"></i> <?php echo (int) $job['vacancies']; ?> Openings</span>
            <?php endif; ?>
            <span><i class="fa-regular fa-calendar text-[#2fcaf0] mr-1"></i> Posted <?php echo date('M d, Y', strtotime($job['posted_date'])); ?></span>
        </div>
    </div>
</section>

<section class="py-16 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 md:p-10 mb-10">
            <h2 class="text-xl font-bold text-[#173978] mb-6">Job Description</h2>
            <div class="prose prose-slate max-w-none text-gray-700"><?php echo $job['description']; ?></div>
        </div>

        <div id="apply" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 md:p-10">
            <h2 class="text-2xl font-bold text-[#173978] mb-2">Apply for this Role</h2>
            <p class="text-gray-500 mb-6 text-sm">Submit your details and resume below.<?php if (!empty($job['apply_email'])): ?> Or email <a href="mailto:<?php echo htmlspecialchars($job['apply_email']); ?>" class="text-[#173978] font-bold underline"><?php echo htmlspecialchars($job['apply_email']); ?></a><?php endif; ?></p>

            <?php if ($status_msg !== ''): ?>
            <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-lg border border-red-200"><?php echo htmlspecialchars($status_msg); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="job_id" value="<?php echo (int) $job['id']; ?>">
                <input type="text" name="website_check" style="display:none !important;" tabindex="-1" autocomplete="off">

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="name" class="w-full border border-gray-300 rounded-lg p-3 focus:outline-[#2fcaf0]" required>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" class="w-full border border-gray-300 rounded-lg p-3 focus:outline-[#2fcaf0]" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Phone</label>
                        <input type="tel" name="phone" class="w-full border border-gray-300 rounded-lg p-3 focus:outline-[#2fcaf0]" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Resume (PDF/DOC)</label>
                    <input type="file" name="resume" accept=".pdf,.doc,.docx" class="w-full text-sm" required>
                </div>
                <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-[#173978] text-white font-bold rounded-lg hover:bg-[#2fcaf0] hover:text-[#173978] transition-colors">
                    Submit Application
                </button>
            </form>
        </div>

        <div class="mt-8 text-center">
            <a href="careers#openings-section" class="inline-flex items-center text-[#173978] font-bold hover:text-[#2fcaf0] transition-colors">
                <i class="fa-solid fa-arrow-left mr-2"></i> View All Openings
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
