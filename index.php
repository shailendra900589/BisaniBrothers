<?php
// 1. PREVENT HEADER ERRORS
session_start();
require 'db.php';
require_once 'includes/testimonials-helpers.php';
require_once 'includes/enquiry-form-handler.php';
enquiry_process_general_form($pdo, 'Home Page');

// --- PAGE CONTENT START ---
include 'includes/header.php';
?>

<?php enquiry_render_status_alerts(); ?>

<div class="w-full overflow-hidden max-w-[100vw]">

    <section class="relative bg-[#052c65] min-h-[650px] flex items-center pt-10 pb-10 w-full">
        
        <div class="absolute inset-0 z-0">
            <img src="assets/images/bg_image.webp" 
                 alt="Hero Background" 
                 class="w-full h-full object-cover"
                 fetchpriority="high"
                 decoding="async">
        </div>

        <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 rounded-full bg-[#2fcaf0] opacity-10 blur-[100px] animate-pulse z-0"></div>
        <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-full bg-blue-900 opacity-20 blur-[80px] z-0"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                
                <div data-aos="fade-right" data-aos-duration="1000">
                    <div class="bg-[#052c65]/60 sm:bg-[#052c65]/40 backdrop-blur-md border border-white/10 p-6 sm:p-8 md:p-12 rounded-2xl sm:rounded-3xl shadow-2xl relative group hover:border-white/20 transition-all duration-300 mx-2 sm:mx-0">
                        
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                        
                        <h1 class="font-bold tracking-tight leading-tight relative z-10">
                            <span class="text-[#2fcaf0] drop-shadow-sm block text-3xl sm:text-4xl lg:text-5xl mb-2">
                             <?php echo htmlspecialchars(t('index.hero_line1')); ?>
                            </span> 
                            
                            <span class="text-white block text-2xl sm:text-3xl lg:text-4xl">
                                <?php echo htmlspecialchars(t('index.hero_line2')); ?>
                            </span>
                        </h1>

                        <p class="mt-4 sm:mt-6 text-sm sm:text-base lg:text-lg text-blue-100 max-w-lg leading-relaxed font-medium opacity-90 relative z-10">
                            <?php echo htmlspecialchars(t('index.hero_desc')); ?>
                        </p>

                        <div class="mt-8 sm:mt-10 flex flex-col sm:flex-row gap-3 sm:gap-4 relative z-10">
                            <button onclick="scrollToContactUs()" class="inline-flex items-center justify-center px-6 sm:px-8 py-3.5 border border-transparent text-sm sm:text-base font-bold rounded text-[#052c65] bg-[#2fcaf0] hover:bg-white md:text-lg transition-all duration-300 shadow-[0_0_20px_rgba(47,202,240,0.3)] hover:shadow-[0_0_30px_rgba(47,202,240,0.6)] cursor-pointer">
                                <?php echo htmlspecialchars(t('index.cta_growth')); ?>
                                <svg class="ml-2 -mr-1 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </button>

                            <button onclick="scrollToServices()" class="inline-flex items-center justify-center px-6 sm:px-8 py-3.5 border border-white/20 text-sm sm:text-base font-bold rounded text-white hover:bg-white/10 md:text-lg transition-all duration-300 cursor-pointer">
                                <?php echo htmlspecialchars(t('index.cta_services')); ?>
                            </button>
                        </div>
                    </div> 
                </div>

                <div class="relative flex justify-center lg:justify-end" data-aos="fade-left" data-aos-duration="1200">
                </div>

            </div>
        </div>  
    </section>

    <section id="impact-stats" class="bg-white py-12 sm:py-16 relative z-10 border-t border-gray-100 w-full">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-y-2 divide-x-0 lg:divide-y-0 lg:divide-x-2 divide-gray-100">
                
                <div class="flex flex-col items-center justify-center p-4 sm:p-6 text-center">
                    <h3 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-[#173978] mb-2">
                        <span class="count-up" data-target="10">0</span>+
                    </h3>
                    <p class="text-xs sm:text-sm md:text-base font-bold text-gray-600 uppercase tracking-wider">
                        <?php echo htmlspecialchars(t('index.stat_years')); ?>
                    </p>
                </div>

                <div class="flex flex-col items-center justify-center p-4 sm:p-6 text-center">
                    <h3 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-[#173978] mb-2">
                        <span class="count-up" data-target="150">0</span>+
                    </h3>
                    <p class="text-xs sm:text-sm md:text-base font-bold text-gray-600 uppercase tracking-wider">
                        <?php echo htmlspecialchars(t('index.stat_clients')); ?>
                    </p>
                </div>

                <div class="flex flex-col items-center justify-center p-4 sm:p-6 text-center">
                    <h3 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-[#173978] mb-2">
                        <span class="count-up" data-target="400000">0</span>+
                    </h3>
                    <p class="text-xs sm:text-sm md:text-base font-bold text-gray-600 uppercase tracking-wider">
                        <?php echo htmlspecialchars(t('index.stat_outlets')); ?>
                    </p>
                </div>

                <div class="flex flex-col items-center justify-center p-4 sm:p-6 text-center">
                    <h3 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-[#173978] mb-2">
                        <span class="count-up" data-target="2100">0</span>+
                    </h3>
                    <p class="text-xs sm:text-sm md:text-base font-bold text-gray-600 uppercase tracking-wider">
                        <?php echo htmlspecialchars(t('index.stat_team')); ?>
                    </p>
                </div>

            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const counters = document.querySelectorAll('.count-up');
                const statsSection = document.getElementById('impact-stats');
                let hasAnimated = false; 

                const runCounter = () => {
                    counters.forEach(counter => {
                        const updateCount = () => {
                            const target = +counter.getAttribute('data-target');
                            const count = +counter.innerText;
                            const speed = 100; 
                            const inc = target / speed;

                            if (count < target) {
                                counter.innerText = Math.ceil(count + inc);
                                setTimeout(updateCount, 20); 
                            } else {
                                counter.innerText = target;
                            }
                        };
                        updateCount();
                    });
                };

                const observerOptions = {
                    root: null,
                    rootMargin: '0px',
                    threshold: 0.5 
                };

                const observer = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && !hasAnimated) {
                            hasAnimated = true; 
                            runCounter();
                            observer.unobserve(entry.target);
                        }
                    });
                }, observerOptions);

                if (statsSection) {
                    observer.observe(statsSection);
                }
            });
        </script>
    </section>

    <section class="py-16 sm:py-24 bg-[#173978] relative w-full" id="services">
        
        <div class="absolute inset-0 opacity-5 pointer-events-none bb-dot-grid-white-fine"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            
            <div class="text-center mb-12 sm:mb-16" data-aos="fade-up">
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-white mb-4 sm:mb-6">
                    <?php echo htmlspecialchars(t('index.services_title', 'Our Services')); ?>
                </h2>
                <p class="text-blue-100 text-base sm:text-lg max-w-2xl mx-auto leading-relaxed font-light px-2">
                    <?php echo htmlspecialchars(t('index.services_desc')); ?>
                </p>
            </div>

            <div class="flex flex-wrap justify-center gap-6 sm:gap-8">

                <div class="w-full sm:w-[calc(50%-1rem)] lg:w-[calc(33.333%-1.5rem)] flex" data-aos="fade-up" data-aos-delay="100">
                    <div class="bg-white rounded-[2rem] p-8 sm:p-10 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 group w-full flex flex-col items-center text-center relative">
                        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-transparent via-[#2fcaf0] to-transparent scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>
                        
                        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-white border border-gray-100 rounded-full flex items-center justify-center mb-6 sm:mb-8 shadow-md group-hover:scale-110 transition-transform duration-300">
                            <img src="assets/vectors/Sales.png" alt="Sales" class="w-10 h-10 sm:w-12 sm:h-12 object-contain">
                        </div>
                        
                        <h3 class="text-lg sm:text-xl font-bold text-[#173978] mb-3 sm:mb-4"><?php echo htmlspecialchars(t('services.sales')); ?></h3>
                        <p class="text-gray-600 mb-6 sm:mb-8 leading-relaxed text-sm flex-grow">
                            <?php echo htmlspecialchars(t('index.sales_desc')); ?>
                        </p>
                        
                        <a href="<?php echo htmlspecialchars(locale_url('sales-growth')); ?>" class="inline-flex items-center text-sm font-bold text-[#173978] group-hover:text-[#2fcaf0] transition-colors uppercase tracking-wide">
                            <?php echo htmlspecialchars(t('common.know_more', 'KNOW MORE')); ?> <i class="fa-solid fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>

                <div class="w-full sm:w-[calc(50%-1rem)] lg:w-[calc(33.333%-1.5rem)] flex" data-aos="fade-up" data-aos-delay="200">
                    <div class="bg-white rounded-[2rem] p-8 sm:p-10 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 group w-full flex flex-col items-center text-center relative">
                        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-transparent via-[#2fcaf0] to-transparent scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>
                        
                        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-white border border-gray-100 rounded-full flex items-center justify-center mb-6 sm:mb-8 shadow-md group-hover:scale-110 transition-transform duration-300">
                            <img src="assets/vectors/Survey.png" alt="Survey" class="w-10 h-10 sm:w-12 sm:h-12 object-contain">
                        </div>
                        
                        <h3 class="text-lg sm:text-xl font-bold text-[#173978] mb-3 sm:mb-4"><?php echo htmlspecialchars(t('services.survey')); ?></h3>
                        <p class="text-gray-600 mb-6 sm:mb-8 leading-relaxed text-sm flex-grow">
                            <?php echo htmlspecialchars(t('index.survey_desc')); ?>
                        </p>
                        
                        <a href="<?php echo htmlspecialchars(locale_url('survey-market-research')); ?>" class="inline-flex items-center text-sm font-bold text-[#173978] group-hover:text-[#2fcaf0] transition-colors uppercase tracking-wide">
                            <?php echo htmlspecialchars(t('common.know_more', 'KNOW MORE')); ?> <i class="fa-solid fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>

                <div class="w-full sm:w-[calc(50%-1rem)] lg:w-[calc(33.333%-1.5rem)] flex" data-aos="fade-up" data-aos-delay="300">
                    <div class="bg-white rounded-[2rem] p-8 sm:p-10 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 group w-full flex flex-col items-center text-center relative">
                        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-transparent via-[#2fcaf0] to-transparent scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>
                        
                        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-white border border-gray-100 rounded-full flex items-center justify-center mb-6 sm:mb-8 shadow-md group-hover:scale-110 transition-transform duration-300">
                            <img src="assets/vectors/Hiring.png" alt="Staffing" class="w-10 h-10 sm:w-12 sm:h-12 object-contain">
                        </div>
                        
                        <h3 class="text-lg sm:text-xl font-bold text-[#173978] mb-3 sm:mb-4"><?php echo htmlspecialchars(t('services.staffing')); ?></h3>
                        <p class="text-gray-600 mb-6 sm:mb-8 leading-relaxed text-sm flex-grow">
                            <?php echo htmlspecialchars(t('index.staffing_desc')); ?>
                        </p>
                        
                        <a href="<?php echo htmlspecialchars(locale_url('staffing-solutions')); ?>" class="inline-flex items-center text-sm font-bold text-[#173978] group-hover:text-[#2fcaf0] transition-colors uppercase tracking-wide">
                            <?php echo htmlspecialchars(t('common.know_more', 'KNOW MORE')); ?> <i class="fa-solid fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>

                <div class="w-full sm:w-[calc(50%-1rem)] lg:w-[calc(33.333%-1.5rem)] flex" data-aos="fade-up" data-aos-delay="400">
                    <div class="bg-white rounded-[2rem] p-8 sm:p-10 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 group w-full flex flex-col items-center text-center relative">
                        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-transparent via-[#2fcaf0] to-transparent scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>
                        
                        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-white border border-gray-100 rounded-full flex items-center justify-center mb-6 sm:mb-8 shadow-md group-hover:scale-110 transition-transform duration-300">
                            <img src="assets/vectors/BTL.png" alt="BTL" class="w-10 h-10 sm:w-12 sm:h-12 object-contain">
                        </div>
                        
                        <h3 class="text-lg sm:text-xl font-bold text-[#173978] mb-3 sm:mb-4"><?php echo htmlspecialchars(t('services.btl')); ?></h3>
                        <p class="text-gray-600 mb-6 sm:mb-8 leading-relaxed text-sm flex-grow">
                            <?php echo htmlspecialchars(t('index.btl_desc')); ?>
                        </p>
                        
                        <a href="<?php echo htmlspecialchars(locale_url('btl-atl')); ?>" class="inline-flex items-center text-sm font-bold text-[#173978] group-hover:text-[#2fcaf0] transition-colors uppercase tracking-wide">
                            <?php echo htmlspecialchars(t('common.know_more', 'KNOW MORE')); ?> <i class="fa-solid fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>

                <div class="w-full sm:w-[calc(50%-1rem)] lg:w-[calc(33.333%-1.5rem)] flex" data-aos="fade-up" data-aos-delay="500">
                    <div class="bg-white rounded-[2rem] p-8 sm:p-10 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 group w-full flex flex-col items-center text-center relative">
                        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-transparent via-[#2fcaf0] to-transparent scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>
                        
                        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-white border border-gray-100 rounded-full flex items-center justify-center mb-6 sm:mb-8 shadow-md group-hover:scale-110 transition-transform duration-300">
                            <img src="assets/vectors/Lending.png" alt="Lending" class="w-10 h-10 sm:w-12 sm:h-12 object-contain">
                        </div>
                        
                        <h3 class="text-lg sm:text-xl font-bold text-[#173978] mb-3 sm:mb-4"><?php echo htmlspecialchars(t('index.lending_title', 'Lending & Collection Solutions')); ?></h3>
                        <p class="text-gray-600 mb-6 sm:mb-8 leading-relaxed text-sm flex-grow">
                            <?php echo htmlspecialchars(t('index.lending_desc')); ?>
                        </p>
                        
                        <a href="<?php echo htmlspecialchars(locale_url('lending-collection')); ?>" class="inline-flex items-center text-sm font-bold text-[#173978] group-hover:text-[#2fcaf0] transition-colors uppercase tracking-wide">
                            <?php echo htmlspecialchars(t('common.know_more', 'KNOW MORE')); ?> <i class="fa-solid fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>

				<div class="w-full sm:w-[calc(50%-1rem)] lg:w-[calc(33.333%-1.5rem)] flex" data-aos="fade-up" data-aos-delay="200">
                    <div class="bg-white rounded-[2rem] p-8 sm:p-10 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 group w-full flex flex-col items-center text-center relative">
                        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-transparent via-[#2fcaf0] to-transparent scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>
                        
                        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-white border border-gray-100 rounded-full flex items-center justify-center mb-6 sm:mb-8 shadow-md group-hover:scale-110 transition-transform duration-300">
                            <img src="assets/vectors/charging-station.webp" alt="Ev Charging Station" class="w-10 h-10 sm:w-12 sm:h-12 object-contain">
                        </div>
                        
                        <h3 class="text-lg sm:text-xl font-bold text-[#173978] mb-3 sm:mb-4"><?php echo htmlspecialchars(t('index.ev_title', 'EV Infrastructure Support')); ?></h3>
                        <p class="text-gray-600 mb-6 sm:mb-8 leading-relaxed text-sm flex-grow">
                            <?php echo htmlspecialchars(t('index.ev_desc')); ?>
                        </p>
                        
                        <a href="<?php echo htmlspecialchars(locale_url('ev-infrastructure')); ?>" class="inline-flex items-center text-sm font-bold text-[#173978] group-hover:text-[#2fcaf0] transition-colors uppercase tracking-wide">
                            <?php echo htmlspecialchars(t('common.know_more', 'KNOW MORE')); ?> <i class="fa-solid fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>
				
            </div>
        </div>
    </section>

    <section class="py-16 sm:py-24 bg-[#173978] relative w-full" id="industries">
        
        <div class="absolute inset-0 opacity-5 bb-dot-grid-white"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 mb-8 sm:mb-12 text-center">
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-3 sm:mb-4 tracking-tight">
                <?php echo htmlspecialchars(t('index.industries_title')); ?>
            </h2>
            <p class="text-blue-200 text-base sm:text-lg opacity-90 px-2">
                <?php echo htmlspecialchars(t('index.industries_desc')); ?>
            </p>
        </div>

        <?php
        ob_start();
        $industryIcons = [
            ['key' => 'index.industry_fintech', 'path' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z'],
            ['key' => 'index.industry_bfsi', 'path' => 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z'],
            ['key' => 'index.industry_education', 'path' => 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.499 5.221 69.17 69.17 0 00-2.66.81m-15.482 0A50.55 50.55 0 0112 13.489a50.551 50.551 0 017.54-3.342'],
            ['key' => 'index.industry_agritech', 'path' => 'M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z'],
            ['key' => 'index.industry_retail', 'path' => 'M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z'],
        ];
        foreach ($industryIcons as $icon):
        ?>
                <div class="group flex flex-col items-center cursor-pointer min-w-[100px] sm:min-w-[120px] transition-transform duration-300 hover:scale-110">
                    <div class="relative w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center mb-3 sm:mb-4">
                        <div class="blob-shape absolute inset-0 bg-[#cb595c] w-full h-full shadow-lg group-hover:rotate-12 transition-all duration-300"></div>
                        <svg class="w-8 h-8 sm:w-9 sm:h-9 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo htmlspecialchars($icon['path']); ?>" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-white text-sm sm:text-lg text-center"><?php echo htmlspecialchars(t($icon['key'])); ?></h3>
                </div>
        <?php
        endforeach;
        $industryMarqueeItems = ob_get_clean();
        ?>

        <div class="relative w-full overflow-hidden industries-marquee" data-industries-marquee>
            <div class="industries-marquee-track">
                <?php for ($marqueeCopy = 0; $marqueeCopy < 6; $marqueeCopy++): ?>
                <div class="industries-marquee-set"<?php echo $marqueeCopy > 0 ? ' aria-hidden="true"' : ''; ?>>
                    <?php echo $industryMarqueeItems; ?>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <script>
        (function () {
            function measureSetWidth(set) {
                if (!set) return 0;
                return Math.ceil(set.getBoundingClientRect().width);
            }

            function syncIndustriesMarquee() {
                var root = document.querySelector('[data-industries-marquee]');
                if (!root) return;

                var track = root.querySelector('.industries-marquee-track');
                if (!track) return;

                var sets = track.querySelectorAll('.industries-marquee-set');
                var firstSet = sets[0];
                if (!firstSet) return;

                firstSet.removeAttribute('aria-hidden');

                var setWidth = measureSetWidth(firstSet);
                var viewWidth = Math.ceil(root.getBoundingClientRect().width);
                if (setWidth <= 0 || viewWidth <= 0) return;

                var neededSets = Math.max(2, Math.ceil((viewWidth + setWidth) / setWidth) + 1);

                while (track.querySelectorAll('.industries-marquee-set').length < neededSets) {
                    var clone = firstSet.cloneNode(true);
                    clone.setAttribute('aria-hidden', 'true');
                    track.appendChild(clone);
                }

                void track.offsetWidth;

                setWidth = measureSetWidth(firstSet);
                track.style.setProperty('--marquee-end', (-setWidth) + 'px');

                var pxPerSecond = 70;
                track.style.animationDuration = Math.max(18, setWidth / pxPerSecond) + 's';
            }

            function boot() {
                syncIndustriesMarquee();
                window.requestAnimationFrame(syncIndustriesMarquee);
                if (document.fonts && document.fonts.ready) {
                    document.fonts.ready.then(syncIndustriesMarquee);
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }

            var resizeTimer;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(syncIndustriesMarquee, 120);
            });
        })();
        </script>

        <div class="mt-6 sm:mt-8 text-center">
            <span class="text-lg sm:text-xl font-bold text-white tracking-wide">
                <?php echo htmlspecialchars(t('index.industries_other')); ?>
            </span>
        </div>

    </section>

    <?php
    $homeTestimonials = testimonials_fetch_active($pdo, 3);
    if ($homeTestimonials):
    ?>
    <section class="py-16 sm:py-24 bg-gray-50 relative w-full overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12" data-aos="fade-up">
                <span class="text-[#2fcaf0] font-bold tracking-wider uppercase text-sm"><?php echo htmlspecialchars(t('index.client_voices')); ?></span>
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-[#173978] mt-2"><?php echo htmlspecialchars(t('index.trusted_growing')); ?></h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($homeTestimonials as $testimonial): ?>
                <blockquote class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-lg hover:border-[#2fcaf0] transition-all" data-aos="fade-up">
                    <div class="flex gap-1 text-amber-400 mb-3 text-sm">
                        <?php for ($i = 0; $i < (int) ($testimonial['rating'] ?? 5); $i++): ?><i class="fa-solid fa-star"></i><?php endfor; ?>
                    </div>
                    <p class="text-gray-600 text-sm leading-relaxed mb-4">&ldquo;<?php echo htmlspecialchars($testimonial['quote']); ?>&rdquo;</p>
                    <footer class="border-t border-gray-100 pt-4">
                        <p class="font-bold text-[#173978] text-sm"><?php echo htmlspecialchars($testimonial['company']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars(trim(($testimonial['role_title'] ?? '') . (!empty($testimonial['service_line']) ? ' · ' . $testimonial['service_line'] : ''))); ?></p>
                    </footer>
                </blockquote>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="py-16 sm:py-24 bg-white relative w-full overflow-hidden" id="clients">
        
        <div class="absolute inset-0 opacity-40 pointer-events-none bb-dot-grid-gray"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            
            <div class="text-center mb-10 sm:mb-16" data-aos="fade-up">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-800 tracking-tight drop-shadow-sm px-2">
                    <?php echo htmlspecialchars(t('index.trusted_brands')); ?>
                </h2>
            </div>

            <div class="relative w-full overflow-hidden marquee-wrapper">
                <div class="absolute top-0 left-0 h-full w-16 sm:w-24 md:w-48 bg-gradient-to-r from-white to-transparent z-20 pointer-events-none"></div>
                <div class="absolute top-0 right-0 h-full w-16 sm:w-24 md:w-48 bg-gradient-to-l from-white to-transparent z-20 pointer-events-none"></div>

                <div class="animate-marquee flex items-center gap-8 sm:gap-12 py-4">
                    
                    <div class="flex items-center justify-center w-32 sm:w-48 h-16 sm:h-24">
                        <img src="assets/brands/Agrim.jpeg" alt="Agrim" class="max-h-12 sm:max-h-16 w-auto object-contain transition-transform duration-300 hover:scale-110">
                    </div>
                    
                    <div class="flex items-center justify-center w-32 sm:w-48 h-16 sm:h-24">
                        <img src="assets/brands/Jio-Haptik.png" alt="Jio Haptic" class="max-h-16 sm:max-h-24 w-auto object-contain transition-transform duration-300 hover:scale-110">
                    </div>
                    
                    <div class="flex items-center justify-center w-32 sm:w-48 h-16 sm:h-24">
                        <img src="assets/brands/Airtel-Payment.png" alt="Airtel Payment Bank" class="max-h-12 sm:max-h-16 w-auto object-contain transition-transform duration-300 hover:scale-110">
                    </div>
                    
                    <div class="flex items-center justify-center w-32 sm:w-48 h-16 sm:h-24">
                        <img src="assets/brands/Finnable.jpeg" alt="Finnable" class="max-h-12 sm:max-h-16 w-auto object-contain transition-transform duration-300 hover:scale-110">
                    </div>
                    
                    <div class="flex items-center justify-center w-32 sm:w-48 h-16 sm:h-24">
                        <img src="assets/brands/Flexiloan.jpeg" alt="Flexiloan" class="max-h-20 sm:max-h-26 w-auto object-contain transition-transform duration-300 hover:scale-110">
                    </div>
                    
                    <div class="flex items-center justify-center w-32 sm:w-48 h-16 sm:h-24">
                        <img src="assets/brands/InCred.jpeg" alt="InCred" class="max-h-12 sm:max-h-16 w-auto object-contain transition-transform duration-300 hover:scale-110">
                    </div>

                    <div class="flex items-center justify-center w-32 sm:w-48 h-16 sm:h-24">
                        <img src="assets/brands/Kissht.jpeg" alt="Kissht" class="max-h-12 sm:max-h-16 w-auto object-contain transition-transform duration-300 hover:scale-110">
                    </div>
                    
                    <div class="flex items-center justify-center w-32 sm:w-48 h-16 sm:h-24">
                        <img src="assets/brands/Mosambee.jpeg" alt="Mosambee" class="max-h-12 sm:max-h-16 w-auto object-contain transition-transform duration-300 hover:scale-110">
                    </div>

                    <div class="flex items-center justify-center w-32 sm:w-48 h-16 sm:h-24">
                        <img src="assets/brands/RocketPay.webp" alt="RocketPay" class="max-h-12 sm:max-h-16 w-auto object-contain transition-transform duration-300 hover:scale-110">
                    </div>

                    <div class="flex items-center justify-center w-32 sm:w-48 h-16 sm:h-24">
                        <img src="assets/brands/Pine-Labs.jpeg" alt="Pine-Labs" class="max-h-20 sm:max-h-30 w-auto object-contain transition-transform duration-300 hover:scale-110">
                    </div>

                    <div class="flex items-center justify-center w-32 sm:w-48 h-16 sm:h-24">
                        <img src="assets/brands/Jio.png" alt="Jio" class="max-h-12 sm:max-h-16 w-auto object-contain transition-transform duration-300 hover:scale-110">
                    </div>
                    
                    <div class="flex items-center justify-center w-32 sm:w-48 h-16 sm:h-24">
                        <img src="assets/brands/Basic.jpeg" alt="Basic" class="max-h-12 sm:max-h-16 w-auto object-contain transition-transform duration-300 hover:scale-110">
                    </div>
                    
                    <div class="flex items-center justify-center w-32 sm:w-48 h-16 sm:h-24">
                        <img src="assets/brands/FatakPay.jpeg" alt="FatakPay" class="max-h-12 sm:max-h-16 w-auto object-contain transition-transform duration-300 hover:scale-110">
                    </div>

                </div>
            </div>

        </div>
    </section>

    <section class="py-16 sm:py-24 bg-gray-50 relative w-full overflow-hidden" id="about-execution">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center mb-10" data-aos="fade-up">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-[#173978] tracking-tight mb-4">
                    <?php echo htmlspecialchars(t('index.seo_section_title')); ?>
                </h2>
                <p class="text-base sm:text-lg text-gray-600 leading-relaxed">
                    <?php echo htmlspecialchars(t('index.seo_section_subtitle')); ?>
                </p>
            </div>
            <div class="max-w-4xl mx-auto space-y-5 text-gray-600 text-sm sm:text-base leading-relaxed" data-aos="fade-up" data-aos-delay="100">
                <p><?php echo htmlspecialchars(t('index.seo_section_p1')); ?></p>
                <p><?php echo htmlspecialchars(t('index.seo_section_p2')); ?></p>
                <p><?php echo htmlspecialchars(t('index.seo_section_p3')); ?></p>
            </div>
        </div>
    </section>

    <section class="py-16 sm:py-24 bg-white relative w-full overflow-hidden" id="partnership">
        
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
            <div class="absolute top-10 sm:top-20 left-4 sm:left-10 w-48 sm:w-64 h-48 sm:h-64 bg-blue-50 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob"></div>
            <div class="absolute bottom-10 sm:bottom-20 right-4 sm:right-10 w-48 sm:w-64 h-48 sm:h-64 bg-cyan-50 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob animation-delay-2000"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            
            <div class="text-center mb-12 sm:mb-16" data-aos="fade-up">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-800 tracking-tight px-2">
                    <?php echo htmlspecialchars(t('index.how_we_work_title')); ?>
                </h2>
                <p class="mt-4 sm:mt-6 text-base sm:text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed px-2">
                    <?php echo htmlspecialchars(t('index.how_we_work_desc')); ?>
                </p>
            </div>

            <div class="relative grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8 mb-12 sm:mb-16">
                
                <div class="relative z-10 group cursor-default" data-aos="fade-up" data-aos-delay="100">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-white border-4 border-gray-100 group-hover:border-[#2fcaf0] rounded-full flex items-center justify-center shadow-lg group-hover:shadow-[#2fcaf0]/30 transition-all duration-300 transform group-hover:scale-110 mb-4 sm:mb-6">
                            <img src="assets/vectors/Align_Vector.png" alt="Align Vector Icon" 
                              class="w-8 h-8 sm:w-10 sm:h-10 object-contain filter grayscale opacity-60 group-hover:grayscale-0 group-hover:opacity-100 group-hover:brightness-110 transition-all duration-300">
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-800 group-hover:text-[#2fcaf0] transition-colors duration-300"><?php echo htmlspecialchars(t('index.step_align')); ?></h3>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1 sm:mt-2 group-hover:text-[#2fcaf0] transition-colors duration-300"><?php echo htmlspecialchars(t('index.step_align_desc')); ?></p>
                    </div>
                </div>

                <div class="relative z-10 group cursor-default" data-aos="fade-up" data-aos-delay="200">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-white border-4 border-gray-100 group-hover:border-[#2fcaf0] rounded-full flex items-center justify-center shadow-lg group-hover:shadow-[#2fcaf0]/30 transition-all duration-300 transform group-hover:scale-110 mb-4 sm:mb-6">
                            <img src="assets/vectors/DeployVector.png" alt="Deploy Vector Icon" 
                                 class="w-8 h-8 sm:w-10 sm:h-10 object-contain filter grayscale opacity-60 group-hover:grayscale-0 group-hover:opacity-100 group-hover:brightness-110 transition-all duration-300">                    </div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-800 group-hover:text-[#2fcaf0] transition-colors duration-300"><?php echo htmlspecialchars(t('index.step_deploy')); ?></h3>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1 sm:mt-2 group-hover:text-[#2fcaf0] transition-colors duration-300"><?php echo htmlspecialchars(t('index.step_deploy_desc')); ?></p>
                    </div>
                </div>

                <div class="relative z-10 group cursor-default" data-aos="fade-up" data-aos-delay="300">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-white border-4 border-gray-100 group-hover:border-[#2fcaf0] rounded-full flex items-center justify-center shadow-lg group-hover:shadow-[#2fcaf0]/30 transition-all duration-300 transform group-hover:scale-110 mb-4 sm:mb-6">
                             <img src="assets/vectors/ExecuteVector.png" alt="Execute Vector Icon" 
                                class="w-8 h-8 sm:w-10 sm:h-10 object-contain filter grayscale opacity-60 group-hover:grayscale-0 group-hover:opacity-100 group-hover:brightness-110 transition-all duration-300">                    </div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-800 group-hover:text-[#2fcaf0] transition-colors duration-300"><?php echo htmlspecialchars(t('index.step_execute')); ?></h3>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1 sm:mt-2 group-hover:text-[#2fcaf0] transition-colors duration-300"><?php echo htmlspecialchars(t('index.step_execute_desc')); ?></p>
                    </div>
                </div>

                <div class="relative z-10 group cursor-default" data-aos="fade-up" data-aos-delay="400">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-white border-4 border-gray-100 group-hover:border-[#2fcaf0] rounded-full flex items-center justify-center shadow-lg group-hover:shadow-[#2fcaf0]/30 transition-all duration-300 transform group-hover:scale-110 mb-4 sm:mb-6">
                            <img src="assets/vectors/Optimize_Vector.png" alt="Arrow Icon" class="w-16 h-10 sm:w-20 sm:h-12  object-contain transition-transform duration-300 transform group-hover:translate-x-1">
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-800 group-hover:text-[#2fcaf0] transition-colors duration-300"><?php echo htmlspecialchars(t('index.step_optimize')); ?></h3>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1 sm:mt-2 group-hover:text-[#2fcaf0] transition-colors duration-300"><?php echo htmlspecialchars(t('index.step_optimize_desc')); ?></p>
                    </div>
                </div>

            </div>

            <div class="text-center" data-aos="fade-up" data-aos-delay="500">
                <a href="<?php echo htmlspecialchars(locale_url('why-work-with-us')); ?>" class="inline-flex items-center justify-center px-8 sm:px-10 py-3.5 sm:py-4 text-base sm:text-lg font-bold text-white transition-all duration-300 bg-[#2fcaf0] rounded-full shadow-lg hover:bg-[#1eadd0] hover:shadow-xl hover:-translate-y-1 group">
                    <?php echo htmlspecialchars(t('index.work_with_us')); ?>
                    <svg class="w-5 h-5 ml-2 transition-transform duration-300 transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </a>
            </div>

        </div>
    </section>

    <section class="relative py-16 sm:py-24 bg-white w-full overflow-hidden" id="contact-us">
        
        <div class="absolute top-0 left-0 w-48 h-48 sm:w-64 sm:h-64 bg-[#2fcaf0] opacity-5 rounded-full -translate-x-1/2 -translate-y-1/2 blur-2xl sm:blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-64 h-64 sm:w-96 sm:h-96 bg-[#2fcaf0] opacity-5 rounded-full translate-x-1/2 translate-y-1/2 blur-2xl sm:blur-3xl"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 sm:gap-12 lg:gap-16 items-center">
                
                <div class="text-center lg:text-left" data-aos="fade-right">
                    
                    <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-gray-900 tracking-tight mb-4 sm:mb-6 leading-tight">
                        <?php echo htmlspecialchars(t('index.grow_together')); ?> <span class="text-[#2fcaf0]"><?php echo htmlspecialchars(t('index.together')); ?></span>
                    </h2>

                    <p class="text-base sm:text-lg md:text-xl text-gray-600 mb-8 sm:mb-10 font-light leading-relaxed max-w-lg mx-auto lg:mx-0 px-2 sm:px-0">
                        <?php echo htmlspecialchars(t('index.cta_intro')); ?>
                    </p>

                    <div class="flex flex-col sm:flex-row items-center lg:justify-start justify-center gap-4 sm:gap-6 w-full">
                        
                        <div class="w-full sm:w-auto flex flex-col gap-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider hidden sm:block pl-2"><?php echo htmlspecialchars(t('index.for_businesses')); ?></span>
                            <a href="<?php echo htmlspecialchars(locale_url('why-work-with-us')); ?>" class="w-full sm:w-auto px-6 sm:px-8 py-3.5 sm:py-4 bg-[#2fcaf0] text-white text-base sm:text-lg font-bold rounded-full shadow-lg hover:shadow-xl hover:bg-[#25b2d9] hover:-translate-y-1 transition-all duration-300 flex items-center justify-center">
                                <?php echo htmlspecialchars(t('index.explore_solutions')); ?>
                                <i class="fa-solid fa-briefcase ml-2"></i>
                            </a>
                        </div>

                        <div class="w-full sm:w-auto flex flex-col gap-2 mt-4 sm:mt-0">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider hidden sm:block pl-2"><?php echo htmlspecialchars(t('index.for_partners')); ?></span>
                            <a href="<?php echo htmlspecialchars(locale_url('partner-with-us')); ?>" class="w-full sm:w-auto px-6 sm:px-8 py-3.5 sm:py-4 bg-transparent border-2 border-[#173978] text-[#173978] text-base sm:text-lg font-bold rounded-full hover:bg-[#173978] hover:text-white hover:-translate-y-1 transition-all duration-300 flex items-center justify-center">
                                <?php echo htmlspecialchars(t('index.become_partner')); ?>
                                <i class="fa-solid fa-handshake ml-2"></i>
                            </a>
                        </div>

                    </div>
                </div>

                <div class="relative flex justify-center lg:justify-end w-full mt-10 lg:mt-0" data-aos="fade-left">
                    
                    <div class="absolute -inset-2 sm:-inset-4 bg-[#2fcaf0]/30 rounded-2xl sm:rounded-3xl blur-lg sm:blur-xl -z-10 transform scale-95"></div>

                    <div class="bg-[#2fcaf0] p-6 sm:p-8 md:p-12 rounded-2xl sm:rounded-3xl w-full">
                        <div class="mb-6 sm:mb-8 text-center sm:text-left">
                            <h2 class="text-2xl sm:text-3xl font-bold text-white mb-1 sm:mb-2"><?php echo htmlspecialchars(t('index.get_in_touch')); ?></h2>
                            <p class="text-sm sm:text-base text-blue-100"><?php echo htmlspecialchars(t('index.form_intro')); ?></p>
                        </div>

                        <form action="" method="POST" class="space-y-4" data-enquiry-form="1">
                            <?php enquiry_form_hidden_fields(); ?>
                            <input type="text" name="website_check" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <input type="text" name="name" placeholder="<?php echo htmlspecialchars(t('index.form_name')); ?>" class="w-full px-4 py-3 text-sm sm:text-base rounded-lg border-0 focus:ring-2 focus:ring-[#173978] outline-none" required>
                                <input type="email" name="email" placeholder="<?php echo htmlspecialchars(t('index.form_email')); ?>" class="w-full px-4 py-3 text-sm sm:text-base rounded-lg border-0 focus:ring-2 focus:ring-[#173978] outline-none" required>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <input type="tel" name="phone" placeholder="<?php echo htmlspecialchars(t('index.form_phone')); ?>" class="w-full px-4 py-3 text-sm sm:text-base rounded-lg border-0 focus:ring-2 focus:ring-[#173978] outline-none" required>
                                <input type="text" name="subject" placeholder="<?php echo htmlspecialchars(t('index.form_subject')); ?>" class="w-full px-4 py-3 text-sm sm:text-base rounded-lg border-0 focus:ring-2 focus:ring-[#173978] outline-none">
                            </div>
                            
                            <textarea name="message" rows="4" placeholder="<?php echo htmlspecialchars(t('index.form_message')); ?>" class="w-full px-4 py-3 text-sm sm:text-base rounded-lg border-0 focus:ring-2 focus:ring-[#173978] outline-none" required></textarea>
                            
                            <button type="submit" class="bg-[#173978] text-white font-bold py-3.5 px-8 rounded-lg hover:bg-[#0f2655] transition-all w-full sm:w-auto shadow-lg text-base sm:text-lg">
                                <?php echo htmlspecialchars(t('index.send_message')); ?> <i class="fa-solid fa-paper-plane ml-2"></i>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>

</div> <script>
    function scrollToContactUs() {
        const section = document.getElementById('contact-us');
        if (section) { section.scrollIntoView({ behavior: 'smooth' }); }
    }
    function scrollToServices() {
        const section = document.getElementById('services');
        if (section) { section.scrollIntoView({ behavior: 'smooth' }); }
    }
</script>

<?php enquiry_render_form_validation_script(); ?>

<?php include 'includes/footer.php'; ?>