<?php
session_start();
require 'db.php';
require_once 'includes/enquiry-helpers.php';

$form_error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!empty($_POST['website_check'])) {
        die('Spam detected.');
    }

    $name = enquiry_normalize_field($_POST['name'] ?? '');
    $mobile = enquiry_normalize_field($_POST['mobile'] ?? '');
    $email = enquiry_normalize_field($_POST['email'] ?? '');
    $city = enquiry_normalize_field($_POST['city'] ?? '');
    $applicant_type = enquiry_normalize_field($_POST['applicant_type'] ?? 'individual');
    $occupation = enquiry_normalize_field($_POST['occupation'] ?? '');
    $experience = enquiry_normalize_field($_POST['experience'] ?? '');
    $work_type = enquiry_normalize_field($_POST['work_type'] ?? '');
    $team_size = enquiry_normalize_field($_POST['team_size'] ?? '');
    $regions = enquiry_normalize_field($_POST['regions'] ?? '');

    $messageParts = array_filter([
        $city !== '' ? 'City: ' . $city : '',
        $occupation !== '' ? 'Occupation: ' . $occupation : '',
        $experience !== '' ? 'Experience: ' . $experience . ' years' : '',
        $work_type !== '' ? 'Work preference: ' . $work_type : '',
        $team_size !== '' ? 'Team size: ' . $team_size : '',
        $regions !== '' ? 'Regions: ' . $regions : '',
    ]);
    $message = implode("\n", $messageParts);

    if ($name === '' || mb_strlen($name) < 2) {
        $form_error = 'Please enter your full name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_error = 'Please enter a valid email address.';
    } elseif (strlen(enquiry_phone_last10($mobile)) < 10) {
        $form_error = 'Please enter a valid 10-digit mobile number.';
    } elseif ($city === '' || $occupation === '' || $experience === '') {
        $form_error = 'Please complete all required fields.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO growth_partners (name, mobile, email, applicant_type, message, created_at)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $ok = $stmt->execute([
                $name,
                $mobile,
                $email,
                $applicant_type !== '' ? $applicant_type : 'individual',
                $message,
                date('Y-m-d H:i:s'),
            ]);
            if ($ok) {
                require_once __DIR__ . '/includes/locale.php';
                header('Location: ' . locale_url('growth-partner?status=success'));
                exit();
            }
            $form_error = 'Unable to submit application. Please try again.';
        } catch (PDOException $e) {
            $form_error = 'Unable to submit application. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<script>
    function toggleAgencyFields(value) {
        const agencyFields = document.getElementById('agency-fields');
        if (value === 'agency') {
            agencyFields.classList.remove('hidden');
        } else {
            agencyFields.classList.add('hidden');
        }
    }
</script>

<section class="relative py-20 md:py-28 overflow-hidden bb-hero-gradient-radial">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col md:flex-row items-center justify-between gap-12 lg:gap-20">
        
        <div class="w-full md:w-1/2 text-center md:text-left animate-fade-in-up">
            <span class="text-[#2fcaf0] font-bold tracking-[0.2em] uppercase text-sm mb-6 block">
                <?php echo page_te('hero_eyebrow'); ?>
            </span>
            
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold text-white mb-8 leading-tight">
                <?php echo page_te('hero_h1_a'); ?> <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#2fcaf0] to-[#86e3f8]">
                    <?php echo page_te('hero_h1_b'); ?>
                </span>
            </h1>
            
            <p class="text-lg md:text-xl text-blue-100/90 max-w-2xl leading-relaxed mb-12">
                Bisani Brothers Private Limited works with individuals and agencies who want to be part of large-scale business and sales execution projects across industries.
            </p>
            
            <div>
                <a href="#register" class="inline-flex items-center px-10 py-4 bg-[#cb595c] hover:bg-[#b84b4e] text-white font-bold rounded-full transition-all transform hover:-translate-y-1 shadow-xl shadow-red-900/30 text-lg">
                    <?php echo page_te('hero_btn'); ?> <i class="fa-solid fa-arrow-right ml-3"></i>
                </a>
            </div>
        </div>

        <div class="w-full md:w-1/2 relative">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90%] h-[90%] bg-[#2fcaf0] rounded-full mix-blend-overlay filter blur-3xl opacity-30"></div>
            
            <img src="assets/images/Growth_Partner_page.webp" 
                 alt="Growth Partner" 
                 class="w-full h-auto object-contain relative z-10 drop-shadow-2xl animate-float-infinite">
        </div>
    </div>
</section>

<section class="py-24 bg-white overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row items-center gap-12 lg:gap-20">
            
            <div class="w-full md:w-1/2 relative animate-fade-in-up">
                <div class="absolute top-0 left-0 w-full h-full bg-blue-50 rounded-full mix-blend-multiply filter blur-3xl opacity-70 -z-10 transform -translate-x-6 -translate-y-6"></div>
                <img src="assets/images/Flexibility.png" 
                     alt="Flexibility" 
                     class="w-full max-w-md mx-auto transform hover:scale-105 transition-transform duration-500">
            </div>

            <div class="w-full md:w-1/2 text-center md:text-left animate-fade-in-up delay-200">
                <div class="w-24 h-1.5 bg-[#2fcaf0] mx-auto md:mx-0 mb-8 rounded-full"></div>
                
                <h2 class="text-3xl md:text-4xl font-bold text-[#173978] mb-6 leading-tight">
                    Why Become a <br> <span class="text-[#2fcaf0]">Growth Partner?</span>
                </h2>
                
                <h3 class="text-xl font-bold text-gray-800 mb-4">Flexibility That Works for Everyone</h3>
                
                <p class="text-lg text-gray-600 leading-relaxed mb-6">
                    We believe growth should be accessible, flexible, and inclusive. Whether you’re an individual professional or an agency with a team, our Growth Partner program is designed to fit your availability, goals, and strengths.
                </p>
                
                <ul class="space-y-3">
                    <li class="flex items-center justify-center md:justify-start gap-3">
                        <i class="fa-solid fa-check-circle text-[#2fcaf0] text-xl"></i>
                        <span class="text-gray-700 font-medium">Structured Support</span>
                    </li>
                    <li class="flex items-center justify-center md:justify-start gap-3">
                        <i class="fa-solid fa-check-circle text-[#2fcaf0] text-xl"></i>
                        <span class="text-gray-700 font-medium">Consistent Engagement</span>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</section>

<section class="py-20 bg-[#173978] text-white relative">
    <div class="absolute inset-0 bg-[url('assets/images/pattern.png')] opacity-10"></div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-3xl font-extrabold">What You Gain as a Growth Partner</h2>
            <div class="w-16 h-1.5 bg-[#cb595c] mx-auto mt-4 rounded-full"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="bg-white/5 backdrop-blur-sm border border-white/10 p-8 rounded-xl hover:bg-white/10 transition-all hover:-translate-y-1" data-aos="fade-up">
                <div class="text-[#2fcaf0] text-3xl mb-4"><i class="fa-solid fa-clock"></i></div>
                <h3 class="text-xl font-bold mb-2">Work That Fits Your Lifestyle</h3>
                <p class="text-blue-200 text-sm">Choose part-time or full-time. Decide your availability and engagement level.</p>
            </div>
            
            <div class="bg-white/5 backdrop-blur-sm border border-white/10 p-8 rounded-xl hover:bg-white/10 transition-all hover:-translate-y-1" data-aos="fade-up" data-aos-delay="100">
                <div class="text-[#2fcaf0] text-3xl mb-4"><i class="fa-solid fa-chalkboard-user"></i></div>
                <h3 class="text-xl font-bold mb-2">Learn Before You Execute</h3>
                <p class="text-blue-200 text-sm">Full training and continuous guidance provided so you feel confident.</p>
            </div>

            <div class="bg-white/5 backdrop-blur-sm border border-white/10 p-8 rounded-xl hover:bg-white/10 transition-all hover:-translate-y-1" data-aos="fade-up" data-aos-delay="200">
                <div class="text-[#2fcaf0] text-3xl mb-4"><i class="fa-solid fa-door-open"></i></div>
                <h3 class="text-xl font-bold mb-2">Opportunity Without Barriers</h3>
                <p class="text-blue-200 text-sm">No prior sales experience needed. Freshers and career switchers welcome.</p>
            </div>

            <div class="bg-white/5 backdrop-blur-sm border border-white/10 p-8 rounded-xl hover:bg-white/10 transition-all hover:-translate-y-1" data-aos="fade-up" data-aos-delay="300">
                <div class="text-[#2fcaf0] text-3xl mb-4"><i class="fa-solid fa-scale-balanced"></i></div>
                <h3 class="text-xl font-bold mb-2">Inclusive & Equal Platform</h3>
                <p class="text-blue-200 text-sm">No gender bias — both men and women are encouraged to participate.</p>
            </div>

            <div class="bg-white/5 backdrop-blur-sm border border-white/10 p-8 rounded-xl hover:bg-white/10 transition-all hover:-translate-y-1" data-aos="fade-up" data-aos-delay="400">
                <div class="text-[#2fcaf0] text-3xl mb-4"><i class="fa-solid fa-chart-line"></i></div>
                <h3 class="text-xl font-bold mb-2">Performance-Linked Earnings</h3>
                <p class="text-blue-200 text-sm">Work at your own pace with task-based earning opportunities.</p>
            </div>

            <div class="bg-white/5 backdrop-blur-sm border border-white/10 p-8 rounded-xl hover:bg-white/10 transition-all hover:-translate-y-1" data-aos="fade-up" data-aos-delay="500">
                <div class="text-[#2fcaf0] text-3xl mb-4"><i class="fa-solid fa-calendar-check"></i></div>
                <h3 class="text-xl font-bold mb-2">Reliable Monthly Payouts</h3>
                <p class="text-blue-200 text-sm">Earnings settled monthly for clarity and predictability.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-20 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12" data-aos="fade-up">
            <h2 class="text-3xl font-extrabold text-[#173978]">Who Can Apply?</h2>
            <p class="text-gray-500 mt-2">Open to anyone willing to learn, execute, and grow.</p>
        </div>

        <div class="flex flex-wrap justify-center gap-4 md:gap-8">
            <div class="bg-white px-8 py-4 rounded-full shadow-sm border border-gray-100 font-bold text-[#173978] flex items-center gap-3">
                <i class="fa-solid fa-user text-[#2fcaf0]"></i> Individuals seeking flexibility
            </div>
            <div class="bg-white px-8 py-4 rounded-full shadow-sm border border-gray-100 font-bold text-[#173978] flex items-center gap-3">
                <i class="fa-solid fa-briefcase text-[#2fcaf0]"></i> Freelancers & Field Pros
            </div>
            <div class="bg-white px-8 py-4 rounded-full shadow-sm border border-gray-100 font-bold text-[#173978] flex items-center gap-3">
                <i class="fa-solid fa-users text-[#2fcaf0]"></i> Sales Executives
            </div>
            <div class="bg-white px-8 py-4 rounded-full shadow-sm border border-gray-100 font-bold text-[#173978] flex items-center gap-3">
                <i class="fa-solid fa-building text-[#2fcaf0]"></i> Small Agencies
            </div>
        </div>
    </div>
</section>

<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-3xl font-extrabold text-[#173978]">Voices of Our Growth Partners</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-gray-50 p-8 rounded-2xl relative" data-aos="fade-up">
                <i class="fa-solid fa-quote-left text-4xl text-[#2fcaf0] opacity-30 absolute top-6 left-6"></i>
                <p class="text-gray-600 italic mb-6 relative z-10 pt-4">
                    "Partnering with Bisani Brothers gave me the flexibility I was looking for. The training was clear, support was always available, and the work structure helped me perform better with confidence."
                </p>
                <div>
                    <h4 class="font-bold text-[#173978]">Rahul Verma</h4>
                    <p class="text-xs text-gray-400 uppercase font-bold">Field Sales Associate</p>
                </div>
            </div>

            <div class="bg-gray-50 p-8 rounded-2xl relative" data-aos="fade-up" data-aos-delay="100">
                <i class="fa-solid fa-quote-left text-4xl text-[#2fcaf0] opacity-30 absolute top-6 left-6"></i>
                <p class="text-gray-600 italic mb-6 relative z-10 pt-4">
                    "I didn’t come from a sales background, but the BBPL team guided me at every step. The work hours are flexible, and payments are reliable. Great opportunity for women."
                </p>
                <div>
                    <h4 class="font-bold text-[#173978]">Neha Sharma</h4>
                    <p class="text-xs text-gray-400 uppercase font-bold">Growth Partner – Operations</p>
                </div>
            </div>

            <div class="bg-gray-50 p-8 rounded-2xl relative" data-aos="fade-up" data-aos-delay="200">
                <i class="fa-solid fa-quote-left text-4xl text-[#2fcaf0] opacity-30 absolute top-6 left-6"></i>
                <p class="text-gray-600 italic mb-6 relative z-10 pt-4">
                    "As an agency, we were looking for consistent projects. Working with Bisani Brothers has been smooth — clear communication and long-term collaboration. It feels like a partnership."
                </p>
                <div>
                    <h4 class="font-bold text-[#173978]">Amit Kulkarni</h4>
                    <p class="text-xs text-gray-400 uppercase font-bold">Agency Partner</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-24 bg-[#173978] relative" id="register">
    <div class="absolute inset-0 bg-[url('assets/images/pattern.png')] opacity-5"></div>

    <div class="max-w-4xl mx-auto px-4 relative z-10">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row">
            
            <div class="w-full md:w-2/5 bg-gradient-to-br from-blue-50 to-white p-10 flex flex-col justify-center">
                <h3 class="text-2xl font-bold text-[#173978] mb-4">Join Our Network</h3>
                <p class="text-gray-600 mb-8">Fill out the form to start your journey with Bisani Brothers.</p>
                
                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-[#173978] text-white flex items-center justify-center shrink-0">1</div>
                        <p class="text-sm font-bold text-gray-700">Register</p>
                    </div>
                    <div class="w-0.5 h-6 bg-gray-200 ml-5"></div>
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center shrink-0">2</div>
                        <p class="text-sm font-bold text-gray-400">Get Trained</p>
                    </div>
                    <div class="w-0.5 h-6 bg-gray-200 ml-5"></div>
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center shrink-0">3</div>
                        <p class="text-sm font-bold text-gray-400">Start Earning</p>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-3/5 p-10">
                <h3 class="text-xl font-bold text-[#173978] mb-6 border-b pb-2">Growth Partner Registration</h3>
                
                <form action="" method="POST" class="space-y-5">
                    <input type="text" name="website_check" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">

                    <?php if ($form_error !== ''): ?>
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo htmlspecialchars($form_error); ?></div>
                    <?php endif; ?>

                    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">Application submitted successfully. Our team will contact you soon.</div>
                    <?php endif; ?>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Full Name</label>
                            <input type="text" name="name" class="w-full border border-gray-300 rounded-lg p-2.5 focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Mobile</label>
                            <input type="tel" name="mobile" class="w-full border border-gray-300 rounded-lg p-2.5 focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">City & State</label>
                            <input type="text" name="city" class="w-full border border-gray-300 rounded-lg p-2.5 focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] outline-none" required>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Email</label>
                            <input type="email" name="email" class="w-full border border-gray-300 rounded-lg p-2.5 focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] outline-none" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Are you applying as:</label>
                        <div class="flex gap-6">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="applicant_type" value="individual" checked onchange="toggleAgencyFields('individual')" class="text-[#173978] focus:ring-[#173978]">
                                <span class="ml-2 text-sm font-medium text-gray-700">Individual</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="applicant_type" value="agency" onchange="toggleAgencyFields('agency')" class="text-[#173978] focus:ring-[#173978]">
                                <span class="ml-2 text-sm font-medium text-gray-700">Agency / Team</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Current Occupation</label>
                            <input type="text" name="occupation" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Experience (Yrs)</label>
                         <input type="number" name="experience" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Work Preference</label>
                        <select name="work_type" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none bg-white">
                            <option>Part-time</option>
                            <option>Full-time</option>
                        </select>
                    </div>

                    <div id="agency-fields" class="hidden p-4 bg-blue-50 rounded-lg border border-blue-100 space-y-3">
                        <p class="text-xs font-bold text-[#173978] uppercase mb-2">Agency Details</p>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Team Size</label>
                           <input type="number" name="team_size" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Regions / Cities Covered</label>
                            <input type="text" name="regions" class="w-full border border-gray-300 rounded-lg p-2.5 outline-none" required>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 mt-4">
                        <input type="checkbox" required class="mt-1">
                        <p class="text-xs text-gray-500">I confirm that the information shared is accurate and I am interested in partnering with Bisani Brothers Private Limited.</p>
                    </div>

                    <button type="submit" class="w-full bg-[#cb595c] hover:bg-[#b84b4e] text-white font-bold py-3.5 rounded-lg shadow-lg hover:shadow-xl transition-all">
                        Apply Now
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>