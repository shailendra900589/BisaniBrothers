<?php
// 1. PREVENT HEADER ERRORS
session_start();
require 'db.php'; 

// --- BACKEND LOGIC: HANDLE FORM SUBMISSION ---
$status_msg = "";
$status_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!empty($_POST['website_check'])) {
        die("Spam detected.");
    }

    $all_data = implode(" ", $_POST);
    if (preg_match('/http|www\.|ftp/i', $all_data)) {
        echo "<script>alert('Error: Links are not allowed in this form.'); window.location.href='why-work-with-us.php';</script>";
        exit();
    }

    require_once 'includes/enquiry-helpers.php';

    $services = isset($_POST['services']) ? implode(", ", array_map('strval', (array) $_POST['services'])) : "";

    $result = enquiry_submit_business($pdo, [
        'name'     => $_POST['name'] ?? '',
        'email'    => $_POST['email'] ?? '',
        'phone'    => $_POST['phone'] ?? '',
        'industry' => $_POST['industry'] ?? '',
        'services' => $services,
        'message'  => $_POST['message'] ?? '',
    ]);

    if ($result['status'] === 'success') {
        header("Location: why-work-with-us.php?status=success");
        exit();
    }

    $status_msg = $result['message'] ?? 'Error submitting form. Please try again.';
}

// --- PAGE CONTENT START ---
include 'includes/header.php';
?>

<?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
<div id="success-alert" class="fixed top-24 left-1/2 transform -translate-x-1/2 z-50 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-2xl flex items-center animate-bounce" style="min-width: 300px;">
    <i class="fa-solid fa-check-circle text-2xl mr-3"></i>
    <div>
        <p class="font-bold"><?php echo page_te('success_title'); ?></p>
        <p class="text-sm"><?php echo page_te('success_desc'); ?></p>
    </div>
    <button onclick="document.getElementById('success-alert').remove()" class="ml-auto pl-6 text-green-700 font-bold hover:text-green-900">✕</button>
</div>
<script>setTimeout(() => { document.getElementById('success-alert').remove(); }, 6000);</script>
<?php endif; ?>

