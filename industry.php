<?php
require 'db.php';
require_once 'includes/seo.php';
require_once 'includes/industry-config.php';
require_once 'includes/case-study-helpers.php';

$slug = trim($_GET['slug'] ?? '');
$ind = industry_get($slug);

if (!$ind) {
    http_response_code(404);
    $pageTitle = 'Industry Not Found | Bisani Brothers';
    include '404.php';
    exit();
}

$pageTitle = ($ind['meta_title'] ?? $ind['headline']) . ' | Bisani Brothers';
$pageDesc = $ind['meta_desc'] ?? $ind['tagline'];
$base = seo_site_url_rtrim();
$pageUrl = $base . '/' . industry_url($slug);

$pageSchemas = [
    seo_service_schema($ind['headline'], $pageDesc, $pageUrl),
    seo_breadcrumb_schema([
        ['name' => 'Home', 'url' => $base . '/'],
        ['name' => 'Industries', 'url' => $base . '/industries'],
        ['name' => $ind['name'], 'url' => $pageUrl],
    ]),
];

$relatedCases = [];
try {
    $relatedCases = case_study_fetch_published($pdo, $ind['name'], 3);
} catch (PDOException $e) {
}

$serviceLinks = [
    'Sales & Growth'           => 'sales-growth',
    'Lending & Collection'     => 'lending-collection',
    'Staffing Solutions'       => 'staffing-solutions',
    'Survey & Market Research' => 'survey-market-research',
    'BTL & ATL Activation'     => 'btl-atl',
    'EV Infrastructure'        => 'ev-infrastructure',
];

include 'includes/header.php';
?>

<section class="py-20 md:py-32 bg-[#173978] text-white relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <nav class="text-sm text-blue-200 mb-6">
            <a href="./" class="hover:text-white">Home</a> / <a href="industries" class="hover:text-white">Industries</a> / <?php echo htmlspecialchars($ind['name']); ?>
        </nav>
        <div class="max-w-3xl">
            <div class="inline-flex items-center gap-2 bg-white/10 rounded-full px-4 py-1.5 text-sm mb-6">
                <i class="<?php echo htmlspecialchars($ind['icon']); ?> text-[#2fcaf0]"></i> <?php echo htmlspecialchars($ind['name']); ?>
            </div>
            <h1 class="text-3xl md:text-5xl font-extrabold mb-4 leading-tight"><?php echo htmlspecialchars($ind['headline']); ?></h1>
            <p class="text-blue-100 text-lg md:text-xl"><?php echo htmlspecialchars($ind['tagline']); ?></p>
        </div>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-3 gap-12">
        <div class="lg:col-span-2">
            <p class="text-gray-600 text-lg leading-relaxed mb-8"><?php echo htmlspecialchars($ind['body']); ?></p>
            <h2 class="text-2xl font-bold text-[#173978] mb-4">What We Deliver</h2>
            <ul class="space-y-3 mb-10">
                <?php foreach ($ind['highlights'] as $h): ?>
                <li class="flex items-start gap-3 text-gray-700"><i class="fa-solid fa-check text-[#2fcaf0] mt-1"></i> <?php echo htmlspecialchars($h); ?></li>
                <?php endforeach; ?>
            </ul>
            <h2 class="text-2xl font-bold text-[#173978] mb-4">Relevant Services</h2>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($ind['services'] as $svc):
                    $link = $serviceLinks[$svc] ?? 'contact';
                ?>
                <a href="<?php echo htmlspecialchars($link); ?>" class="px-4 py-2 rounded-full bg-slate-100 text-[#173978] text-sm font-semibold hover:bg-[#2fcaf0] hover:text-[#173978] transition-colors"><?php echo htmlspecialchars($svc); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <aside>
            <div class="bg-gray-50 rounded-2xl border border-gray-200 p-6 sticky top-28">
                <h3 class="font-bold text-[#173978] mb-4">Impact at a Glance</h3>
                <?php foreach ($ind['stats'] as $st): ?>
                <div class="mb-4 pb-4 border-b border-gray-200 last:border-0">
                    <p class="text-2xl font-extrabold text-[#2fcaf0]"><?php echo htmlspecialchars($st['value']); ?></p>
                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($st['label']); ?></p>
                </div>
                <?php endforeach; ?>
                <a href="contact" class="mt-4 block w-full text-center py-3 bg-[#173978] text-white font-bold rounded-lg hover:bg-[#2fcaf0] hover:text-[#173978] transition-colors">Talk to Our Team</a>
            </div>
        </aside>
    </div>
</section>

<?php if ($relatedCases): ?>
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-2xl font-bold text-[#173978] mb-8">Related Success Stories</h2>
        <div class="grid md:grid-cols-3 gap-6">
            <?php foreach ($relatedCases as $cs): ?>
            <a href="case-studies/<?php echo htmlspecialchars($cs['slug']); ?>" class="bg-white rounded-xl border p-6 hover:border-[#2fcaf0] hover:shadow-lg transition-all">
                <span class="text-xs font-bold text-[#2fcaf0] uppercase"><?php echo htmlspecialchars($cs['service_line'] ?? ''); ?></span>
                <h3 class="font-bold text-[#173978] mt-2"><?php echo htmlspecialchars($cs['title']); ?></h3>
                <p class="text-sm text-gray-500 mt-2 line-clamp-2"><?php echo htmlspecialchars(strip_tags($cs['results'] ?? '')); ?></p>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-8"><a href="case-studies" class="text-[#173978] font-bold hover:text-[#2fcaf0]">View all case studies →</a></div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
