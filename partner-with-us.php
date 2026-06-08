<?php
session_start();
require_once __DIR__ . '/includes/security.php';
security_bootstrap();
require 'db.php';

// 2. BACKEND LOGIC (Handled on the same page)
$status_msg = "";
$status_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- A. BOT PROTECTION (HONEYPOT) ---
    if (!empty($_POST['website_check'])) {
        die("Spam detected.");
    }

    // --- B. PREVENT LINKS (SPAM PROTECTION) ---
    $all_data = implode(" ", $_POST);
    if (preg_match('/http|www\.|ftp/i', $all_data)) {
        require_once __DIR__ . '/includes/locale.php';
        echo "<script>alert('Error: Links are not allowed in this form.'); window.location.href='" . htmlspecialchars(locale_url('partner-with-us'), ENT_QUOTES) . "';</script>";
        exit();
    }

    require_once 'includes/enquiry-helpers.php';

    $name = enquiry_normalize_field($_POST['name'] ?? '');
    $email = enquiry_normalize_field($_POST['email'] ?? '');
    $mobile = enquiry_normalize_field($_POST['phone'] ?? '');
    $applicant_type = enquiry_normalize_field($_POST['partner_type'] ?? '');
    $message = enquiry_normalize_field($_POST['message'] ?? '');

    if ($name === '' || mb_strlen($name) < 2) {
        $status_msg = 'Please enter your full name.';
        $status_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $status_msg = 'Please enter a valid email address.';
        $status_type = 'error';
    } elseif (strlen(enquiry_phone_last10($mobile)) < 10) {
        $status_msg = 'Please enter a valid 10-digit mobile number.';
        $status_type = 'error';
    } elseif ($message === '' || mb_strlen($message) < 5) {
        $status_msg = 'Please enter your message (at least 5 characters).';
        $status_type = 'error';
    } else {
        $created_at = date('Y-m-d H:i:s');

        try {
            $sql = "INSERT INTO growth_partners (name, mobile, email, applicant_type, message, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$name, $mobile, $email, $applicant_type, $message, $created_at]);

            if ($result) {
                require_once __DIR__ . '/includes/locale.php';
                header('Location: ' . locale_url('partner-with-us?status=success'));
                exit();
            }

            $status_msg = "Error submitting application.";
            $status_type = "error";
        } catch (PDOException $e) {
            error_log('partner-with-us submit: ' . $e->getMessage());
            $status_msg = 'Unable to submit application. Please try again.';
            $status_type = 'error';
        }
    }
}

// 3. PAGE CONTENT START
include 'includes/header.php';
?>

<?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
<div id="success-alert" class="fixed top-24 left-1/2 transform -translate-x-1/2 z-50 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-2xl flex items-center animate-bounce bb-alert-min-w-300">
    <i class="fa-solid fa-check-circle text-2xl mr-3"></i>
    <div>
        <p class="font-bold"><?php echo page_te('success_title'); ?></p>
        <p class="text-sm"><?php echo page_te('success_desc'); ?></p>
    </div>
    <button onclick="document.getElementById('success-alert').remove()" class="ml-auto pl-6 text-green-700 font-bold hover:text-green-900">✕</button>
</div>
<script>
    setTimeout(() => { 
        const alert = document.getElementById('success-alert');
        if(alert) { alert.style.opacity = '0'; setTimeout(() => alert.remove(), 500); }
    }, 6000);
</script>
<?php endif; ?>

<?php if($status_msg && $status_type == 'error'): ?>
    <div class="fixed top-24 left-1/2 transform -translate-x-1/2 z-50 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-xl">
        <p><strong>Error:</strong> <?php echo security_e($status_msg); ?></p>
    </div>
<?php endif; ?>

