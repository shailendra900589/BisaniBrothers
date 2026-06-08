<?php
session_start();
require 'db.php';
require_once 'includes/enquiry-form-handler.php';
enquiry_process_general_form($pdo, 'Survey & Market Research Page');

// --- PAGE CONTENT START ---
include 'includes/header.php';
$_introHl = page_t('intro_highlight');
$_introBody = page_t('intro_desc');
$_introPrefix = 'Our ' . $_introHl;
if (str_starts_with($_introBody, $_introPrefix)) {
    $_introBody = ltrim(substr($_introBody, strlen($_introPrefix)));
}
?>

<?php enquiry_render_status_alerts(); ?>

<section class="relative h-[100vh] min-h-[700px] flex items-center relative overflow-hidden">
    
    <div class="absolute inset-0 z-0">
        <img src="assets/bg/Survery_Marketing_Research.webp"
             alt="Market insights network background" 
             class="w-full h-full object-cover object-center">
        
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

            <button onclick="scrollToSurvey()" class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-base font-bold rounded text-[#052c65] bg-[#2fcaf0] hover:bg-white md:text-lg transition-all duration-300 shadow-[0_0_20px_rgba(47,202,240,0.3)] hover:shadow-[0_0_30px_rgba(47,202,240,0.6)] cursor-pointer group">
                <?php echo page_te('know_more'); ?>
                <i class="fa-solid fa-arrow-right ml-3 transition-transform group-hover:translate-x-1"></i>
            </button>

        </div>

    </div>
</section>

<section class="py-24 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center" data-aos="fade-up">
        
        <h2 class="text-3xl md:text-4xl font-extrabold text-[#173978] mb-8 leading-tight">
            <?php echo page_te('intro_title'); ?>
        </h2>
        
        <p class="text-xl text-gray-600 leading-relaxed max-w-3xl mx-auto font-medium">
            Our <span class="font-extrabold text-[#173978] relative after:absolute after:bottom-0 after:left-0 after:w-full after:h-[3px] after:bg-[#2fcaf0]/30 after:-z-10"><?php echo page_te('intro_highlight'); ?></span> <?php echo htmlspecialchars($_introBody, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </div>
</section>

<section class="py-24 bg-[#f4f7fc] relative overflow-hidden">
    <div class="absolute top-0 right-0 w-96 h-96 bg-[#2fcaf0]/5 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-extrabold text-[#173978]"><?php echo page_te('challenges_title'); ?></h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <div class="bg-white p-8 md:p-10 rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 group" data-aos="fade-up" data-aos-delay="100">
                <div class="flex items-start gap-5">
                    <div class="w-14 h-14 rounded-full bg-[#fff5f5] flex-shrink-0 flex items-center justify-center text-[#cb595c] group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-eye-slash text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-[#173978] mb-3"><?php echo page_te('c1_title'); ?></h3>
                        <p class="text-gray-600 leading-relaxed">
                            <?php echo page_te('c1_desc'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-8 md:p-10 rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 group" data-aos="fade-up" data-aos-delay="200">
                <div class="flex items-start gap-5">
                    <div class="w-14 h-14 rounded-full bg-[#fff5f5] flex-shrink-0 flex items-center justify-center text-[#cb595c] group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-database text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-[#173978] mb-3"><?php echo page_te('c2_title'); ?></h3>
                        <p class="text-gray-600 leading-relaxed">
                            <?php echo page_te('c2_desc'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-8 md:p-10 rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 group" data-aos="fade-up" data-aos-delay="300">
                <div class="flex items-start gap-5">
                    <div class="w-14 h-14 rounded-full bg-[#fff5f5] flex-shrink-0 flex items-center justify-center text-[#cb595c] group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-clipboard-question text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-[#173978] mb-3"><?php echo page_te('c3_title'); ?></h3>
                        <p class="text-gray-600 leading-relaxed">
                            <?php echo page_te('c3_desc'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-8 md:p-10 rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 group" data-aos="fade-up" data-aos-delay="400">
                <div class="flex items-start gap-5">
                    <div class="w-14 h-14 rounded-full bg-[#fff5f5] flex-shrink-0 flex items-center justify-center text-[#cb595c] group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-[#173978] mb-3"><?php echo page_te('c4_title'); ?></h3>
                        <p class="text-gray-600 leading-relaxed">
                            <?php echo page_te('c4_desc'); ?>
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<section class="py-24 bg-[#173978] text-white relative overflow-hidden">
    <div class="absolute inset-0 opacity-5 pointer-events-none bb-dot-grid-white-subtle"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-3xl md:text-5xl font-extrabold mb-6">
                <?php echo page_te('deliver_title'); ?> <span class="text-[#2fcaf0]"><?php echo page_te('deliver_highlight'); ?></span>
            </h2>
            <p class="text-blue-100 text-lg leading-relaxed max-w-3xl mx-auto">
                <?php echo page_te('deliver_desc'); ?>
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" data-aos="fade-up" data-aos-delay="100">
            
            <div class="flex flex-col items-center text-center bg-white/5 p-8 rounded-2xl border border-white/10 hover:bg-white/10 transition-all duration-300 hover:-translate-y-2 group h-full">
                <div class="w-16 h-16 rounded-full bg-[#2fcaf0] flex items-center justify-center text-[#173978] text-2xl font-bold mb-6 shadow-lg shadow-[#2fcaf0]/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="fa-solid fa-users"></i>
                </div>
                <h3 class="text-lg font-bold leading-tight group-hover:text-[#2fcaf0] transition-colors"><?php echo page_te('d1'); ?></h3>
            </div>

            <div class="flex flex-col items-center text-center bg-white/5 p-8 rounded-2xl border border-white/10 hover:bg-white/10 transition-all duration-300 hover:-translate-y-2 group h-full">
                <div class="w-16 h-16 rounded-full bg-[#2fcaf0] flex items-center justify-center text-[#173978] text-2xl font-bold mb-6 shadow-lg shadow-[#2fcaf0]/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <h3 class="text-lg font-bold leading-tight group-hover:text-[#2fcaf0] transition-colors"><?php echo page_te('d2'); ?></h3>
            </div>

            <div class="flex flex-col items-center text-center bg-white/5 p-8 rounded-2xl border border-white/10 hover:bg-white/10 transition-all duration-300 hover:-translate-y-2 group h-full">
                <div class="w-16 h-16 rounded-full bg-[#2fcaf0] flex items-center justify-center text-[#173978] text-2xl font-bold mb-6 shadow-lg shadow-[#2fcaf0]/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <h3 class="text-lg font-bold leading-tight group-hover:text-[#2fcaf0] transition-colors"><?php echo page_te('d3'); ?></h3>
            </div>

            <div class="flex flex-col items-center text-center bg-white/5 p-8 rounded-2xl border border-white/10 hover:bg-white/10 transition-all duration-300 hover:-translate-y-2 group h-full">
                <div class="w-16 h-16 rounded-full bg-[#2fcaf0] flex items-center justify-center text-[#173978] text-2xl font-bold mb-6 shadow-lg shadow-[#2fcaf0]/20 group-hover:scale-110 transition-transform duration-300">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <h3 class="text-lg font-bold leading-tight group-hover:text-[#2fcaf0] transition-colors"><?php echo page_te('d4'); ?></h3>
            </div>

        </div>

    </div>
</section>

<section class="py-20 bg-gray-50 relative" id="survey-enquiry">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-stretch">
            
            <div class="flex flex-col justify-between h-full" data-aos="fade-right">
                
                <div>
                    <h2 class="text-3xl font-extrabold text-[#173978] mb-10"><?php echo page_te('why_bbpl'); ?></h2>
                    <div class="space-y-8">
                        
                        <div class="flex items-start gap-5">
                            <div class="w-14 h-14 bg-white rounded-xl shadow-sm flex items-center justify-center text-[#cb595c] font-bold text-2xl shrink-0 border border-gray-100">
                                <i class="fa-solid fa-briefcase"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#173978] text-lg"><?php echo page_te('why_pan_industry'); ?></h4>
                                <p class="text-gray-600 text-sm mt-1 leading-relaxed"><?php echo page_te('why_pan_industry_d'); ?></p>
                            </div>
                        </div>

                        <div class="flex items-start gap-5">
                            <div class="w-14 h-14 bg-white rounded-xl shadow-sm flex items-center justify-center text-[#cb595c] font-bold text-2xl shrink-0 border border-gray-100">
                                <i class="fa-solid fa-rocket"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#173978] text-lg"><?php echo page_te('why_rapid'); ?></h4>
                                <p class="text-gray-600 text-sm mt-1 leading-relaxed"><?php echo page_te('why_rapid_d'); ?></p>
                            </div>
                        </div>

                        <div class="flex items-start gap-5">
                            <div class="w-14 h-14 bg-white rounded-xl shadow-sm flex items-center justify-center text-[#cb595c] font-bold text-2xl shrink-0 border border-gray-100">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#173978] text-lg"><?php echo page_te('why_process'); ?></h4>
                                <p class="text-gray-600 text-sm mt-1 leading-relaxed"><?php echo page_te('why_process_d'); ?></p>
                            </div>
                        </div>

                        <div class="flex items-start gap-5">
                            <div class="w-14 h-14 bg-white rounded-xl shadow-sm flex items-center justify-center text-[#cb595c] font-bold text-2xl shrink-0 border border-gray-100">
                                <i class="fa-solid fa-eye"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#173978] text-lg"><?php echo page_te('why_accountability'); ?></h4>
                                <p class="text-gray-600 text-sm mt-1 leading-relaxed"><?php echo page_te('why_accountability_d'); ?></p>
                            </div>
                        </div>

                    </div>
                    <br><br>
                    <div class="pt-12 lg:pt-0">
                         <h3 class="text-xl md:text-2xl font-bold text-[#173978] leading-normal text-justify px-2">
                            <?php echo page_te('why_closing'); ?>
                        </h3>
                    </div>
                </div>
            </div>

            <div data-aos="fade-left" class="h-full">
                <div class="bg-white p-8 md:p-10 rounded-3xl shadow-2xl border-t-8 border-[#2fcaf0] h-full flex flex-col justify-center">
                    
                    <h3 class="text-3xl font-extrabold text-[#173978] mb-3"><?php echo page_te('form_build_title'); ?></h3>
                    <p class="text-gray-500 text-base mb-8"><?php echo page_te('form_intro'); ?></p>

                    <form action="" method="POST" class="space-y-5" data-enquiry-form="1">
                        
                        <?php enquiry_form_hidden_fields(); ?>
                        <input type="text" name="website_check" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 tracking-wider"><?php echo page_te('form_name'); ?></label>
                            <input type="text" name="name" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-4 focus:outline-none focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] transition-colors font-medium text-gray-800" placeholder="<?php echo page_te('form_ph_name'); ?>" required>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 tracking-wider"><?php echo page_te('form_company'); ?></label>
                                <input type="text" name="company" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-4 focus:outline-none focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] transition-colors font-medium text-gray-800" placeholder="<?php echo page_te('form_ph_company'); ?>" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 tracking-wider"><?php echo page_te('form_phone'); ?></label>
                                <input type="tel" name="phone" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-4 focus:outline-none focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] transition-colors font-medium text-gray-800" placeholder="<?php echo page_te('form_ph_phone'); ?>" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 tracking-wider"><?php echo page_te('form_email'); ?></label>
                            <input type="email" name="email" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-4 focus:outline-none focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] transition-colors font-medium text-gray-800" placeholder="<?php echo page_te('form_ph_email'); ?>" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 tracking-wider"><?php echo page_te('form_requirement'); ?></label>
                            <textarea name="message" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-4 focus:outline-none focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] transition-colors font-medium text-gray-800 resize-none" placeholder="<?php echo page_te('form_ph_message'); ?>" required></textarea>
                        </div>

                        <button type="submit" class="w-full bg-[#173978] text-white font-bold text-lg py-4 rounded-xl hover:bg-[#2fcaf0] hover:text-[#173978] transition-all shadow-lg hover:shadow-xl hover:-translate-y-1">
                            <?php echo page_te('form_submit'); ?>
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
    function scrollToSurvey() {
        const section = document.getElementById('survey-enquiry');
        if (section) {
            section.scrollIntoView({ behavior: 'smooth' });
        }
    }
</script>

<?php enquiry_render_form_validation_script(); ?>
<?php include 'includes/footer.php'; ?>