<?php if(!empty($status_msg)): ?>
    <script>alert(<?php echo json_encode($status_msg, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);</script>
<?php endif; ?>

<section class="relative h-[100vh] min-h-[700px] flex items-center relative overflow-hidden">
    
    <div class="absolute inset-0 z-0 ">
        <img src="assets/bg/Why_work_with_us.webp"
             alt="Execution team working together" 
             class="w-full h-full object-cover ">
        <div class="absolute inset-0 bg-black/10"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full flex items-center" data-aos="fade-up">
        
        <div class="max-w-4xl bg-[#173978]/50 backdrop-blur-sm p-10 md:p-16 rounded-[2rem] shadow-2xl border border-white/10">
            
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight mb-6 leading-tight text-white">
                <?php echo page_te('hero_h1_a'); ?> <br>
                <span class="text-[#2fcaf0]"><?php echo page_te('hero_h1_b'); ?></span>
            </h1>
            
            <p class="text-lg md:text-2xl text-blue-50 font-light mb-10 leading-relaxed">
                <?php echo page_te('hero_desc'); ?>
            </p>

            <button onclick="scrollToContact()" class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-base font-bold rounded text-[#052c65] bg-[#2fcaf0] hover:bg-white md:text-lg transition-all duration-300 shadow-[0_0_20px_rgba(47,202,240,0.3)] hover:shadow-[0_0_30px_rgba(47,202,240,0.6)] cursor-pointer group">
                <?php echo page_te('hero_btn'); ?>
                <i class="fa-solid fa-arrow-right ml-3 transition-transform group-hover:translate-x-1"></i>
            </button> </div>

    </div>
</section>

<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center" data-aos="fade-up">
        
        <h2 class="text-3xl md:text-4xl font-extrabold text-[#173978] mb-6">
            <?php echo page_te('scale_title'); ?>
        </h2>
        
        <p class="text-lg text-gray-600 leading-loose mb-6">
            <?php echo page_te('scale_p1'); ?>
        </p>

        <p class="text-lg text-gray-600 leading-loose">
            <?php echo page_te('scale_p2'); ?>
        </p>
    </div>
</section>

<section class="py-24 bg-[#f4f7fc] relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-extrabold text-[#173978]"><?php echo page_te('challenges_title'); ?></h2>
            <p class="text-gray-600 mt-4 text-lg"><?php echo page_te('challenges_sub'); ?></p>
        </div>

        <div class="flex flex-wrap justify-center gap-8">
            
            <div class="w-full md:w-[calc(50%-1rem)] lg:w-[calc(33.333%-2rem)] bg-white p-8 rounded-[2rem] shadow-sm hover:shadow-lg transition-all flex flex-col items-center text-center" data-aos="fade-up" data-aos-delay="100">
                <div class="w-12 h-12 bg-red-50 text-[#cb595c] rounded-full flex items-center justify-center text-xl mb-4"><i class="fa-solid fa-users-slash"></i></div>
                <h3 class="text-lg font-bold text-[#173978] mb-2"><?php echo page_te('ch1_t'); ?></h3>
                <p class="text-gray-600"><?php echo page_te('ch1_d'); ?></p>
            </div>

            <div class="w-full md:w-[calc(50%-1rem)] lg:w-[calc(33.333%-2rem)] bg-white p-8 rounded-[2rem] shadow-sm hover:shadow-lg transition-all flex flex-col items-center text-center" data-aos="fade-up" data-aos-delay="200">
                <div class="w-12 h-12 bg-red-50 text-[#cb595c] rounded-full flex items-center justify-center text-xl mb-4"><i class="fa-solid fa-map-location-dot"></i></div>
                <h3 class="text-lg font-bold text-[#173978] mb-2"><?php echo page_te('ch2_t'); ?></h3>
                <p class="text-gray-600"><?php echo page_te('ch2_d'); ?></p>
            </div>

            <div class="w-full md:w-[calc(50%-1rem)] lg:w-[calc(33.333%-2rem)] bg-white p-8 rounded-[2rem] shadow-sm hover:shadow-lg transition-all flex flex-col items-center text-center" data-aos="fade-up" data-aos-delay="300">
                <div class="w-12 h-12 bg-red-50 text-[#cb595c] rounded-full flex items-center justify-center text-xl mb-4"><i class="fa-solid fa-eye-slash"></i></div>
                <h3 class="text-lg font-bold text-[#173978] mb-2"><?php echo page_te('ch3_t'); ?></h3>
                <p class="text-gray-600"><?php echo page_te('ch3_d'); ?></p>
            </div>

            <div class="w-full md:w-[calc(50%-1rem)] lg:w-[calc(33.333%-2rem)] bg-white p-8 rounded-[2rem] shadow-sm hover:shadow-lg transition-all flex flex-col items-center text-center" data-aos="fade-up" data-aos-delay="400">
                <div class="w-12 h-12 bg-red-50 text-[#cb595c] rounded-full flex items-center justify-center text-xl mb-4"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <h3 class="text-lg font-bold text-[#173978] mb-2"><?php echo page_te('ch4_t'); ?></h3>
                <p class="text-gray-600"><?php echo page_te('ch4_d'); ?></p>
            </div>

            <div class="w-full md:w-[calc(50%-1rem)] lg:w-[calc(33.333%-2rem)] bg-white p-8 rounded-[2rem] shadow-sm hover:shadow-lg transition-all flex flex-col items-center text-center" data-aos="fade-up" data-aos-delay="500">
                <div class="w-12 h-12 bg-red-50 text-[#cb595c] rounded-full flex items-center justify-center text-xl mb-4"><i class="fa-solid fa-chart-line-down"></i></div>
                <h3 class="text-lg font-bold text-[#173978] mb-2"><?php echo page_te('ch5_t'); ?></h3>
                <p class="text-gray-600"><?php echo page_te('ch5_d'); ?></p>
            </div>

        </div>
    </div>
</section>

<section class="py-24 bg-[#173978] text-white relative overflow-hidden">
    <div class="absolute inset-0 opacity-5 pointer-events-none" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px;"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-3xl md:text-5xl font-extrabold mb-4"><?php echo page_te('support_title'); ?></h2>
            <p class="text-blue-100 text-lg"><?php echo page_te('support_sub'); ?></p>
        </div>

        <div class="flex flex-wrap justify-center gap-8">

            <div class="w-full md:w-[calc(50%-1rem)] lg:w-[calc(33.333%-2rem)] bg-white rounded-[2rem] p-8 text-left group hover:-translate-y-2 transition-transform duration-300" data-aos="fade-up">
                <div class="w-16 h-16 bg-[#eefbfe] rounded-full flex items-center justify-center text-[#2fcaf0] text-3xl mb-6">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h3 class="text-xl font-bold text-[#173978] mb-4"><?php echo page_te('svc1_t'); ?></h3>
                <p class="text-gray-600 mb-6 text-sm leading-relaxed">
                    <?php echo page_te('svc1_d'); ?>
                </p>
                <a href="sales-growth" class="text-[#173978] font-bold text-sm uppercase tracking-wide group-hover:text-[#2fcaf0] transition-colors">
                    <?php echo page_te('svc_link'); ?> <i class="fa-solid fa-arrow-right ml-2"></i>
                </a>
            </div>

            <div class="w-full md:w-[calc(50%-1rem)] lg:w-[calc(33.333%-2rem)] bg-white rounded-[2rem] p-8 text-left group hover:-translate-y-2 transition-transform duration-300" data-aos="fade-up" data-aos-delay="100">
                <div class="w-16 h-16 bg-[#eefbfe] rounded-full flex items-center justify-center text-[#2fcaf0] text-3xl mb-6">
                    <i class="fa-solid fa-magnifying-glass-chart"></i>
                </div>
                <h3 class="text-xl font-bold text-[#173978] mb-4"><?php echo page_te('svc2_t'); ?></h3>
                <p class="text-gray-600 mb-6 text-sm leading-relaxed">
                    <?php echo page_te('svc2_d'); ?>
                </p>
                <a href="survey-market-research" class="text-[#173978] font-bold text-sm uppercase tracking-wide group-hover:text-[#2fcaf0] transition-colors">
                    <?php echo page_te('svc_link'); ?> <i class="fa-solid fa-arrow-right ml-2"></i>
                </a>
            </div>

            <div class="w-full md:w-[calc(50%-1rem)] lg:w-[calc(33.333%-2rem)] bg-white rounded-[2rem] p-8 text-left group hover:-translate-y-2 transition-transform duration-300" data-aos="fade-up" data-aos-delay="200">
                <div class="w-16 h-16 bg-[#eefbfe] rounded-full flex items-center justify-center text-[#2fcaf0] text-3xl mb-6">
                    <i class="fa-solid fa-users-gear"></i>
                </div>
                <h3 class="text-xl font-bold text-[#173978] mb-4"><?php echo page_te('svc3_t'); ?></h3>
                <p class="text-gray-600 mb-6 text-sm leading-relaxed">
                    <?php echo page_te('svc3_d'); ?>
                </p>
                <a href="staffing-solutions" class="text-[#173978] font-bold text-sm uppercase tracking-wide group-hover:text-[#2fcaf0] transition-colors">
                    <?php echo page_te('svc_link'); ?> <i class="fa-solid fa-arrow-right ml-2"></i>
                </a>
            </div>

            <div class="w-full md:w-[calc(50%-1rem)] lg:w-[calc(33.333%-2rem)] bg-white rounded-[2rem] p-8 text-left group hover:-translate-y-2 transition-transform duration-300" data-aos="fade-up" data-aos-delay="300">
                <div class="w-16 h-16 bg-[#eefbfe] rounded-full flex items-center justify-center text-[#2fcaf0] text-3xl mb-6">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <h3 class="text-xl font-bold text-[#173978] mb-4"><?php echo page_te('svc4_t'); ?></h3>
                <p class="text-gray-600 mb-6 text-sm leading-relaxed">
                    <?php echo page_te('svc4_d'); ?>
                </p>
                <a href="btl-atl" class="text-[#173978] font-bold text-sm uppercase tracking-wide group-hover:text-[#2fcaf0] transition-colors">
                    <?php echo page_te('svc_link'); ?> <i class="fa-solid fa-arrow-right ml-2"></i>
                </a>
            </div>

            <div class="w-full md:w-[calc(50%-1rem)] lg:w-[calc(33.333%-2rem)] bg-white rounded-[2rem] p-8 text-left group hover:-translate-y-2 transition-transform duration-300" data-aos="fade-up" data-aos-delay="400">
                <div class="w-16 h-16 bg-[#eefbfe] rounded-full flex items-center justify-center text-[#2fcaf0] text-3xl mb-6">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
                <h3 class="text-xl font-bold text-[#173978] mb-4"><?php echo page_te('svc5_t'); ?></h3>
                <p class="text-gray-600 mb-6 text-sm leading-relaxed">
                    <?php echo page_te('svc5_d'); ?>
                </p>
                <a href="lending-collection" class="text-[#173978] font-bold text-sm uppercase tracking-wide group-hover:text-[#2fcaf0] transition-colors">
                    <?php echo page_te('svc_link'); ?> <i class="fa-solid fa-arrow-right ml-2"></i>
                </a>
            </div>

        </div>
    </div>
</section>

<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-extrabold text-[#173978]"><?php echo page_te('work_title'); ?></h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
            
            <div class="group" data-aos="fade-up">
                <div class="w-20 h-20 mx-auto bg-gray-50 rounded-full flex items-center justify-center text-[#173978] border-2 border-[#173978] text-2xl font-bold mb-6 group-hover:bg-[#173978] group-hover:text-white transition-all">01</div>
                <h3 class="text-xl font-bold text-[#173978] mb-3"><?php echo page_te('step1_t'); ?></h3>
                <p class="text-gray-600 text-sm"><?php echo page_te('step1_d'); ?></p>
            </div>

            <div class="group" data-aos="fade-up" data-aos-delay="100">
                <div class="w-20 h-20 mx-auto bg-gray-50 rounded-full flex items-center justify-center text-[#173978] border-2 border-[#173978] text-2xl font-bold mb-6 group-hover:bg-[#173978] group-hover:text-white transition-all">02</div>
                <h3 class="text-xl font-bold text-[#173978] mb-3"><?php echo page_te('step2_t'); ?></h3>
                <p class="text-gray-600 text-sm"><?php echo page_te('step2_d'); ?></p>
            </div>

            <div class="group" data-aos="fade-up" data-aos-delay="200">
                <div class="w-20 h-20 mx-auto bg-gray-50 rounded-full flex items-center justify-center text-[#173978] border-2 border-[#173978] text-2xl font-bold mb-6 group-hover:bg-[#173978] group-hover:text-white transition-all">03</div>
                <h3 class="text-xl font-bold text-[#173978] mb-3"><?php echo page_te('step3_t'); ?></h3>
                <p class="text-gray-600 text-sm"><?php echo page_te('step3_d'); ?></p>
            </div>

            <div class="group" data-aos="fade-up" data-aos-delay="300">
                <div class="w-20 h-20 mx-auto bg-gray-50 rounded-full flex items-center justify-center text-[#173978] border-2 border-[#173978] text-2xl font-bold mb-6 group-hover:bg-[#173978] group-hover:text-white transition-all">04</div>
                <h3 class="text-xl font-bold text-[#173978] mb-3"><?php echo page_te('step4_t'); ?></h3>
                <p class="text-gray-600 text-sm"><?php echo page_te('step4_d'); ?></p>
            </div>

        </div>
    </div>
</section>

<section id="contact-section" class="py-24 bg-[#f4f7fc] relative">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-5xl font-extrabold text-[#173978] mb-4"><?php echo page_te('connect_title'); ?></h2>
            <p class="text-sm text-gray-600 max-w-2xl mx-auto">
                <?php echo page_te('connect_sub'); ?>
            </p>
        </div>

        <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-xl border border-gray-100" data-aos="zoom-in">
            <h3 class="text-2xl font-bold text-[#173978] mb-8 border-b pb-4"><?php echo page_te('form_talk'); ?></h3>
            
            <form action="" method="POST" class="space-y-6">
                
                <input type="text" name="website_check" style="display:none !important;" tabindex="-1" autocomplete="off" >

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2"><?php echo page_te('form_name'); ?></label>
                        <input type="text" name="name" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-[#2fcaf0] focus:ring-2 focus:ring-[#2fcaf0]/20 outline-none transition-all" placeholder="<?php echo page_te('form_ph_name'); ?>" required>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2"><?php echo page_te('form_email'); ?></label>
                        <input type="email" name="email" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-[#2fcaf0] focus:ring-2 focus:ring-[#2fcaf0]/20 outline-none transition-all" placeholder="<?php echo page_te('form_ph_email'); ?>" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2"><?php echo page_te('form_phone_label'); ?></label>
                        <input type="tel" name="phone" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-[#2fcaf0] focus:ring-2 focus:ring-[#2fcaf0]/20 outline-none transition-all" placeholder="+91 98765 43210" required>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2"><?php echo page_te('form_industry'); ?></label>
                        <select name="industry" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-[#2fcaf0] focus:ring-2 focus:ring-[#2fcaf0]/20 outline-none transition-all bg-white" required>
                            <option value="" disabled selected><?php echo page_te('form_industry_ph'); ?></option>
                            <option value="FinTech / BFSI">FinTech / BFSI</option>
                            <option value="Lending">Lending</option>
                            <option value="FMCG / Retail">FMCG / Retail</option>
                            <option value="Education">Education</option>
                            <option value="E-commerce">E-commerce</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-4"><?php echo page_te('form_services'); ?></label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <label class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="services[]" value="Sales & Growth" class="w-5 h-5 text-[#173978] rounded focus:ring-[#2fcaf0]" required>
                            <span class="text-gray-700 font-medium">Sales & Growth Solutions</span>
                        </label>
                        <label class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="services[]" value="Survey & Market Research" class="w-5 h-5 text-[#173978] rounded focus:ring-[#2fcaf0]" required>
                            <span class="text-gray-700 font-medium">Survey & Market Research</span>
                        </label>
                        <label class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="services[]" value="Staffing Solutions" class="w-5 h-5 text-[#173978] rounded focus:ring-[#2fcaf0]" required>
                            <span class="text-gray-700 font-medium">Staffing Solutions</span>
                        </label>
                        <label class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="services[]" value="BTL & ATL Activation" class="w-5 h-5 text-[#173978] rounded focus:ring-[#2fcaf0]" required>
                            <span class="text-gray-700 font-medium">BTL & ATL Activation</span>
                        </label>
                        <label class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="services[]" value="Lending & Collections" class="w-5 h-5 text-[#173978] rounded focus:ring-[#2fcaf0]" required>
                            <span class="text-gray-700 font-medium">Lending & Collections</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2"><?php echo page_te('form_brief'); ?></label>
                    <textarea name="message" rows="4" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-[#2fcaf0] focus:ring-2 focus:ring-[#2fcaf0]/20 outline-none transition-all" placeholder="<?php echo page_te('form_brief_ph'); ?>" required></textarea>
                </div>

                <div class="text-center pt-4">
                    <button type="submit" class="w-full md:w-auto px-10 py-4 bg-[#173978] text-white font-bold rounded-lg shadow-lg hover:bg-[#2fcaf0] hover:text-[#173978] transition-all transform hover:-translate-y-1 text-lg">
                        <?php echo page_te('form_submit_req'); ?>
                    </button>
                </div>

            </form>
        </div>

    </div>
</section>

<script>
    function scrollToContact() {
        const section = document.getElementById('contact-section');
        if (section) {
            section.scrollIntoView({ behavior: 'smooth' });
        }
    }
</script>

<?php include 'includes/footer.php'; ?>