<section class="relative h-[80vh] min-h-[750px] md:py-40 flex items-center justify-center overflow-hidden">
    <div class="absolute inset-0 z-0">
        <img src="assets/bg/Partner_With_Us.webp" alt="Partner With Us" class="w-full h-full object-cover object-center">
        <div class="absolute inset-0 bg-black/40"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full flex items-center" data-aos="fade-up">
        <div class="max-w-3xl bg-[#173978]/50 backdrop-blur-md p-10 md:p-16 rounded-[2rem] shadow-3xl border border-white/10 my-auto">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight mb-6 leading-tight text-white">
                <?php echo page_te('hero_title'); ?>
            </h1>
            <p class="text-lg md:text-2xl text-blue-50 font-light mb-10 leading-relaxed">
                <?php echo page_te('hero_desc'); ?>
            </p>
            <button onclick="scrollToApply()" class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-base font-bold rounded text-[#052c65] bg-[#2fcaf0] hover:bg-white md:text-lg transition-all duration-300 shadow-[0_0_20px_rgba(47,202,240,0.3)] hover:shadow-[0_0_30px_rgba(47,202,240,0.6)] cursor-pointer group">
                <?php echo page_te('hero_btn'); ?>
                <i class="fa-solid fa-arrow-right ml-3 transition-transform group-hover:translate-x-1"></i>
            </button>
        </div>
    </div>
</section>

<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center" data-aos="fade-up">
        <p class="text-xl text-gray-600 leading-relaxed font-medium mb-6">
            <?php echo page_te('intro_p1'); ?>
        </p>
        <p class="text-lg text-gray-600 leading-relaxed mb-6">
            <?php echo page_te('intro_p2'); ?>
        </p>
        <p class="text-lg text-[#173978] font-bold">
            <?php echo page_te('intro_p3'); ?>
        </p>
    </div>
</section>

<section class="py-24 bg-[#f4f7fc] relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-extrabold text-[#173978]"><?php echo page_te('opp_title'); ?></h2>
            <p class="text-gray-400 mt-4 text-sm max-w-3xl mx-auto"><?php echo page_te('opp_subtitle'); ?></p>
        </div>

        <div class="flex flex-wrap justify-center gap-8 mb-12">
            <div class="w-full md:w-[calc(50%-1rem)] lg:w-[calc(33.333%-2rem)] bg-white p-8 rounded-[2rem] shadow-sm hover:shadow-lg transition-all group flex flex-col items-center text-center" data-aos="fade-up">
                <div class="w-14 h-14 bg-blue-50 text-[#173978] rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-[#173978] group-hover:text-white transition-colors">
                    <i class="fa-solid fa-handshake"></i>
                </div>
                <h3 class="text-lg font-bold text-[#173978] leading-tight"><?php echo page_te('opp1'); ?></h3>
            </div>
            <div class="w-full md:w-[calc(50%-1rem)] lg:w-[calc(33.333%-2rem)] bg-white p-8 rounded-[2rem] shadow-sm hover:shadow-lg transition-all group flex flex-col items-center text-center" data-aos="fade-up" data-aos-delay="100">
                <div class="w-14 h-14 bg-blue-50 text-[#173978] rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-[#173978] group-hover:text-white transition-colors">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <h3 class="text-lg font-bold text-[#173978] leading-tight"><?php echo page_te('opp2'); ?></h3>
            </div>
            <div class="w-full md:w-[calc(50%-1rem)] lg:w-[calc(33.333%-2rem)] bg-white p-8 rounded-[2rem] shadow-sm hover:shadow-lg transition-all group flex flex-col items-center text-center" data-aos="fade-up" data-aos-delay="200">
                <div class="w-14 h-14 bg-blue-50 text-[#173978] rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-[#173978] group-hover:text-white transition-colors">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <h3 class="text-lg font-bold text-[#173978] leading-tight"><?php echo page_te('opp3'); ?></h3>
            </div>
            <div class="w-full md:w-[calc(50%-1rem)] lg:w-[calc(33.333%-2rem)] bg-white p-8 rounded-[2rem] shadow-sm hover:shadow-lg transition-all group flex flex-col items-center text-center" data-aos="fade-up" data-aos-delay="300">
                <div class="w-14 h-14 bg-blue-50 text-[#173978] rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-[#173978] group-hover:text-white transition-colors">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
                <h3 class="text-lg font-bold text-[#173978] leading-tight"><?php echo page_te('opp4'); ?></h3>
            </div>
            <div class="w-full md:w-[calc(50%-1rem)] lg:w-[calc(33.333%-2rem)] bg-white p-8 rounded-[2rem] shadow-sm hover:shadow-lg transition-all group flex flex-col items-center text-center" data-aos="fade-up" data-aos-delay="400">
                <div class="w-14 h-14 bg-blue-50 text-[#173978] rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-[#173978] group-hover:text-white transition-colors">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <h3 class="text-lg font-bold text-[#173978] leading-tight"><?php echo page_te('opp5'); ?></h3>
            </div>
        </div>

        <div class="text-center" data-aos="fade-up">
            <p class="text-lg text-gray-600 font-medium"><?php echo page_te('opp_closing'); ?></p>
        </div>
    </div>
</section>

<section class="py-24 bg-[#173978] text-white relative overflow-hidden">
    <div class="absolute inset-0 opacity-5 pointer-events-none bb-dot-grid-white-subtle"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-3xl md:text-5xl font-extrabold mb-4"><?php echo page_te('offer_title'); ?></h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="bg-white/5 p-8 rounded-2xl border border-white/10 hover:bg-white/10 transition-colors" data-aos="fade-up"><h3 class="text-xl font-bold text-[#2fcaf0] mb-3"><?php echo page_te('offer1_t'); ?></h3><p class="text-blue-100 text-sm leading-relaxed"><?php echo page_te('offer1_d'); ?></p></div>
            <div class="bg-white/5 p-8 rounded-2xl border border-white/10 hover:bg-white/10 transition-colors" data-aos="fade-up" data-aos-delay="100"><h3 class="text-xl font-bold text-[#2fcaf0] mb-3"><?php echo page_te('offer2_t'); ?></h3><p class="text-blue-100 text-sm leading-relaxed"><?php echo page_te('offer2_d'); ?></p></div>
            <div class="bg-white/5 p-8 rounded-2xl border border-white/10 hover:bg-white/10 transition-colors" data-aos="fade-up" data-aos-delay="200"><h3 class="text-xl font-bold text-[#2fcaf0] mb-3"><?php echo page_te('offer3_t'); ?></h3><p class="text-blue-100 text-sm leading-relaxed"><?php echo page_te('offer3_d'); ?></p></div>
            <div class="bg-white/5 p-8 rounded-2xl border border-white/10 hover:bg-white/10 transition-colors" data-aos="fade-up" data-aos-delay="300"><h3 class="text-xl font-bold text-[#2fcaf0] mb-3"><?php echo page_te('offer4_t'); ?></h3><p class="text-blue-100 text-sm leading-relaxed"><?php echo page_te('offer4_d'); ?></p></div>
            <div class="bg-white/5 p-8 rounded-2xl border border-white/10 hover:bg-white/10 transition-colors" data-aos="fade-up" data-aos-delay="400"><h3 class="text-xl font-bold text-[#2fcaf0] mb-3"><?php echo page_te('offer5_t'); ?></h3><p class="text-blue-100 text-sm leading-relaxed"><?php echo page_te('offer5_d'); ?></p></div>
            <div class="bg-white/5 p-8 rounded-2xl border border-white/10 hover:bg-white/10 transition-colors" data-aos="fade-up" data-aos-delay="500"><h3 class="text-xl font-bold text-[#2fcaf0] mb-3"><?php echo page_te('offer6_t'); ?></h3><p class="text-blue-100 text-sm leading-relaxed"><?php echo page_te('offer6_d'); ?></p></div>
        </div>
    </div>
</section>

<section class="py-20 bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12"><h2 class="text-3xl font-extrabold text-[#173978]"><?php echo page_te('trust_title'); ?></h2></div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center divide-y md:divide-y-0 md:divide-x divide-gray-200">
            <div class="p-4" data-aos="zoom-in"><div class="text-4xl font-extrabold text-[#173978] mb-2"><?php echo page_te('trust1_n'); ?></div><div class="text-gray-600 font-medium text-sm"><?php echo page_te('trust1_d'); ?></div></div>
            <div class="p-4" data-aos="zoom-in" data-aos-delay="100"><div class="text-4xl font-extrabold text-[#173978] mb-2"><?php echo page_te('trust2_n'); ?></div><div class="text-gray-600 font-medium text-sm"><?php echo page_te('trust2_d'); ?></div></div>
            <div class="p-4" data-aos="zoom-in" data-aos-delay="200"><div class="text-4xl font-extrabold text-[#173978] mb-2"><?php echo page_te('trust3_n'); ?></div><div class="text-gray-600 font-medium text-sm"><?php echo page_te('trust3_d'); ?></div></div>
            <div class="p-4" data-aos="zoom-in" data-aos-delay="300"><div class="text-4xl font-extrabold text-[#173978] mb-2"><?php echo page_te('trust4_n'); ?></div><div class="text-gray-600 font-medium text-sm"><?php echo page_te('trust4_d'); ?></div></div>
        </div>
    </div>
</section>

<section class="py-24 bg-[#f9fafb]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16" data-aos="fade-up"><h2 class="text-3xl md:text-4xl font-extrabold text-[#173978]"><?php echo page_te('voices_title'); ?></h2></div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-2xl shadow-lg border-t-4 border-[#2fcaf0]" data-aos="fade-up">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center text-[#173978] font-bold text-xl mr-4">R</div>
                    <div><h4 class="font-bold text-[#173978]">Rahul Verma</h4><p class="text-xs text-gray-500 uppercase tracking-wide">Field Sales Associate</p></div>
                </div>
                <p class="text-gray-600 italic leading-relaxed">“Working with Bisani Brothers gave me exposure to well-structured projects. The clarity in process and regular support helped me perform better and grow steadily.”</p>
            </div>
            <div class="bg-white p-8 rounded-2xl shadow-lg border-t-4 border-[#2fcaf0]" data-aos="fade-up" data-aos-delay="100">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center text-[#173978] font-bold text-xl mr-4">N</div>
                    <div><h4 class="font-bold text-[#173978]">Neha Sharma</h4><p class="text-xs text-gray-500 uppercase tracking-wide">On-ground Operations Partner</p></div>
                </div>
                <p class="text-gray-600 italic leading-relaxed">“The guidance and transparency stood out for me. It’s a dependable platform for anyone serious about working and building confidence through real projects.”</p>
            </div>
            <div class="bg-white p-8 rounded-2xl shadow-lg border-t-4 border-[#2fcaf0]" data-aos="fade-up" data-aos-delay="200">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center text-[#173978] font-bold text-xl mr-4">A</div>
                    <div><h4 class="font-bold text-[#173978]">Amit Kulkarni</h4><p class="text-xs text-gray-500 uppercase tracking-wide">Agency Partner</p></div>
                </div>
                <p class="text-gray-600 italic leading-relaxed">“We were looking for consistent execution projects for our team. The coordination, planning, and payment structure made this a reliable long-term association.”</p>
            </div>
        </div>
    </div>
</section>

<section id="application-section" class="py-24 bg-white relative">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <div class="bg-[#173978] rounded-[2.5rem] p-8 md:p-12 shadow-2xl text-white">
            <div class="text-center mb-10">
                <h2 class="text-3xl md:text-4xl font-extrabold mb-4"><?php echo page_te('apply_title'); ?></h2>
                <p class="text-blue-200"><?php echo page_te('apply_subtitle'); ?></p>
            </div>

            <form action="" method="POST" id="partner-form" class="space-y-6">
                
                <input type="text" name="website_check" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-blue-200 mb-2"><?php echo page_te('form_name'); ?></label>
                        <input type="text" name="name" class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 focus:border-[#2fcaf0] focus:ring-2 focus:ring-[#2fcaf0]/50 outline-none transition-all text-white placeholder-blue-300/50" placeholder="Your Name" required>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-blue-200 mb-2"><?php echo page_te('form_email'); ?></label>
                        <input type="email" name="email" class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 focus:border-[#2fcaf0] focus:ring-2 focus:ring-[#2fcaf0]/50 outline-none transition-all text-white placeholder-blue-300/50" placeholder="your@email.com" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-blue-200 mb-2"><?php echo page_te('form_phone'); ?></label>
                        <input type="tel" name="phone" class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 focus:border-[#2fcaf0] focus:ring-2 focus:ring-[#2fcaf0]/50 outline-none transition-all text-white placeholder-blue-300/50" placeholder="+91..." required>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-blue-200 mb-2"><?php echo page_te('form_partner_type'); ?></label>
                        <select name="partner_type" class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 focus:border-[#2fcaf0] focus:ring-2 focus:ring-[#2fcaf0]/50 outline-none transition-all text-white bg-[#173978]" required>
                            <option value="" class="text-gray-300"><?php echo page_te('form_select'); ?></option>
                            <option value="Individual" class="text-white bg-[#173978]"><?php echo page_te('form_individual'); ?></option>
                            <option value="Agency" class="text-white bg-[#173978]"><?php echo page_te('form_agency'); ?></option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-blue-200 mb-2"><?php echo page_te('form_message_opt'); ?></label>
                    <textarea name="message" rows="3" class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 focus:border-[#2fcaf0] focus:ring-2 focus:ring-[#2fcaf0]/50 outline-none transition-all text-white placeholder-blue-300/50" placeholder="Tell us about your experience..." required></textarea>
                </div>

                <div class="text-center pt-4">
                    <button type="submit" class="w-full md:w-auto px-12 py-4 bg-[#2fcaf0] text-[#173978] font-bold rounded-lg shadow-lg hover:bg-white transition-all transform hover:-translate-y-1 text-lg">
                        <?php echo page_te('form_apply'); ?>
                    </button>
                </div>

            </form>
        </div>

    </div>
</section>

<script>
    function scrollToApply() {
        const section = document.getElementById('application-section');
        if (section) {
            section.scrollIntoView({ behavior: 'smooth' });
        }
    }
</script>

<?php include 'includes/footer.php'; ?>