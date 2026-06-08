<?php
session_start();
require 'db.php';
require_once 'includes/enquiry-form-handler.php';
enquiry_process_general_form($pdo, 'Sales & Growth Page');

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

<div class="w-full overflow-hidden">

    <section class="relative h-screen sm:h-[80vh] min-h-[500px] md:min-h-[600px] flex items-center overflow-hidden w-full">
        
        <div class="absolute inset-0 z-0">
            <img src="assets/bg/sales_and_growth_bg.webp"
                 alt="Field sales team engaging with merchants" 
                 class="w-full h-full object-cover object-center">
            
            <div class="absolute inset-0 bg-black/40 sm:bg-black/10"></div>
        </div>

        <div class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center mt-10 sm:mt-0" data-aos="fade-up">
            
            <div class="w-full max-w-full sm:max-w-2xl md:max-w-4xl bg-[#173978]/80 sm:bg-[#173978]/50 backdrop-blur-md sm:backdrop-blur-sm p-6 sm:p-10 md:p-16 rounded-2xl sm:rounded-[2rem] shadow-2xl border border-white/10 text-center sm:text-left">
                
                <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight mb-4 sm:mb-6 leading-tight text-white break-words">
                    <?php echo page_te('hero_h1_a'); ?> <br class="hidden sm:block">
                    <span class="text-[#2fcaf0]"><?php echo page_te('hero_h1_b'); ?></span>
                </h1>
                
                <p class="text-base sm:text-lg md:text-2xl text-blue-50 font-light mb-8 sm:mb-10 leading-relaxed px-2 sm:px-0">
                    <?php echo page_te('hero_desc'); ?>
                </p>

                <button onclick="scrollToEnquiry()" class="inline-flex items-center justify-center px-6 sm:px-8 py-3 sm:py-3.5 border border-transparent text-sm sm:text-base font-bold rounded text-[#052c65] bg-[#2fcaf0] hover:bg-white md:text-lg transition-all duration-300 shadow-[0_0_20px_rgba(47,202,240,0.3)] hover:shadow-[0_0_30px_rgba(47,202,240,0.6)] cursor-pointer group w-max">
                    <?php echo page_te('know_more'); ?>
                    <i class="fa-solid fa-arrow-right ml-3 transition-transform group-hover:translate-x-1"></i>
                </button>

            </div>

        </div>
    </section>

    <section class="py-16 sm:py-20 bg-white w-full">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center" data-aos="fade-up">
            
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-[#173978] mb-4 sm:mb-6 px-2">
                <?php echo page_te('intro_title'); ?>
            </h2>
            
            <p class="text-base sm:text-lg text-gray-600 leading-relaxed sm:leading-loose text-center px-2 sm:px-0">
                Our <span class="font-extrabold text-[#173978] relative after:absolute after:bottom-0 after:left-0 after:w-full after:h-[3px] after:bg-[#2fcaf0]/30 after:-z-10"><?php echo page_te('intro_highlight'); ?></span> <?php echo htmlspecialchars($_introBody, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>
    </section>

    <section class="py-16 sm:py-24 bg-[#f4f7fc] relative w-full overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 sm:w-96 sm:h-96 bg-[#2fcaf0]/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            
            <div class="text-center mb-12 sm:mb-16" data-aos="fade-up">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-[#173978] px-2 sm:px-0"><?php echo page_te('challenges_title'); ?></h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">
                
                <div class="bg-white p-6 sm:p-8 md:p-10 rounded-2xl sm:rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 group" data-aos="fade-up" data-aos-delay="100">
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-5 text-center sm:text-left">
                        <div class="w-14 h-14 rounded-full bg-[#fff5f5] flex-shrink-0 flex items-center justify-center text-[#cb595c] group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-store-slash text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg sm:text-xl font-bold text-[#173978] mb-2 sm:mb-3"><?php echo page_te('c1_title'); ?></h3>
                            <p class="text-gray-600 leading-relaxed text-sm sm:text-base">
                                <?php echo page_te('c1_desc'); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 sm:p-8 md:p-10 rounded-2xl sm:rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 group" data-aos="fade-up" data-aos-delay="200">
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-5 text-center sm:text-left">
                        <div class="w-14 h-14 rounded-full bg-[#fff5f5] flex-shrink-0 flex items-center justify-center text-[#cb595c] group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-eye-low-vision text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg sm:text-xl font-bold text-[#173978] mb-2 sm:mb-3"><?php echo page_te('c2_title'); ?></h3>
                            <p class="text-gray-600 leading-relaxed text-sm sm:text-base">
                                <?php echo page_te('c2_desc'); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 sm:p-8 md:p-10 rounded-2xl sm:rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 group" data-aos="fade-up" data-aos-delay="300">
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-5 text-center sm:text-left">
                        <div class="w-14 h-14 rounded-full bg-[#fff5f5] flex-shrink-0 flex items-center justify-center text-[#cb595c] group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-gears text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg sm:text-xl font-bold text-[#173978] mb-2 sm:mb-3"><?php echo page_te('c3_title'); ?></h3>
                            <p class="text-gray-600 leading-relaxed text-sm sm:text-base">
                                <?php echo page_te('c3_desc'); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 sm:p-8 md:p-10 rounded-2xl sm:rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 group" data-aos="fade-up" data-aos-delay="400">
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-5 text-center sm:text-left">
                        <div class="w-14 h-14 rounded-full bg-[#fff5f5] flex-shrink-0 flex items-center justify-center text-[#cb595c] group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-map-location text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg sm:text-xl font-bold text-[#173978] mb-2 sm:mb-3"><?php echo page_te('c4_title'); ?></h3>
                            <p class="text-gray-600 leading-relaxed text-sm sm:text-base">
                                <?php echo page_te('c4_desc'); ?>
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="py-16 sm:py-24 bg-[#173978] text-white relative w-full overflow-hidden">
        <div class="absolute inset-0 opacity-5 pointer-events-none bb-dot-grid-white-subtle"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            
            <div class="text-center mb-12 sm:mb-16" data-aos="fade-up">
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold mb-4 sm:mb-6">
                    <?php echo page_te('deliver_title'); ?> <span class="text-[#2fcaf0]"><?php echo page_te('deliver_highlight'); ?></span>
                </h2>
                <p class="text-blue-100 text-base sm:text-xl leading-relaxed max-w-3xl mx-auto px-2">
                    <?php echo page_te('deliver_desc'); ?>
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6" data-aos="fade-up" data-aos-delay="100">
                
                <div class="flex flex-col items-center text-center bg-white/5 p-6 sm:p-8 rounded-2xl border border-white/10 hover:bg-white/10 transition-all duration-300 hover:-translate-y-2 group h-full">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-[#2fcaf0] flex items-center justify-center text-[#173978] text-xl sm:text-2xl font-bold mb-4 sm:mb-6 shadow-lg shadow-[#2fcaf0]/20 group-hover:scale-110 transition-transform duration-300">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <h3 class="text-base sm:text-lg font-bold leading-tight group-hover:text-[#2fcaf0] transition-colors"><?php echo page_te('d1'); ?></h3>
                </div>

                <div class="flex flex-col items-center text-center bg-white/5 p-6 sm:p-8 rounded-2xl border border-white/10 hover:bg-white/10 transition-all duration-300 hover:-translate-y-2 group h-full">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-[#2fcaf0] flex items-center justify-center text-[#173978] text-xl sm:text-2xl font-bold mb-4 sm:mb-6 shadow-lg shadow-[#2fcaf0]/20 group-hover:scale-110 transition-transform duration-300">
                        <i class="fa-solid fa-filter-circle-dollar"></i>
                    </div>
                    <h3 class="text-base sm:text-lg font-bold leading-tight group-hover:text-[#2fcaf0] transition-colors"><?php echo page_te('d2'); ?></h3>
                </div>

                <div class="flex flex-col items-center text-center bg-white/5 p-6 sm:p-8 rounded-2xl border border-white/10 hover:bg-white/10 transition-all duration-300 hover:-translate-y-2 group h-full">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-[#2fcaf0] flex items-center justify-center text-[#173978] text-xl sm:text-2xl font-bold mb-4 sm:mb-6 shadow-lg shadow-[#2fcaf0]/20 group-hover:scale-110 transition-transform duration-300">
                        <i class="fa-solid fa-arrow-up-right-dots"></i>
                    </div>
                    <h3 class="text-base sm:text-lg font-bold leading-tight group-hover:text-[#2fcaf0] transition-colors"><?php echo page_te('d3'); ?></h3>
                </div>

                <div class="flex flex-col items-center text-center bg-white/5 p-6 sm:p-8 rounded-2xl border border-white/10 hover:bg-white/10 transition-all duration-300 hover:-translate-y-2 group h-full">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-[#2fcaf0] flex items-center justify-center text-[#173978] text-xl sm:text-2xl font-bold mb-4 sm:mb-6 shadow-lg shadow-[#2fcaf0]/20 group-hover:scale-110 transition-transform duration-300">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <h3 class="text-base sm:text-lg font-bold leading-tight group-hover:text-[#2fcaf0] transition-colors"><?php echo page_te('d4'); ?></h3>
                </div>
                
                <div class="flex flex-col items-center text-center bg-white/5 p-6 sm:p-8 rounded-2xl border border-white/10 hover:bg-white/10 transition-all duration-300 hover:-translate-y-2 group h-full md:col-span-3 lg:col-span-1">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-[#2fcaf0] flex items-center justify-center text-[#173978] text-xl sm:text-2xl font-bold mb-4 sm:mb-6 shadow-lg shadow-[#2fcaf0]/20 group-hover:scale-110 transition-transform duration-300">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                    <h3 class="text-base sm:text-lg font-bold leading-tight group-hover:text-[#2fcaf0] transition-colors"><?php echo page_te('d5'); ?></h3>
                </div>

            </div>

        </div>
    </section>

    <section class="py-16 sm:py-20 bg-gray-50 relative w-full" id="enquiry-section">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 md:gap-16 items-stretch">
                
                <div class="flex flex-col justify-between h-full order-2 lg:order-1" data-aos="fade-right">
                    
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-[#173978] mb-8 sm:mb-10 text-center lg:text-left"><?php echo page_te('why_bbpl'); ?></h2>
                        
                        <div class="space-y-6 sm:space-y-8">
                            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 text-center sm:text-left">
                                <div class="w-12 h-12 sm:w-14 sm:h-14 bg-white rounded-xl shadow-sm flex items-center justify-center text-[#cb595c] font-bold text-xl sm:text-2xl shrink-0 border border-gray-100">
                                    <i class="fa-solid fa-briefcase"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-[#173978] text-base sm:text-lg"><?php echo page_te('why_pan_industry'); ?></h4>
                                    <p class="text-gray-600 text-sm mt-1 leading-relaxed"><?php echo page_te('why_pan_industry_d'); ?></p>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 text-center sm:text-left">
                                <div class="w-12 h-12 sm:w-14 sm:h-14 bg-white rounded-xl shadow-sm flex items-center justify-center text-[#cb595c] font-bold text-xl sm:text-2xl shrink-0 border border-gray-100">
                                    <i class="fa-solid fa-rocket"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-[#173978] text-base sm:text-lg"><?php echo page_te('why_rapid'); ?></h4>
                                    <p class="text-gray-600 text-sm mt-1 leading-relaxed"><?php echo page_te('why_rapid_d'); ?></p>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 text-center sm:text-left">
                                <div class="w-12 h-12 sm:w-14 sm:h-14 bg-white rounded-xl shadow-sm flex items-center justify-center text-[#cb595c] font-bold text-xl sm:text-2xl shrink-0 border border-gray-100">
                                    <i class="fa-solid fa-list-check"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-[#173978] text-base sm:text-lg"><?php echo page_te('why_process'); ?></h4>
                                    <p class="text-gray-600 text-sm mt-1 leading-relaxed"><?php echo page_te('why_process_d'); ?></p>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 text-center sm:text-left">
                                <div class="w-12 h-12 sm:w-14 sm:h-14 bg-white rounded-xl shadow-sm flex items-center justify-center text-[#cb595c] font-bold text-xl sm:text-2xl shrink-0 border border-gray-100">
                                    <i class="fa-solid fa-eye"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-[#173978] text-base sm:text-lg"><?php echo page_te('why_accountability'); ?></h4>
                                    <p class="text-gray-600 text-sm mt-1 leading-relaxed"><?php echo page_te('why_accountability_d'); ?></p>
                                </div>
                            </div>
                        </div>
                    
                        <div class="pt-8 sm:pt-12 text-center lg:text-left">
                             <h3 class="text-lg sm:text-xl md:text-2xl font-bold text-[#173978] leading-normal px-2 sm:px-0">
                                <?php echo page_te('why_closing'); ?>
                            </h3>
                        </div>
                    </div>
                </div>

                <div data-aos="fade-left" class="h-full order-1 lg:order-2">
                    <div class="bg-white p-6 sm:p-8 md:p-10 rounded-2xl sm:rounded-3xl shadow-2xl border-t-8 border-[#2fcaf0] h-full flex flex-col justify-center">
                        <h3 class="text-2xl sm:text-3xl font-extrabold text-[#173978] mb-2 text-center sm:text-left"><?php echo page_te('form_build_title'); ?></h3>
                        <p class="text-gray-500 text-sm sm:text-base mb-6 sm:mb-8 text-center sm:text-left"><?php echo page_te('form_intro'); ?></p>

                        <form action="" method="POST" class="space-y-4 sm:space-y-5" data-enquiry-form="1">
                            
                            <?php enquiry_form_hidden_fields(); ?>
                            <input type="text" name="website_check" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1 sm:mb-2 tracking-wider"><?php echo page_te('form_name'); ?></label>
                                <input type="text" name="name" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 sm:p-4 text-sm sm:text-base focus:outline-none focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] transition-colors font-medium text-gray-800" placeholder="<?php echo page_te('form_ph_name'); ?>" required>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 sm:mb-2 tracking-wider"><?php echo page_te('form_company'); ?></label>
                                    <input type="text" name="company" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 sm:p-4 text-sm sm:text-base focus:outline-none focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] transition-colors font-medium text-gray-800" placeholder="<?php echo page_te('form_ph_company'); ?>" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 sm:mb-2 tracking-wider"><?php echo page_te('form_phone'); ?></label>
                                    <input type="tel" name="phone" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 sm:p-4 text-sm sm:text-base focus:outline-none focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] transition-colors font-medium text-gray-800" placeholder="<?php echo page_te('form_ph_phone'); ?>" required>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1 sm:mb-2 tracking-wider"><?php echo page_te('form_email'); ?></label>
                                <input type="email" name="email" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 sm:p-4 text-sm sm:text-base focus:outline-none focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] transition-colors font-medium text-gray-800" placeholder="<?php echo page_te('form_ph_email'); ?>" required>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1 sm:mb-2 tracking-wider"><?php echo page_te('form_requirement'); ?></label>
                                <textarea name="message" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 sm:p-4 text-sm sm:text-base focus:outline-none focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] transition-colors font-medium text-gray-800 resize-none" placeholder="<?php echo page_te('form_ph_message'); ?>" required></textarea>
                            </div>

                            <button type="submit" class="w-full bg-[#173978] text-white font-bold text-base sm:text-lg py-3.5 sm:py-4 rounded-xl hover:bg-[#2fcaf0] hover:text-[#173978] transition-all shadow-lg hover:shadow-xl hover:-translate-y-1 mt-2">
                                <?php echo page_te('form_submit'); ?>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>

</div> <script>
    function scrollToEnquiry() {
        const section = document.getElementById('enquiry-section');
        if (section) {
            section.scrollIntoView({ behavior: 'smooth' });
        }
    }
</script>

<?php enquiry_render_form_validation_script(); ?>
<?php include 'includes/footer.php'; ?>