<?php
// 1. PREVENT HEADER ERRORS
session_start();
require 'db.php';
require_once 'includes/enquiry-form-handler.php';
enquiry_process_general_form($pdo, 'Contact Us Page');

// --- PAGE CONTENT START ---
$pageTitle = "Contact Us | Bisani Brothers";
$pageDesc = "Contact Bisani Brothers in Lucknow for sales execution, staffing solutions, market research, lending collection, and business growth enquiries across India.";
include 'includes/header.php';
?>

<?php enquiry_render_status_alerts(); ?>

<section class="relative w-full py-24 md:py-40 flex items-center justify-center overflow-hidden">
    
    <div class="absolute inset-0 z-0">
        <img src="assets/bg/Contact_Us.webp" 
             alt="Contact Us" 
             class="w-full h-full object-cover object-center">
        <div class="absolute inset-0 bg-black/40"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full flex items-center" data-aos="fade-up">
        
        <div class="max-w-3xl bg-[#173978]/50 backdrop-blur-md p-10 md:p-16 rounded-[2rem] shadow-3xl border border-white/10 my-auto">

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight mb-8 leading-tight text-white">
                <?php echo page_te('hero_h1_a'); ?> <br>
                <span class="text-[#2fcaf0]"><?php echo page_te('hero_h1_b'); ?></span>
            </h1>
            
            <p class="text-lg md:text-2xl text-blue-50 font-light mb-10 leading-relaxed">
                <?php echo page_te('hero_desc'); ?>
            </p>

            <button onclick="scrollToContactForm()" class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-base font-bold rounded text-[#052c65] bg-[#2fcaf0] hover:bg-white md:text-lg transition-all duration-300 shadow-[0_0_20px_rgba(47,202,240,0.3)] hover:shadow-[0_0_30px_rgba(47,202,240,0.6)] cursor-pointer group">
                <?php echo page_te('hero_btn'); ?>
                <i class="fa-solid fa-arrow-right ml-3 transition-transform group-hover:translate-x-1"></i>
            </button>

        </div>

    </div>
</section>

<section class="py-24 bg-white" id="contact-form-section">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col lg:flex-row border border-gray-100">
            
            <div class="w-full lg:w-5/12 bg-[#173978] p-10 lg:p-12 text-white relative flex flex-col justify-between overflow-hidden">
                <div class="absolute top-0 right-0 -mr-10 -mt-10 w-40 h-40 bg-[#2fcaf0] rounded-full opacity-20 blur-2xl"></div>
                <div class="absolute bottom-0 left-0 -ml-10 -mb-10 w-40 h-40 bg-[#cb595c] rounded-full opacity-20 blur-2xl"></div>

                <div class="relative z-10">
                    <h3 class="text-2xl font-bold mb-6"><?php echo page_te('info_title'); ?></h3>
                    <p class="text-blue-200 mb-8 leading-relaxed">
                        <?php echo page_te('info_desc'); ?>
                    </p>

                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <i class="fa-solid fa-phone mt-1 text-[#2fcaf0]"></i>
                            <div>
                                <span class="block text-xs font-bold uppercase text-blue-300"><?php echo page_te('phone'); ?></span>
                                <span class="font-medium"><a href="tel:+91 52 2453 0208">+91 52 2453 0208</a></span>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <i class="fa-solid fa-envelope mt-1 text-[#2fcaf0]"></i>
                            <div>
                                <span class="block text-xs font-bold uppercase text-blue-300"><?php echo page_te('email'); ?></span>
                                <span class="font-medium"><a href="mailto:contact@bisanibrother.com">contact@bisanibrother.com</a></span>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <i class="fa-solid fa-location-dot mt-1 text-[#2fcaf0]"></i>
                            <div>
                                <span class="block text-xs font-bold uppercase text-blue-300"><?php echo page_te('hq'); ?></span>
                                <span class="font-medium">Mumbai, Gurugram and Lucknow</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 mt-10">
                    <div class="flex gap-4">
                        <a href="https://www.linkedin.com/company/bisani-brothers"  target="_blank" class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center hover:bg-[#2fcaf0] hover:border-[#2fcaf0] hover:text-[#173978] transition-all">
                            <i class="fa-brands fa-linkedin-in"></i>
                        </a>
                        <a href="https://www.facebook.com/profile.php?id=61582749106777"  target="_blank" class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center hover:bg-[#2fcaf0] hover:border-[#2fcaf0] hover:text-[#173978] transition-all">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a href="https://www.instagram.com/bisanibrothers/"  target="_blank" class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center hover:bg-[#2fcaf0] hover:border-[#2fcaf0] hover:text-[#173978] transition-all">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                         <a href="<?php echo seo_escape(SEO_YOUTUBE_CHANNEL); ?>" target="_blank" rel="noopener noreferrer" aria-label="YouTube" class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center hover:bg-[#2fcaf0] hover:border-[#2fcaf0] hover:text-[#173978] transition-all">
                            <i class="fa-brands fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-7/12 p-10 lg:p-14 bg-white">
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-[#173978] mb-2"><?php echo page_te('form_title'); ?></h2>
                    <p class="text-gray-500"><?php echo page_te('form_subtitle'); ?></p>
                </div>

                <form action="" method="POST" class="space-y-6" data-enquiry-form="1">
                    
                    <?php enquiry_form_hidden_fields(); ?>
                    <input type="text" name="website_check" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2"><?php echo page_te('form_name'); ?></label>
                            <input type="text" name="name" class="w-full border border-gray-200 rounded-lg p-3.5 bg-gray-50 focus:bg-white focus:outline-none focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] transition-colors" placeholder="<?php echo page_te('form_ph_name'); ?>" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2"><?php echo page_te('form_phone'); ?></label>
                            <input type="tel" name="phone" class="w-full border border-gray-200 rounded-lg p-3.5 bg-gray-50 focus:bg-white focus:outline-none focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] transition-colors" placeholder="<?php echo page_te('form_ph_phone'); ?>" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2"><?php echo page_te('form_email'); ?></label>
                            <input type="email" name="email" class="w-full border border-gray-200 rounded-lg p-3.5 bg-gray-50 focus:bg-white focus:outline-none focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] transition-colors" placeholder="<?php echo page_te('form_ph_email'); ?>" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2"><?php echo page_te('form_requirement'); ?></label>
                            <input type="text" name="subject" class="w-full border border-gray-200 rounded-lg p-3.5 bg-gray-50 focus:bg-white focus:outline-none focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] transition-colors" placeholder="<?php echo page_te('form_requirement'); ?>" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2"><?php echo htmlspecialchars(page_t('form_message', 'Message'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <textarea name="message" rows="4" class="w-full border border-gray-200 rounded-lg p-3.5 bg-gray-50 focus:bg-white focus:outline-none focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] transition-colors" placeholder="<?php echo page_te('form_ph_message'); ?>" required></textarea>
                    </div>

                    <button type="submit" class="w-full bg-[#173978] text-white font-bold py-4 rounded-lg hover:bg-[#2fcaf0] hover:text-[#173978] transition-all shadow-lg hover:shadow-xl flex justify-center items-center gap-2 group">
                        <?php echo page_te('form_submit'); ?>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </form>
            </div>

        </div>
    </div>
</section>

<script>
    function scrollToContactForm() {
        const section = document.getElementById('contact-form-section');
        if (section) {
            section.scrollIntoView({ behavior: 'smooth' });
        }
    }
</script>

<section class="py-20 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-extrabold text-[#173978]">Frequently Asked Questions</h2>
            <p class="text-gray-500 mt-2"><a href="faqs" class="text-[#2fcaf0] font-semibold hover:underline">View all FAQs →</a></p>
        </div>

        <div class="space-y-4">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-[#2fcaf0] transition-colors cursor-pointer">
                <h3 class="font-bold text-gray-900 text-lg mb-2">Do you provide staffing for all industries?</h3>
                <p class="text-gray-600">Yes, we specialize in FinTech, Sales, BFSI, Retail, and Operational staffing across various sectors.</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-[#2fcaf0] transition-colors cursor-pointer">
                <h3 class="font-bold text-gray-900 text-lg mb-2">How can I apply for a job?</h3>
                <p class="text-gray-600">You can visit our <a href="<?php echo htmlspecialchars(locale_url('careers')); ?>" class="text-[#2fcaf0] hover:underline">Careers page</a> or register as a <a href="<?php echo htmlspecialchars(locale_url('partner-with-us')); ?>" class="text-[#2fcaf0] hover:underline">Partner with Us,</a> if you are looking for flexible work.</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-[#2fcaf0] transition-colors cursor-pointer">
                <h3 class="font-bold text-gray-900 text-lg mb-2">Where are your offices located?</h3>
                <p class="text-gray-600">We have offices in Mumbai, Delhi, and Lucknow, with operational teams across multiple states in India.</p>
            </div>
        </div>
    </div>
</section>

<?php enquiry_render_form_validation_script(); ?>

<?php include 'includes/footer.php'; ?>