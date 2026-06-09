<?php
require 'db.php';
require_once 'includes/case-study-helpers.php';

$pageTitle = 'Client Success Stories | Bisani Brothers';
$pageDesc = 'Read client success stories from Bisani Brothers — how FinTech, NBFC, retail, and enterprise brands scale with disciplined field execution, staffing, and measurable on-ground results across India.';

$cases = [];
try {
    $cases = case_study_fetch_published($pdo);
} catch (PDOException $e) {
}

require_once 'includes/seo.php';
$caseCanonical = seo_canonical_for_path('case-studies');
$caseSchemaItems = [];
foreach ($cases as $cs) {
    $caseSchemaItems[] = [
        'name' => $cs['title'],
        'url'  => seo_site_url_rtrim() . '/case-studies/' . rawurlencode($cs['slug']),
    ];
}
$pageSchemas = [
    seo_collection_page_schema($pageTitle, $pageDesc, $caseCanonical),
];
if ($caseSchemaItems !== []) {
    $pageSchemas[] = seo_item_list_schema($pageTitle, $pageDesc, $caseSchemaItems, $caseCanonical);
}

include 'includes/header.php';
?>

<section class="py-20 md:py-28 bg-[#173978] text-white text-center">
    <div class="max-w-3xl mx-auto px-4">
        <h1 class="text-3xl md:text-5xl font-extrabold mb-4"><?php echo page_te('hero_title'); ?></h1>
        <p class="text-blue-100 text-lg"><?php echo page_te('hero_desc'); ?></p>
    </div>
</section>

<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if ($cases): ?>
        <div class="grid md:grid-cols-2 gap-8">
            <?php foreach ($cases as $cs): ?>
            <article class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-xl hover:border-[#2fcaf0] transition-all group">
                <?php if (!empty($cs['image_path'])): ?>
                <div class="aspect-video bg-gray-100 overflow-hidden">
                    <?php require_once __DIR__ . '/includes/upload-storage.php'; ?>
                    <img src="<?php echo htmlspecialchars(upload_storage_public_url($cs['image_path'])); ?>" alt="<?php echo htmlspecialchars($cs['title']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                </div>
                <?php endif; ?>
                <div class="p-8">
                    <div class="flex flex-wrap gap-2 mb-3">
                        <?php if ($cs['industry']): ?><span class="text-[10px] font-bold uppercase bg-blue-50 text-[#173978] px-2 py-1 rounded"><?php echo htmlspecialchars($cs['industry']); ?></span><?php endif; ?>
                        <?php if ($cs['service_line']): ?><span class="text-[10px] font-bold uppercase bg-cyan-50 text-[#2fcaf0] px-2 py-1 rounded"><?php echo htmlspecialchars($cs['service_line']); ?></span><?php endif; ?>
                    </div>
                    <h2 class="text-xl font-bold text-[#173978] mb-2 group-hover:text-[#2fcaf0] transition-colors">
                        <a href="case-studies/<?php echo htmlspecialchars($cs['slug']); ?>"><?php echo htmlspecialchars($cs['title']); ?></a>
                    </h2>
                    <p class="text-gray-500 text-sm line-clamp-3"><?php echo htmlspecialchars(strip_tags($cs['results'] ?? $cs['challenge'] ?? '')); ?></p>
                    <a href="case-studies/<?php echo htmlspecialchars($cs['slug']); ?>" class="inline-flex items-center mt-4 text-sm font-bold text-[#173978] hover:text-[#2fcaf0]"><?php echo page_te('read_more'); ?> <i class="fa-solid fa-arrow-right ml-2 text-xs"></i></a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center text-gray-500 py-12"><?php echo page_te('empty'); ?></p>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
