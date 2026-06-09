<?php
require 'db.php';
require_once 'includes/seo.php';

$filterCat = isset($_GET['category']) ? trim($_GET['category']) : '';

$pageTitle = 'FAQs | Bisani Brothers';
$pageDesc = 'Frequently asked questions about Bisani Brothers services — staffing, FinTech execution, BTL activation, careers, and partnerships.';

$faqs = [];
try {
    $localeCol = $pdo->query("SHOW COLUMNS FROM site_faqs LIKE 'locale'")->fetch();
    $sql = 'SELECT id, category, question, answer FROM site_faqs WHERE is_active = 1';
    $params = [];
    if ($localeCol) {
        $sql .= ' AND locale = ?';
        $params[] = locale_current();
    }
    if ($filterCat !== '') {
        $sql .= ' AND category = ?';
        $params[] = $filterCat;
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}

$categories = [];
try {
    $categories = $pdo->query("SELECT DISTINCT category FROM site_faqs WHERE is_active = 1" . ($localeCol ? " AND locale = '" . str_replace("'", "''", locale_current()) . "'" : '') . " ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
}

$faqSchemaItems = [];
foreach ($faqs as $f) {
    $faqSchemaItems[] = ['question' => $f['question'], 'answer' => strip_tags($f['answer'])];
}
$pageSchemas = [];
if ($faqSchemaItems) {
    $pageSchemas[] = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn($item) => [
            '@type' => 'Question',
            'name' => $item['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
        ], $faqSchemaItems),
    ];
}
$pageSchemas[] = seo_webpage_schema($pageTitle, $pageDesc, seo_canonical_for_path('faqs'));

include 'includes/header.php';
?>

<section class="py-20 md:py-28 bg-[#173978] text-white text-center">
    <div class="max-w-3xl mx-auto px-4">
        <h1 class="text-3xl md:text-5xl font-extrabold mb-4"><?php echo htmlspecialchars(t('pages.faqs.heading', 'Frequently Asked Questions')); ?></h1>
        <p class="text-blue-100 text-lg"><?php echo htmlspecialchars(t('search.subtitle')); ?></p>
    </div>
</section>

<section class="py-16 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4">
        <?php if ($categories): ?>
        <div class="flex flex-wrap gap-2 mb-10 justify-center">
            <a href="faqs" class="px-4 py-2 rounded-full text-sm font-bold <?php echo $filterCat === '' ? 'bg-[#173978] text-white' : 'bg-white text-gray-600 border border-gray-200 hover:border-[#2fcaf0]'; ?>"><?php echo page_te('filter_all'); ?></a>
            <?php foreach ($categories as $cat): ?>
            <a href="faqs?category=<?php echo urlencode($cat); ?>" class="px-4 py-2 rounded-full text-sm font-bold <?php echo $filterCat === $cat ? 'bg-[#173978] text-white' : 'bg-white text-gray-600 border border-gray-200 hover:border-[#2fcaf0]'; ?>"><?php echo htmlspecialchars($cat); ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($faqs): ?>
        <div class="space-y-4">
            <?php foreach ($faqs as $f):
                $fid = 'faq-' . md5($f['question']);
            ?>
            <details id="<?php echo htmlspecialchars($fid); ?>" class="bg-white rounded-xl border border-gray-200 overflow-hidden group open:border-[#2fcaf0] open:shadow-md">
                <summary class="px-6 py-5 font-bold text-[#173978] cursor-pointer list-none flex justify-between items-center gap-4">
                    <span><?php echo htmlspecialchars($f['question']); ?></span>
                    <i class="fa-solid fa-chevron-down text-[#2fcaf0] text-sm transition-transform group-open:rotate-180 shrink-0"></i>
                </summary>
                <div class="px-6 pb-5 text-gray-600 leading-relaxed border-t border-gray-100 pt-4"><?php echo nl2br(htmlspecialchars($f['answer'])); ?></div>
            </details>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center text-gray-500"><?php echo page_te('empty'); ?></p>
        <?php endif; ?>

        <div class="mt-12 text-center bg-white rounded-2xl border border-gray-200 p-8">
            <h2 class="text-xl font-bold text-[#173978] mb-2"><?php echo page_te('still_title'); ?></h2>
            <p class="text-gray-500 mb-4"><?php echo page_te('still_desc'); ?></p>
            <a href="contact" class="inline-flex items-center px-6 py-3 bg-[#173978] text-white font-bold rounded-lg hover:bg-[#2fcaf0] hover:text-[#173978] transition-colors"><?php echo page_te('contact_btn'); ?> <i class="fa-solid fa-arrow-right ml-2"></i></a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
