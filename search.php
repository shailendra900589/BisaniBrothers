<?php
require 'db.php';
require_once 'includes/seo.php';
require_once 'includes/search-helpers.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['search']) ? trim($_GET['search']) : '');
$pageTitle = $q !== '' ? t('search.title') . ': ' . $q . ' | Bisani Brothers' : t('pages.search.title');
$pageDesc = t('pages.search.desc');
$results = $q !== '' ? search_site($pdo, $q) : [];

include 'includes/header.php';
?>

<section class="py-16 md:py-24 bg-[#173978] text-white">
    <div class="max-w-3xl mx-auto px-4 text-center">
        <h1 class="text-3xl md:text-4xl font-extrabold mb-4"><?php echo htmlspecialchars(t('search.title')); ?></h1>
        <p class="text-blue-100 mb-8"><?php echo htmlspecialchars(t('search.subtitle')); ?></p>
        <form action="<?php echo htmlspecialchars(locale_url('search')); ?>" method="get" class="flex gap-2 max-w-xl mx-auto" role="search">
            <input type="search" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="<?php echo htmlspecialchars(t('search.placeholder')); ?>" class="flex-1 rounded-xl px-4 py-3 text-[#173978] font-medium focus:outline-none focus:ring-2 focus:ring-[#2fcaf0]" required minlength="2">
            <button type="submit" class="px-6 py-3 bg-[#2fcaf0] text-[#173978] font-bold rounded-xl hover:bg-white transition-colors"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </div>
</section>

<section class="py-12 bg-gray-50 min-h-[40vh]">
    <div class="max-w-3xl mx-auto px-4">
        <?php if ($q === ''): ?>
            <p class="text-gray-500 text-center"><?php echo htmlspecialchars(t('search.min_chars')); ?></p>
        <?php elseif (empty($results)): ?>
            <div class="bg-white rounded-2xl border border-gray-200 p-10 text-center">
                <i class="fa-regular fa-face-frown text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-600 font-medium"><?php echo htmlspecialchars(t('search.no_results')); ?> <strong><?php echo htmlspecialchars($q); ?></strong></p>
                <p class="text-sm text-gray-400 mt-2"><?php echo htmlspecialchars(t('search.try_other')); ?> <a href="<?php echo htmlspecialchars(locale_url('contact')); ?>" class="text-[#2fcaf0] font-semibold hover:underline"><?php echo htmlspecialchars(t('search.contact_us')); ?></a>.</p>
            </div>
        <?php else: ?>
            <p class="text-sm text-gray-500 mb-6"><?php echo count($results); ?> <?php echo htmlspecialchars(t('search.results_for')); ?> &ldquo;<?php echo htmlspecialchars($q); ?>&rdquo;</p>
            <div class="space-y-4">
                <?php foreach ($results as $r): ?>
                <a href="<?php echo htmlspecialchars($r['url']); ?>" class="block bg-white rounded-xl border border-gray-200 p-5 hover:border-[#2fcaf0] hover:shadow-md transition-all group">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#2fcaf0] bg-cyan-50 px-2 py-0.5 rounded"><?php echo htmlspecialchars(locale_type_label($r['type'])); ?></span>
                    <h2 class="text-lg font-bold text-[#173978] mt-2 group-hover:text-[#2fcaf0] transition-colors"><?php echo htmlspecialchars($r['title']); ?></h2>
                    <?php if (!empty($r['desc'])): ?>
                    <p class="text-sm text-gray-500 mt-1 line-clamp-2"><?php echo htmlspecialchars($r['desc']); ?></p>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
