<?php
// Ensure Database Connection exists
if (!isset($pdo)) {
    if (file_exists('includes/db.php')) { include 'includes/db.php'; }
    elseif (file_exists('db.php')) { include 'db.php'; }
}

$globalPopup = null;

if (isset($pdo)) {
    try {
        $popStmt = $pdo->query("SELECT * FROM popups WHERE is_active = 1 LIMIT 1");
        $globalPopup = $popStmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { /* Fail silently */ }
}
?>

<div id="floating-contact-widget" class="fixed bottom-6 right-6 z-[999999] flex flex-col items-end pointer-events-none">
    
    <div id="floating-menu" class="flex flex-col items-end gap-4 mb-4 transition-all duration-300 opacity-0 translate-y-10 pointer-events-none origin-bottom">
        
        <div class="group relative flex items-center justify-end pointer-events-auto">
            <span class="absolute right-14 bg-white text-[#173978] text-xs font-bold px-3 py-1.5 rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none border border-gray-100">
                <?php echo htmlspecialchars(t('footer.chat_whatsapp', 'Chat on WhatsApp')); ?>
            </span>
            <a href="https://wa.me/919793649177?text=Hello%20Bisani%20Brothers!%20%F0%9F%9A%80%20I%20was%20browsing%20your%20website%20and%20I'm%20interested%20in%20exploring%20how%20your%20solutions%20can%20help%20my%20business%20grow.%20Let's%20connect!" 
               target="_blank" 
               class="w-12 h-12 bg-[#25D366] text-white rounded-full flex items-center justify-center hover:scale-110 hover:shadow-xl transition-all duration-300 shadow-lg text-2xl">
                <i class="fa-brands fa-whatsapp"></i>
            </a>
        </div>

        <div class="group relative flex items-center justify-end pointer-events-auto">
            <span class="absolute right-14 bg-white text-[#173978] text-xs font-bold px-3 py-1.5 rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none border border-gray-100">
                <?php echo htmlspecialchars(t('footer.send_email', 'Send an Email')); ?>
            </span>
            <a href="mailto:contact@bisanibrother.com?subject=Business%20Inquiry%20-%20Website" 
               class="w-12 h-12 bg-[#EA4335] text-white rounded-full flex items-center justify-center hover:scale-110 hover:shadow-xl transition-all duration-300 shadow-lg text-xl">
                <i class="fa-solid fa-envelope"></i>
            </a>
        </div>

        <div class="group relative flex items-center justify-end pointer-events-auto">
            <span class="absolute right-14 bg-white text-[#173978] text-xs font-bold px-3 py-1.5 rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap pointer-events-none border border-gray-100">
                <?php echo htmlspecialchars(t('footer.call_us', 'Call Us Directly')); ?>
            </span>
            <a href="tel:+919793649177" 
               class="w-12 h-12 bg-[#173978] text-[#2fcaf0] border border-[#2fcaf0] rounded-full flex items-center justify-center hover:scale-110 hover:shadow-xl hover:bg-[#2fcaf0] hover:text-[#173978] transition-all duration-300 shadow-lg text-xl">
                <i class="fa-solid fa-phone"></i>
            </a>
        </div>

    </div>

    <button id="floating-main-btn" onclick="toggleContactMenu()" class="relative w-14 h-14 bg-[#2fcaf0] text-[#173978] rounded-full flex items-center justify-center shadow-lg hover:shadow-2xl hover:scale-105 transition-all duration-300 z-10 pointer-events-auto cursor-pointer">
        <span class="absolute inset-0 w-full h-full rounded-full bg-[#2fcaf0] opacity-50 animate-ping z-0" id="pulse-ring"></span>
        
        <i id="icon-chat" class="fa-solid fa-comments text-2xl z-10 transition-transform duration-300 transform scale-100 rotate-0"></i>
        <i id="icon-close" class="fa-solid fa-xmark text-2xl z-10 transition-transform duration-300 absolute transform scale-0 rotate-90"></i>
    </button>
</div>

<script>
    function toggleContactMenu() {
        const menu = document.getElementById('floating-menu');
        const iconChat = document.getElementById('icon-chat');
        const iconClose = document.getElementById('icon-close');
        const pulseRing = document.getElementById('pulse-ring');
        const mainBtn = document.getElementById('floating-main-btn');

        // Check if menu is currently hidden
        if (menu.classList.contains('opacity-0')) {
            // OPEN MENU
            menu.classList.remove('opacity-0', 'translate-y-10', 'pointer-events-none');
            menu.classList.add('opacity-100', 'translate-y-0');
            
            // Switch icons (Chat to X)
            iconChat.classList.replace('scale-100', 'scale-0');
            iconChat.classList.replace('rotate-0', '-rotate-90');
            iconClose.classList.replace('scale-0', 'scale-100');
            iconClose.classList.replace('rotate-90', 'rotate-0');
            
            // UI Changes on open
            pulseRing.classList.add('hidden'); // Stop pulsing when open
            mainBtn.classList.replace('bg-[#2fcaf0]', 'bg-[#173978]');
            mainBtn.classList.replace('text-[#173978]', 'text-white');

        } else {
            // CLOSE MENU
            menu.classList.add('opacity-0', 'translate-y-10', 'pointer-events-none');
            menu.classList.remove('opacity-100', 'translate-y-0');
            
            // Switch icons (X to Chat)
            iconClose.classList.replace('scale-100', 'scale-0');
            iconClose.classList.replace('rotate-0', 'rotate-90');
            iconChat.classList.replace('scale-0', 'scale-100');
            iconChat.classList.replace('-rotate-90', 'rotate-0');
            
            // UI Changes on close
            pulseRing.classList.remove('hidden'); // Resume pulsing
            mainBtn.classList.replace('bg-[#173978]', 'bg-[#2fcaf0]');
            mainBtn.classList.replace('text-white', 'text-[#173978]');
        }
    }

    // Optional: Close menu if clicking anywhere outside of it
    document.addEventListener('click', function(event) {
        const widget = document.getElementById('floating-contact-widget');
        const menu = document.getElementById('floating-menu');
        
        if (!widget.contains(event.target) && !menu.classList.contains('opacity-0')) {
            toggleContactMenu();
        }
    });
</script>

<?php if (!empty($globalPopup)): ?>
    <div id="global-popup-overlay" class="fixed inset-0 z-[10000] hidden flex items-center justify-center px-4 font-sans">
        
        <div id="popup-backdrop" class="absolute inset-0 bg-[#0f172a]/80 backdrop-blur-sm transition-opacity duration-500 opacity-0" onclick="closeGlobalPopup()"></div>

        <div id="popup-content" class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden transform scale-95 opacity-0 transition-all duration-500 flex flex-col md:flex-row">
            
            <button onclick="closeGlobalPopup()" class="absolute top-4 right-4 z-20 w-8 h-8 bg-gray-100 hover:bg-[#cb595c] hover:text-white rounded-full flex items-center justify-center transition-colors cursor-pointer text-gray-500">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <?php if (!empty($globalPopup['image_path'])): ?>
            <div class="w-full md:w-1/2 relative min-h-[250px] md:min-h-[400px]">
                <img src="<?php echo htmlspecialchars($globalPopup['image_path']); ?>" class="absolute inset-0 w-full h-full object-cover">
                
                <div class="absolute inset-0 bg-gradient-to-t from-[#173978]/90 via-transparent to-transparent md:bg-gradient-to-r md:from-transparent md:to-white/5"></div>
                
                <div class="absolute inset-0 bg-gradient-to-tr from-white/0 via-white/20 to-white/0 -skew-x-12 translate-x-[-150%] animate-shine"></div>
            </div>
            <?php endif; ?>

            <div class="w-full <?php echo !empty($globalPopup['image_path']) ? 'md:w-1/2' : 'w-full'; ?> p-8 md:p-10 flex flex-col justify-center relative bg-white">
                
                <div class="absolute top-6 right-6 opacity-5 pointer-events-none">
                    <i class="fa-solid fa-bullhorn text-6xl text-[#173978]"></i>
                </div>

                <div class="mb-1">
                    <span class="text-xs font-bold tracking-widest text-[#2fcaf0] uppercase"><?php echo htmlspecialchars(t('footer.announcement', 'Announcement')); ?></span>
                </div>

                <h3 class="text-2xl md:text-3xl font-extrabold text-[#173978] mb-4 leading-tight">
                    <?php echo htmlspecialchars($globalPopup['title']); ?>
                </h3>

                <div class="text-gray-600 mb-8 text-base leading-relaxed max-h-[200px] overflow-y-auto custom-scrollbar pr-2">
                    <?php echo security_sanitize_rich_html($globalPopup['content']); ?>
                </div>

                <?php if (!empty($globalPopup['btn_link'])): ?>
                <div class="mt-auto">
                    <a href="<?php echo htmlspecialchars($globalPopup['btn_link']); ?>" onclick="closeGlobalPopup()" class="block w-full text-center bg-[#173978] hover:bg-[#122c5e] text-white font-bold py-3.5 rounded-lg shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-1">
                        <?php echo !empty($globalPopup['btn_text']) ? htmlspecialchars($globalPopup['btn_text']) : htmlspecialchars(t('common.learn_more', 'Learn More')); ?>
                        <i class="fa-solid fa-arrow-right ml-2"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Variable to hold the auto-close timer
        let popupAutoCloseTimer;

        document.addEventListener("DOMContentLoaded", function() {
            // 1. Wait 1 second after page load, then show popup
            setTimeout(() => {
                showPremiumPopup();
            }, 1000); 
        });

        function showPremiumPopup() {
            const overlay = document.getElementById('global-popup-overlay');
            const backdrop = document.getElementById('popup-backdrop');
            const content = document.getElementById('popup-content');

            if(overlay && backdrop && content) {
                // Unhide
                overlay.classList.remove('hidden');
                
                // Animate In
                requestAnimationFrame(() => {
                    backdrop.classList.remove('opacity-0');
                    content.classList.remove('scale-95', 'opacity-0');
                    content.classList.add('scale-100', 'opacity-100');
                });

                // 2. START AUTO-CLOSE TIMER (5 Seconds)
                popupAutoCloseTimer = setTimeout(() => {
                    closeGlobalPopup();
                }, 5000); // 5000ms = 5 Seconds
            }
        }

        function closeGlobalPopup() {
            // Stop the timer if the user closes it manually before 5 seconds
            clearTimeout(popupAutoCloseTimer);

            const overlay = document.getElementById('global-popup-overlay');
            const backdrop = document.getElementById('popup-backdrop');
            const content = document.getElementById('popup-content');

            // Animate Out
            if(backdrop && content) {
                backdrop.classList.add('opacity-0');
                content.classList.remove('scale-100', 'opacity-100');
                content.classList.add('scale-95', 'opacity-0');
            }

            // Hide completely after animation
            setTimeout(() => {
                if(overlay) overlay.classList.add('hidden');
            }, 500);
        }
    </script>

<?php endif; ?>


<?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
<div id="success-toast" class="fixed top-24 left-1/2 transform -translate-x-1/2 z-[100] bg-white border-l-4 border-green-500 text-gray-800 px-6 py-4 rounded-lg shadow-2xl flex items-center gap-4 animate-bounce">
    <div class="text-green-500 text-2xl"><i class="fa-solid fa-circle-check"></i></div>
    <div>
        <h4 class="font-bold text-sm uppercase text-green-600"><?php echo htmlspecialchars(t('footer.toast_success', 'Success!')); ?></h4>
        <p class="text-sm"><?php echo htmlspecialchars(t('footer.toast_submitted', 'Your details have been submitted successfully.')); ?></p>
    </div>
    <button onclick="document.getElementById('success-toast').remove()" class="text-gray-400 hover:text-gray-600 ml-2">&times;</button>
</div>
<script>
    // Remove query param to prevent showing on refresh
    if (history.replaceState) {
        var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({path:newurl},'',newurl);
    }
    // Auto hide after 5 seconds
    setTimeout(() => { 
        const toast = document.getElementById('success-toast');
        if(toast) { toast.style.opacity = '0'; setTimeout(()=>toast.remove(), 500); }
    }, 5000);
</script>
<?php endif; ?>

<footer class="bg-[#173978] pt-16 pb-8 border-t border-white/20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-12 mb-12">
            
            <div class="md:col-span-5">
                
                <a href="<?php echo htmlspecialchars(locale_url('')); ?>" class="inline-block mb-4 group">
                    <h2 class="text-2xl font-bold text-white group-hover:text-[#2fcaf0] transition-colors">
                        Bisani Brothers Private Limited <br>
                        <span class="text-lg font-normal opacity-90"></span>
                    </h2>
                </a>

                <p class="mt-4 text-white leading-relaxed text-sm md:text-base opacity-90 text-justify">
                    <?php echo htmlspecialchars(t('footer.desc')); ?>
                </p>
                <div class="relative z-10 mt-10">
                    <div class="flex gap-4">
                        
                        <a href="https://www.linkedin.com/company/bisani-brothers" target="_blank" 
                           class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center text-white hover:bg-[#2fcaf0] hover:border-[#2fcaf0] hover:text-[#173978] transition-all">
                            <i class="fa-brands fa-linkedin-in"></i>
                        </a>

                        <a href="https://www.facebook.com/profile.php?id=61582749106777" target="_blank" 
                           class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center text-white hover:bg-[#2fcaf0] hover:border-[#2fcaf0] hover:text-[#173978] transition-all">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>

                        <a href="https://www.instagram.com/bisanibrothers/" target="_blank" 
                           class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center text-white hover:bg-[#2fcaf0] hover:border-[#2fcaf0] hover:text-[#173978] transition-all">
                            <i class="fa-brands fa-instagram"></i>
                        </a>

                        <a href="<?php echo seo_escape(SEO_YOUTUBE_CHANNEL); ?>" target="_blank" rel="noopener noreferrer"
                           class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center text-white hover:bg-[#2fcaf0] hover:border-[#2fcaf0] hover:text-[#173978] transition-all"
                           aria-label="YouTube">
                            <i class="fa-brands fa-youtube"></i>
                        </a>

                    </div>
                </div>
            </div>

            <div class="md:col-span-3">
                <h3 class="text-sm font-extrabold text-[#2fcaf0] tracking-wider uppercase mb-5 border-b-2 border-white/30 inline-block pb-1"><?php echo htmlspecialchars(t('footer.company')); ?></h3>
                <ul class="space-y-3">
                    <li>
                        <a href="<?php echo htmlspecialchars(locale_url('about')); ?>" class="text-white font-medium hover:text-[#2fcaf0] transition-colors flex items-center group">
                            <span class="w-0 group-hover:w-2 h-0.5 bg-[#2fcaf0] mr-0 group-hover:mr-2 transition-all duration-300"></span>
                            <?php echo htmlspecialchars(t('footer.about_us')); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo htmlspecialchars(locale_url('case-studies')); ?>" class="text-white font-medium hover:text-[#2fcaf0] transition-colors flex items-center group">
                            <span class="w-0 group-hover:w-2 h-0.5 bg-[#2fcaf0] mr-0 group-hover:mr-2 transition-all duration-300"></span>
                            <?php echo htmlspecialchars(t('nav.case_studies')); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo htmlspecialchars(locale_url('industries')); ?>" class="text-white font-medium hover:text-[#2fcaf0] transition-colors flex items-center group">
                            <span class="w-0 group-hover:w-2 h-0.5 bg-[#2fcaf0] mr-0 group-hover:mr-2 transition-all duration-300"></span>
                            <?php echo htmlspecialchars(t('nav.industries')); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo htmlspecialchars(locale_url('careers')); ?>" class="text-white font-medium hover:text-[#2fcaf0] transition-colors flex items-center group">
                            <span class="w-0 group-hover:w-2 h-0.5 bg-[#2fcaf0] mr-0 group-hover:mr-2 transition-all duration-300"></span>
                            <?php echo htmlspecialchars(t('nav.careers')); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo htmlspecialchars(locale_url('blog')); ?>" class="text-white font-medium hover:text-[#2fcaf0] transition-colors flex items-center group">
                            <span class="w-0 group-hover:w-2 h-0.5 bg-[#2fcaf0] mr-0 group-hover:mr-2 transition-all duration-300"></span>
                            <?php echo htmlspecialchars(t('nav.blog_mobile')); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo htmlspecialchars(locale_url('faqs')); ?>" class="text-white font-medium hover:text-[#2fcaf0] transition-colors flex items-center group">
                            <span class="w-0 group-hover:w-2 h-0.5 bg-[#2fcaf0] mr-0 group-hover:mr-2 transition-all duration-300"></span>
                            <?php echo htmlspecialchars(t('nav.faqs')); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo htmlspecialchars(locale_url('contact')); ?>" class="text-white font-medium hover:text-[#2fcaf0] transition-colors flex items-center group">
                            <span class="w-0 group-hover:w-2 h-0.5 bg-[#2fcaf0] mr-0 group-hover:mr-2 transition-all duration-300"></span>
                            <?php echo htmlspecialchars(t('nav.contact')); ?>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.skill-art.org/" target="_blank" class="text-white font-medium hover:text-[#2fcaf0] transition-colors flex items-center group">
                            <span class="w-0 group-hover:w-2 h-0.5 bg-[#2fcaf0] mr-0 group-hover:mr-2 transition-all duration-300"></span>
                            <?php echo htmlspecialchars(t('footer.csr')); ?>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="md:col-span-4">
                <h3 class="text-sm font-extrabold text-[#2fcaf0] tracking-wider uppercase mb-5 border-b-2 border-white/30 inline-block pb-1"><?php echo htmlspecialchars(t('footer.solutions')); ?></h3>
                <ul class="space-y-3">
                    
                    <li>
                        <a href="<?php echo htmlspecialchars(locale_url('sales-growth')); ?>" class="text-white font-medium hover:text-[#2fcaf0] transition-colors flex items-center group">
                            <span class="w-0 group-hover:w-2 h-0.5 bg-[#2fcaf0] mr-0 group-hover:mr-2 transition-all duration-300"></span>
                            <?php echo htmlspecialchars(t('footer.sales_growth_solution')); ?>
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo htmlspecialchars(locale_url('survey-market-research')); ?>" class="text-white font-medium hover:text-[#2fcaf0] transition-colors flex items-center group">
                            <span class="w-0 group-hover:w-2 h-0.5 bg-[#2fcaf0] mr-0 group-hover:mr-2 transition-all duration-300"></span>
                            <?php echo htmlspecialchars(t('services.survey')); ?>
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo htmlspecialchars(locale_url('staffing-solutions')); ?>" class="text-white font-medium hover:text-[#2fcaf0] transition-colors flex items-center group">
                            <span class="w-0 group-hover:w-2 h-0.5 bg-[#2fcaf0] mr-0 group-hover:mr-2 transition-all duration-300"></span>
                            <?php echo htmlspecialchars(t('services.staffing')); ?>
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo htmlspecialchars(locale_url('btl-atl')); ?>" class="text-white font-medium hover:text-[#2fcaf0] transition-colors flex items-center group">
                            <span class="w-0 group-hover:w-2 h-0.5 bg-[#2fcaf0] mr-0 group-hover:mr-2 transition-all duration-300"></span>
                            <?php echo htmlspecialchars(t('footer.btl_activations')); ?>
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo htmlspecialchars(locale_url('lending-collection')); ?>" class="text-white font-medium hover:text-[#2fcaf0] transition-colors flex items-center group">
                            <span class="w-0 group-hover:w-2 h-0.5 bg-[#2fcaf0] mr-0 group-hover:mr-2 transition-all duration-300"></span>
                            <?php echo htmlspecialchars(t('services.lending')); ?>
                        </a>
                    </li>

                </ul>
            </div>

        </div>

        <div class="border-t border-white/20 pt-10 pb-6">
            <div class="max-w-xl mx-auto text-center">
                <h3 class="text-lg font-bold text-white mb-2"><?php echo htmlspecialchars(t('footer.newsletter_title')); ?></h3>
                <p class="text-sm text-blue-100 mb-4 opacity-90"><?php echo htmlspecialchars(t('footer.newsletter_desc')); ?></p>
                <form id="newsletter-form" class="flex flex-col sm:flex-row gap-2 justify-center" novalidate>
                    <input type="email" id="newsletter-email" name="email" placeholder="<?php echo htmlspecialchars(t('footer.work_email')); ?>" class="flex-1 max-w-sm mx-auto sm:mx-0 rounded-lg px-4 py-2.5 text-[#173978] text-sm focus:outline-none focus:ring-2 focus:ring-[#2fcaf0]" required>
                    <input type="text" name="website_check" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                    <button type="submit" class="px-6 py-2.5 bg-[#2fcaf0] text-[#173978] font-bold rounded-lg hover:bg-white transition-colors text-sm whitespace-nowrap"><?php echo htmlspecialchars(t('footer.subscribe')); ?></button>
                </form>
                <p id="newsletter-msg" class="text-xs mt-3 text-blue-100 hidden"></p>
            </div>
        </div>

        <div class="border-t border-white/30 pt-8 flex flex-col md:flex-row justify-between items-center">
            
            <p class="text-white text-sm mb-4 md:mb-0 font-medium opacity-90">
                &copy; <?php echo date("Y"); ?> Bisani Brothers. <?php echo htmlspecialchars(t('footer.copyright')); ?>
            </p>

            <div class="flex items-center space-x-6">
                <a href="<?php echo htmlspecialchars(locale_url('privacy')); ?>" class="text-sm text-white hover:text-[#2fcaf0] transition-colors font-semibold"><?php echo htmlspecialchars(t('footer.privacy')); ?></a>
                <span class="text-[#2fcaf0] opacity-50">|</span>
                <a href="<?php echo htmlspecialchars(locale_url('terms')); ?>" class="text-sm text-white hover:text-[#2fcaf0] transition-colors font-semibold"><?php echo htmlspecialchars(t('footer.terms_service')); ?></a>
            </div>

        </div>

    </div>
</footer>

<script defer src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script defer>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof AOS !== 'undefined') {
            AOS.init({ duration: 800, once: true, offset: 100 });
        }
    });

    // Mobile Menu Toggle Logic
    function toggleMenu() {
        const menu = document.getElementById('mobile-menu');
        menu.classList.toggle('hidden');
    }

    (function () {
        var form = document.getElementById('newsletter-form');
        if (!form) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var msg = document.getElementById('newsletter-msg');
            var fd = new FormData(form);
            fetch('newsletter-subscribe.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    msg.classList.remove('hidden');
                    msg.textContent = data.message || '';
                    msg.className = 'text-xs mt-3 ' + (data.ok ? 'text-green-300' : 'text-red-300');
                    if (data.ok) form.reset();
                })
                .catch(function () {
                    msg.classList.remove('hidden');
                    msg.textContent = '<?php echo addslashes(t('footer.newsletter_error', 'Something went wrong. Please try again.')); ?>';
                    msg.className = 'text-xs mt-3 text-red-300';
                });
        });
    })();

    (function () {
        var prefetched = new Set();
        document.querySelectorAll('.locale-switch-link[href]').forEach(function (link) {
            link.addEventListener('mouseenter', function () {
                var href = link.getAttribute('href');
                if (!href || prefetched.has(href)) return;
                prefetched.add(href);
                var hint = document.createElement('link');
                hint.rel = 'prefetch';
                hint.href = href;
                document.head.appendChild(hint);
            }, { passive: true });
            link.addEventListener('click', function () {
                document.documentElement.classList.add('locale-switching');
            }, { passive: true });
        });
    })();
</script>
<style>
html.locale-switching { cursor: progress; }
html.locale-switching body { opacity: 0.92; transition: opacity 0.15s ease; }
</style>

<?php
require_once __DIR__ . '/meta-pixel.php';
$footerScript = $scriptName ?? basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
if (meta_pixel_active() && in_array($footerScript, ['blog', 'blog-details'], true)):
    $pixelScript = meta_pixel_script_url($base_url ?? '');
?>
<script>
(function () {
    function loadPixel() {
        var s = document.createElement('script');
        s.async = true;
        s.src = <?php echo json_encode($pixelScript, JSON_UNESCAPED_SLASHES); ?>;
        document.head.appendChild(s);
    }
    if ('requestIdleCallback' in window) {
        requestIdleCallback(loadPixel, { timeout: 3000 });
    } else {
        window.addEventListener('load', function () { setTimeout(loadPixel, 100); }, { once: true });
    }
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/blog-warm-client.php'; ?>

</body>
</html>