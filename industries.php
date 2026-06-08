<?php
require 'db.php';
require_once 'includes/industry-config.php';

$pageTitle = 'Industries We Serve | Bisani Brothers';
$pageDesc = 'FinTech, BFSI, retail, EV, education & telecom — on-ground execution and staffing across India.';
$industries = industry_get_all();

include 'includes/header.php';
?>

<section class="py-20 md:py-28 bg-[#173978] text-white text-center">
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl md:text-5xl font-extrabold mb-4"><?php echo page_te('hero_title'); ?></h1>
        <p class="text-blue-100 text-lg"><?php echo page_te('hero_desc'); ?></p>
    </div>
</section>

<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($industries as $slug => $ind): ?>
            <a href="<?php echo industry_url($slug); ?>" class="group bg-white rounded-2xl border border-gray-200 p-8 hover:border-[#2fcaf0] hover:shadow-xl transition-all">
                <div class="w-14 h-14 rounded-xl bg-[#173978]/10 flex items-center justify-center text-[#173978] text-2xl mb-5 group-hover:bg-[#2fcaf0] group-hover:text-[#173978] transition-colors">
                    <i class="<?php echo htmlspecialchars($ind['icon']); ?>"></i>
                </div>
                <h2 class="text-xl font-bold text-[#173978] mb-2 group-hover:text-[#2fcaf0] transition-colors"><?php echo htmlspecialchars($ind['name']); ?></h2>
                <p class="text-gray-500 text-sm leading-relaxed"><?php echo htmlspecialchars($ind['tagline']); ?></p>
                <span class="inline-flex items-center mt-4 text-sm font-bold text-[#173978] group-hover:text-[#2fcaf0]"><?php echo page_te('explore'); ?> <i class="fa-solid fa-arrow-right ml-2 text-xs"></i></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
