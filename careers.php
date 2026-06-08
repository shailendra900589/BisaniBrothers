<?php
// 1. PREVENT HEADER ERRORS
session_start();
require 'db.php';
require_once 'includes/seo.php';
require_once 'includes/job-helpers.php';

// --- BACKEND LOGIC: HANDLE APPLICATION SUBMISSION ---
$status_msg = "";
$status_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // A. BOT PROTECTION (HONEYPOT)
    if (!empty($_POST['website_check'])) {
        die("Spam detected.");
    }

    // B. PREVENT LINKS (SPAM PROTECTION)
    // We check text fields (not the file) for links
    $text_data = $_POST['name'] . " " . $_POST['email'] . " " . $_POST['phone'];
    if (preg_match('/http|www\.|ftp/i', $text_data)) {
        echo "<script>alert('Error: Links are not allowed in the application form.'); window.location.href='careers';</script>";
        exit();
    }

    // C. GET & SANITIZE INPUTS
    $job_id = intval($_POST['job_id']);
    $name = htmlspecialchars(stripslashes(trim($_POST['name'])));
    $email = htmlspecialchars(stripslashes(trim($_POST['email'])));
    $phone = htmlspecialchars(stripslashes(trim($_POST['phone'])));
    $applied_at = date('Y-m-d H:i:s'); // Matches 'applied_at' column in your image

    // D. FILE UPLOAD LOGIC
    $resume_path = "";
    
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
        $target_dir = "uploads/resumes/";
        
        // Create folder if it doesn't exist
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Get file extension
        $fileType = strtolower(pathinfo($_FILES["resume"]["name"], PATHINFO_EXTENSION));
        
        // Strict Allow List
        $allowed = ['pdf', 'doc', 'docx'];
        
        if (!in_array($fileType, $allowed)) {
            echo "<script>alert('Error: Only PDF and DOC files are allowed.'); window.location.href='careers';</script>";
            exit();
        }

        // Security: Generate Random Filename
        $newFileName = "cv_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $fileType;
        $target_file = $target_dir . $newFileName;

        if (move_uploaded_file($_FILES["resume"]["tmp_name"], $target_file)) {
            $resume_path = $target_file; 
            
            // E. INSERT INTO DATABASE (Table: applications)
            try {
                $sql = "INSERT INTO applications (job_id, applicant_name, email, phone, resume_path, applied_at) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([$job_id, $name, $email, $phone, $resume_path, $applied_at])) {
                    // Success Redirect
                    require_once __DIR__ . '/includes/locale.php';
                    header('Location: ' . locale_url('careers?status=success'));
                    exit();
                } else {
                    $status_msg = "Database Error: Could not save application.";
                }
            } catch (PDOException $e) {
                error_log('careers application: ' . $e->getMessage());
                $status_msg = 'Database Error: Could not save application.';
            }

        } else {
            $status_msg = "Error uploading file. Check folder permissions.";
        }
    } else {
        $status_msg = "Please upload a valid resume file.";
    }
}

// --- PAGINATION SETUP ---
$limit = 10; // Number of jobs per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count total active jobs for pagination
$totalJobsStmt = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 1");
$totalJobs = $totalJobsStmt->fetchColumn();
$totalPages = ceil($totalJobs / $limit);

// Fetch jobs with limit and offset
$jobStmt = $pdo->prepare("SELECT * FROM jobs WHERE status = 1 ORDER BY posted_date DESC LIMIT :limit OFFSET :offset");
$jobStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$jobStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$jobStmt->execute();

// --- PAGE CONTENT START ---
$pageTitle = "Careers | Join Bisani Brothers";
$pageDesc = "Build your career with Bisani Brothers. Explore field sales jobs, telesales, business development, collection executive, and automobile sales vacancies in Lucknow, Patna, Delhi, and Chennai.";
$careersBaseUrl = seo_site_url_rtrim();
$pageSchemas = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => 'Careers at Bisani Brothers',
        'description' => $pageDesc,
        'url' => $careersBaseUrl . '/careers',
        'isPartOf' => ['@type' => 'WebSite', 'name' => SEO_SITE_NAME, 'url' => $careersBaseUrl . '/'],
    ],
];
foreach (job_fetch_active($pdo) as $schemaJob) {
    $pageSchemas[] = seo_job_posting_schema($schemaJob, $careersBaseUrl);
}
include 'includes/header.php';
?>

