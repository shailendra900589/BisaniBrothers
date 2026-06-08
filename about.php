<?php
// ==========================================
//  SEO SETTINGS FOR ABOUT PAGE
// ==========================================
$pageTitle = "About Bisani Brothers | Built for Execution, Designed for Scale";
$pageDesc  = "Learn how Bisani Brothers partners with growing businesses to scale faster across India through sales execution, staffing solutions, market research, and disciplined on-ground operations.";

include 'includes/header.php'; // Assuming you have a header.php file
?>

<section class="py-24 lg:py-32 bg-cover bg-center bg-no-repeat relative flex items-center about-hero-bg" id="about-intro">
    
    <div class="absolute inset-0 bg-black/20"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full">
        
        <div class="max-w-xl lg:max-w-2xl bg-[#0f1d35]/60 backdrop-blur-lg border border-white/10 rounded-[2rem] p-8 md:p-12 shadow-2xl" data-aos="fade-right">
            
            <h1 class="text-4xl md:text-5xl font-extrabold leading-tight mb-6">
                <span class="text-[#2fcaf0] block mb-2"><?php echo page_te('hero_h1_a'); ?></span>
                <span class="text-white"><?php echo page_te('hero_h1_b'); ?></span>
            </h1>

            <p class="text-lg text-gray-100 leading-relaxed font-medium mb-8">
                <?php echo page_te('hero_desc'); ?>
            </p>

            <button onclick="scrollToBuildNext()" class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-base font-bold rounded text-[#052c65] bg-[#2fcaf0] hover:bg-white md:text-lg transition-all duration-300 shadow-[0_0_20px_rgba(47,202,240,0.3)] hover:shadow-[0_0_30px_rgba(47,202,240,0.6)] cursor-pointer group">
                <?php echo page_te('hero_btn'); ?>
                <i class="fa-solid fa-arrow-right ml-3 transition-transform group-hover:translate-x-1"></i>
            </button>

        </div>

    </div>
</section>

<section class="py-24 bg-[#f4f7fc] relative overflow-hidden font-sans" id="our-story">
    
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <div class="bg-white rounded-[2.5rem] p-8 md:p-16 shadow-[0_20px_60px_rgba(0,0,0,0.05)] border border-gray-100" data-aos="fade-up">
            
            <div class="space-y-8 text-lg md:text-xl text-gray-600 leading-relaxed">
                <p>
                    <span class="font-bold text-[#173978]">Bisani Brothers Private Limited</span> is built on one simple idea: to 
                    <span class="font-bold text-[#173978] border-b-[3px] border-[#2fcaf0] pb-0.5">grow together</span>. 
                    We partner with businesses to help them <span class="font-bold text-[#173978]">scale</span>, as well as with individuals and agencies that bring <span class="font-bold text-[#173978]">dedication, energy, and expertise</span> to every project.
                </p>
                

                <p>
                    Whether it’s merchant onboarding, surveys, lending, or on-ground activation, our work is driven by 
                    <span class="font-bold text-[#173978] border-b-[3px] border-[#2fcaf0] pb-0.5">creating meaningful impact and tangible growth</span> — for our clients and for every person who associates with us.
                </p>

                <div class="border-l-[5px] border-[#173978] pl-8 py-2 my-10 bg-gray-50/50 rounded-r-2xl">
                    <p class="font-medium text-[#173978] italic text-xl leading-relaxed">
                        We don’t just execute services; we become an extension of your business, aligning with your goals and sharing in the journey of growth.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-12">
                
                <div class="bg-[#f0f9ff] p-8 md:p-10 rounded-3xl border border-[#2fcaf0]/20 hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center gap-4 mb-5">
                        <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-[#2fcaf0] shadow-sm">
                            <i class="fa-solid fa-eye text-sm"></i>
                        </div>
                        <span class="text-sm font-bold text-[#2fcaf0] uppercase tracking-wider"><?php echo page_te('vision'); ?></span>
                    </div>
                    <p class="text-[#173978] font-bold text-lg leading-relaxed">
                        <?php echo page_te('vision_text'); ?>
                    </p>
                </div>

                <div class="bg-[#fff5f5] p-8 md:p-10 rounded-3xl border border-[#cb595c]/20 hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center gap-4 mb-5">
                        <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-[#cb595c] shadow-sm">
                            <i class="fa-solid fa-bullseye text-sm"></i>
                        </div>
                        <span class="text-sm font-bold text-[#cb595c] uppercase tracking-wider"><?php echo page_te('mission'); ?></span>
                    </div>
                    <p class="text-[#173978] font-bold text-lg leading-relaxed">
                        <?php echo page_te('mission_text'); ?>
                    </p>
                </div>

            </div>

        </div>
    </div>
</section>



<section class="py-24 bg-[#173978] relative overflow-hidden font-sans">
  
  <div class="absolute inset-0 opacity-10 pointer-events-none bb-dot-grid-white-md"></div>

  <div class="absolute inset-0 bg-gradient-to-br from-transparent via-black/10 to-black/30 pointer-events-none"></div>

  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
    <div class="flex flex-col md:flex-row items-center gap-10 md:gap-20">

      <div class="w-full md:w-1/3 flex flex-col items-center justify-center text-center flex-shrink-0">
        
        <div class="mb-6">
            <img src="assets/vectors/Our_Story.png" alt="Our Story" class="w-20 h-20 md:w-24 md:h-24 object-contain">
        </div>
        
        <h2 class="text-4xl md:text-5xl font-extrabold text-white uppercase tracking-tight leading-none drop-shadow-md">
          <?php echo page_te('story_title'); ?>
        </h2>
      </div>

      <div class="w-full md:w-2/3">
        
        <p class="text-lg md:text-xl text-blue-50 leading-loose text-justify font-normal opacity-95">
          Bisani Brothers was shaped by a moment that redefined purpose. After a life-altering experience, our founder, 
          <span class="font-bold text-[#2fcaf0]">Mr. Ashish Bisani</span>, set out to create something that goes beyond business, a platform that creates 
          <span class="font-bold text-[#2fcaf0]">opportunity</span>, <span class="font-bold text-[#2fcaf0]">employment</span>, and <span class="font-bold text-[#2fcaf0]">growth</span>. 
          What began as a vision to give back evolved into an ecosystem where individuals and companies grow together. Over the years, we’ve enabled 
          <span class="font-bold text-[#2fcaf0]">2,100+ people</span> across <span class="font-bold text-[#2fcaf0]">tier-one and tier-two cities</span> to build sustainable livelihoods while contributing to real business outcomes.
        </p>

        <p class="mt-8 text-lg md:text-xl text-blue-50 leading-loose text-justify font-normal opacity-95">
            At <span class="font-bold text-white">Bisani Brothers</span>, growth isn’t transactional; it’s <span class="font-bold text-[#2fcaf0]">collaborative</span>.
            When our partners grow, our people grow, and together, we build something that lasts.
        </p>

      </div>

    </div>
  </div>
</section>

<section class="py-24 bg-blue-50 relative overflow-hidden" id="build-next">
    
    <div class="absolute top-0 left-0 -translate-x-10 -translate-y-10 w-64 h-64 bg-[#2fcaf0] opacity-10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 right-0 translate-x-10 translate-y-10 w-64 h-64 bg-[#cb595c] opacity-10 rounded-full blur-3xl"></div>
    
    <div class="absolute inset-0 opacity-5 pointer-events-none about-pattern-bg"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        
        <div class="mb-8 flex justify-center" data-aos="zoom-in" data-aos-duration="1000">
             </div>

        <h2 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-6 tracking-tight leading-tight" data-aos="fade-up">
            <?php echo page_te('cta_title'); ?>
        </h2>

        <!-- <div class="w-24 h-1.5 bg-[#cb595c] rounded-full mx-auto mb-8" data-aos="zoom-in" data-aos-delay="200"></div> -->

        <p class="text-xl text-gray-600 leading-relaxed font-medium mb-10" data-aos="fade-up" data-aos-delay="300">
            If you’re looking to <span class="text-gray-900 font-bold">partner with a team</span> or <span class="text-gray-900 font-bold">that values ownership, execution, and <br> long-term thinking</span> we’d be glad to start a conversation.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-6" data-aos="fade-up" data-aos-delay="400">
            
            <a href="<?php echo htmlspecialchars(locale_url('partner-with-us')); ?>" class="group relative inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-white transition-all duration-200 bg-[#2fcaf0] rounded-xl hover:bg-[#25b2d9] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#2fcaf0] shadow-lg hover:shadow-xl hover:-translate-y-1">
                <i class="fa-solid fa-handshake mr-3"></i>
                <?php echo page_te('cta_partner'); ?>
                <div class="absolute inset-0 rounded-xl ring-2 ring-white/20 group-hover:ring-white/40"></div>
            </a>

            <a href="<?php echo htmlspecialchars(locale_url('careers')); ?>" class="group relative inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-[#cb595c] transition-all duration-200 bg-white border-2 border-[#cb595c] rounded-xl hover:bg-[#cb595c] hover:text-white focus:outline-none shadow-md hover:shadow-xl hover:-translate-y-1">
                <i class="fa-solid fa-users mr-3"></i>
                <?php echo page_te('cta_careers'); ?>
            </a>

        </div>

    </div>
</section>

<script>
    function scrollToBuildNext() {
        const section = document.getElementById('build-next');
        if (section) {
            section.scrollIntoView({ behavior: 'smooth' });
        }
    }
</script>

<?php include 'includes/footer.php'; ?>