<?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
<div id="success-alert" class="fixed top-24 left-1/2 transform -translate-x-1/2 z-50 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-2xl flex items-center animate-bounce bb-alert-min-w-300">
    <i class="fa-solid fa-check-circle text-2xl mr-3"></i>
    <div>
        <p class="font-bold">Application Submitted!</p>
        <p class="text-sm">We have received your resume successfully.</p>
    </div>
    <button onclick="document.getElementById('success-alert').remove()" class="ml-auto pl-6 text-green-700 font-bold hover:text-green-900">✕</button>
</div>
<script>setTimeout(() => { document.getElementById('success-alert').remove(); }, 6000);</script>
<?php endif; ?>

<?php if(!empty($status_msg)): ?>
    <script>alert(<?php echo json_encode($status_msg, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);</script>
<?php endif; ?>

<section class="relative w-full py-24 md:py-40 flex items-center overflow-hidden">
    <div class="absolute inset-0 z-0">
        <img src="assets/bg/Careers_page.webp" alt="Career Growth With Bisani Brothers" class="w-full h-full object-cover object-center">
        <div class="absolute inset-0 bg-black/30"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full">
        <div class="max-w-3xl w-full bg-[#173978]/50 backdrop-blur-md rounded-[2rem] p-10 md:p-16 text-center md:text-left text-white shadow-2xl border border-white/10" data-aos="fade-up">
            <h1 class="text-4xl md:text-6xl font-extrabold mb-6 tracking-tight leading-tight">
                <?php echo page_te('hero_h1_a'); ?> <br> <span class="text-[#2fcaf0]"><?php echo page_te('hero_h1_b'); ?></span>
            </h1>
            <p class="text-blue-100 text-lg md:text-xl max-w-lg mx-auto md:mx-0 leading-relaxed mb-8">
                <?php echo page_te('hero_desc'); ?>
            </p>
            <div>
                <button onclick="scrollToOpenings()" class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-base font-bold rounded text-[#052c65] bg-[#2fcaf0] hover:bg-white md:text-lg transition-all duration-300 shadow-[0_0_20px_rgba(47,202,240,0.3)] hover:shadow-[0_0_30px_rgba(47,202,240,0.6)] cursor-pointer group">
                    <?php echo page_te('hero_btn'); ?>
                </button>
            </div>
        </div>
    </div>
</section>

<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div>
                <span class="text-[#2fcaf0] font-bold tracking-wider uppercase text-sm mb-2 block">Work With Us</span>
                <h2 class="text-3xl font-extrabold text-[#173978] mb-6">Create Opportunities. <br>Grow Diverse.</h2>
                <div class="prose text-gray-600 mb-8">
                    <p class="mb-4">
                        At <strong>Bisani Brothers Private Limited</strong>, we create opportunities for people who want to learn, perform, and grow across diverse business environments. Whether you’re experienced or just starting out, we offer roles that provide exposure, responsibility, and meaningful work.
                    </p>
                    <p>
                        We operate across industries including <strong>FinTech, BFSI, Education, Retail, Lending</strong>, and allied sectors — offering career opportunities for individuals from different professional backgrounds.
                    </p>
                </div>
                <div class="bg-gray-50 p-6 rounded-xl border border-gray-100">
                    <h3 class="font-bold text-[#173978] mb-4">At BBPL, we value:</h3>
                    <ul class="space-y-3">
                        <li class="flex items-center text-gray-700"><i class="fa-solid fa-check text-[#2fcaf0] mr-3"></i> Ownership and accountability</li>
                        <li class="flex items-center text-gray-700"><i class="fa-solid fa-check text-[#2fcaf0] mr-3"></i> Integrity and transparency</li>
                        <li class="flex items-center text-gray-700"><i class="fa-solid fa-check text-[#2fcaf0] mr-3"></i> Continuous learning</li>
                        <li class="flex items-center text-gray-700"><i class="fa-solid fa-check text-[#2fcaf0] mr-3"></i> Equal opportunity for all</li>
                    </ul>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="bg-[#173978] text-white p-8 rounded-2xl shadow-xl transform hover:-translate-y-2 transition-all">
                    <i class="fa-solid fa-layer-group text-4xl text-[#2fcaf0] mb-4"></i>
                    <h3 class="font-bold text-lg mb-2">Multi-Industry Exposure</h3>
                    <p class="text-blue-200 text-sm">Work across FinTech, Retail, and Education projects.</p>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100 transform hover:-translate-y-2 transition-all">
                    <i class="fa-solid fa-graduation-cap text-4xl text-[#173978] mb-4"></i>
                    <h3 class="font-bold text-lg text-[#173978] mb-2">Learning Support</h3>
                    <p class="text-gray-600 text-sm">Continuous development and training support.</p>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100 transform hover:-translate-y-2 transition-all">
                    <i class="fa-solid fa-chart-line text-4xl text-[#173978] mb-4"></i>
                    <h3 class="font-bold text-lg text-[#173978] mb-2">Performance Driven</h3>
                    <p class="text-gray-600 text-sm">A work environment that rewards execution.</p>
                </div>
                <div class="bg-[#2fcaf0] text-[#173978] p-8 rounded-2xl shadow-xl transform hover:-translate-y-2 transition-all">
                    <i class="fa-solid fa-arrow-trend-up text-4xl text-white mb-4"></i>
                    <h3 class="font-bold text-lg mb-2">Fast Growth</h3>
                    <p class="text-[#173978]/80 text-sm">Opportunities to grow with expanding projects.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-20 bg-gray-50" id="openings-section">
    <div class="max-w-5xl mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-extrabold text-[#173978]"><?php echo page_te('openings_title'); ?></h2>
            <p class="text-gray-500 mt-2">We are currently hiring for the following roles:</p>
        </div>
        
        <div class="space-y-6">
            <?php
            if($jobStmt->rowCount() > 0) {
                while ($job = $jobStmt->fetch()) {
                    $jobId = $job['id'];
                    $jobTitle = htmlspecialchars($job['title']);
                    $jobLoc = htmlspecialchars($job['location']);
                    $jobType = htmlspecialchars($job['type']);
                    $jobSlug = $job['slug'] ?? job_make_slug($job['title'], $job['location'] ?? null, (int) $jobId);
            ?>
            
            <div id="job-<?php echo $jobId; ?>" class="bg-white border border-gray-200 rounded-xl p-6 md:p-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 hover:shadow-xl transition-all duration-300 border-l-4 border-l-[#2fcaf0]">
                
                <div class="flex-1">
                    <h3 class="text-2xl font-bold text-[#173978] mb-2">
                        <a href="jobs/<?php echo htmlspecialchars($jobSlug); ?>" class="hover:text-[#2fcaf0] transition-colors"><?php echo $jobTitle; ?></a>
                    </h3>
                    <div class="flex flex-wrap gap-4 text-sm text-gray-600 font-medium">
                        <span class="flex items-center"><i class="fa-solid fa-location-dot text-[#2fcaf0] mr-2"></i> <?php echo $jobLoc; ?></span>
                        <span class="flex items-center"><i class="fa-solid fa-briefcase text-[#2fcaf0] mr-2"></i> <?php echo $jobType; ?></span>
                        <span class="flex items-center"><i class="fa-regular fa-clock text-[#2fcaf0] mr-2"></i> Posted: <?php echo date("M d", strtotime($job['posted_date'])); ?></span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                    <a href="jobs/<?php echo htmlspecialchars($jobSlug); ?>"
                       class="px-6 py-2.5 border-2 border-[#173978] text-[#173978] font-bold rounded-lg hover:bg-blue-50 transition-colors whitespace-nowrap text-center">
                        <?php echo page_te('view_details'); ?>
                    </a>
                    
                    <button onclick="openApply(<?php echo $jobId; ?>, '<?php echo addslashes($jobTitle); ?>')" 
                            class="px-8 py-2.5 bg-[#173978] text-white font-bold rounded-lg hover:bg-[#2fcaf0] hover:text-[#173978] transition-colors whitespace-nowrap shadow-lg text-center">
                        <?php echo page_te('apply_now'); ?>
                    </button>
                </div>
            </div>
            <?php 
                }
            } else {
                echo "<div class='bg-white p-10 rounded-xl shadow text-center text-gray-500'>
                        <i class='fa-regular fa-folder-open text-4xl mb-4 text-gray-300'></i>
                        <p>" . page_te('no_jobs') . "</p>
                      </div>";
            } 
            ?>
        </div>

        <?php if($totalPages > 1): ?>
        <div class="mt-10 flex justify-center gap-2">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>#openings-section" 
                   class="px-4 py-2 rounded-lg font-bold transition-colors <?php echo ($i == $page) ? 'bg-[#173978] text-white' : 'bg-white text-[#173978] border border-gray-200 hover:bg-gray-100'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <div class="mt-16 text-center bg-blue-50 p-8 rounded-2xl border border-blue-100">
            <h4 class="font-bold text-[#173978] text-xl mb-2">Interested in Working With Us?</h4>
            <p class="text-gray-600 mb-4">If you’re motivated and ready to grow with a fast-expanding organization, we’d love to hear from you.</p>
            <div class="inline-flex items-center bg-white px-6 py-3 rounded-full shadow-sm border border-gray-200">
                <i class="fa-solid fa-envelope text-[#2fcaf0] mr-3 text-xl"></i>
                <div class="text-left">
                    <span class="block text-xs text-gray-400 uppercase font-bold">Email your details to</span>
                    <a href="mailto:hr@bisanibrother.com" class="font-bold text-[#173978] hover:text-[#2fcaf0]">hr@bisanibrother.com</a>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-4">Please mention the role you are applying for in the subject line.</p>
        </div>
    </div>
</section>

<div id="details-modal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-[#173978]/80 backdrop-blur-sm" onclick="closeDetails()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden animate-fade-in-up m-4">
        <div class="bg-[#173978] p-6 flex justify-between items-center">
            <h3 id="modal-job-title" class="text-xl font-bold text-white">Job Details</h3>
            <button onclick="closeDetails()" class="text-white hover:text-[#2fcaf0] text-2xl">&times;</button>
        </div>
        <div class="p-8 max-h-[70vh] overflow-y-auto">
            <div id="modal-job-desc" class="prose text-gray-700"></div>
        </div>
        <div class="p-6 border-t bg-gray-50 text-right">
            <button onclick="closeDetails()" class="px-6 py-2 text-gray-500 hover:text-gray-800 font-bold mr-2">Close</button>
            <button id="modal-apply-btn" class="px-6 py-2 bg-[#173978] text-white font-bold rounded-lg hover:bg-[#2fcaf0]">Apply for this Role</button>
        </div>
    </div>
</div>

<div id="apply-modal" class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeApply()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-full max-w-lg rounded-2xl shadow-2xl p-8 animate-fade-in-up">
        
        <div class="text-center mb-6">
            <h3 class="text-2xl font-bold text-[#173978]">Apply Now</h3>
            <p class="text-sm text-gray-500 mt-1">Role: <span id="apply-role-name" class="font-bold text-[#2fcaf0]"></span></p>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="job_id" id="apply-job-id">
            
            <input type="text" name="website_check" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Full Name</label>
                <input type="text" name="name" class="w-full border border-gray-300 rounded-lg p-3 focus:outline-[#2fcaf0]" required>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
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
                <label class="block text-sm font-bold text-gray-700 mb-1">Upload Resume (PDF/DOC)</label>
                <input type="file" name="resume" class="w-full border border-gray-300 rounded-lg p-2 bg-gray-50 text-sm" accept=".pdf,.doc,.docx" required>
            </div>

            <button type="submit" class="w-full bg-[#173978] text-white font-bold py-3.5 rounded-xl hover:bg-[#2fcaf0] hover:text-[#173978] transition-colors shadow-lg mt-2">
                Submit Application
            </button>
        </form>
        
        <button onclick="closeApply()" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-xl">&times;</button>
    </div>
</div>

<script>
    function scrollToOpenings() {
        const section = document.getElementById('openings-section');
        if (section) { section.scrollIntoView({ behavior: 'smooth' }); }
    }

    function openDetails(id, title, desc) {
        document.getElementById('modal-job-title').innerText = title;
        document.getElementById('modal-job-desc').innerHTML = desc;
        document.getElementById('modal-apply-btn').onclick = function() {
            closeDetails();
            openApply(id, title);
        };
        document.getElementById('details-modal').classList.remove('hidden');
    }

    function closeDetails() {
        document.getElementById('details-modal').classList.add('hidden');
    }

    function openApply(id, title) {
        document.getElementById('apply-job-id').value = id;
        document.getElementById('apply-role-name').innerText = title;
        document.getElementById('apply-modal').classList.remove('hidden');
    }

    function closeApply() {
        document.getElementById('apply-modal').classList.add('hidden');
    }
</script>

<?php include 'includes/footer.php'; ?>