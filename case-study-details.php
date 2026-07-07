<?php
require 'db.php';
require_once 'includes/seo.php';
require_once 'includes/case-study-helpers.php';

$slug = trim($_GET['slug'] ?? '');
$cs = null;
if ($slug !== '') {
    $cs = case_study_fetch_by_slug($pdo, $slug);
}

if (!$cs) {
    http_response_code(404);
    $pageTitle = 'Case Study Not Found | Bisani Brothers';
    include '404.php';
    exit();
}

$base = seo_site_url_rtrim();
$pageTitle = ($cs['meta_title'] ?: $cs['title']) . ' | Bisani Brothers';
$pageDesc = $cs['meta_desc'] ?: strip_tags($cs['results'] ?? '');
$pageImg = seo_absolute_image($cs['image_path'] ?? '', $base);
$ogType = 'article';
$pageUrl = case_study_post_url($cs['slug'], $base);

$pageSchemas = [
    seo_case_study_schema($cs, $base),
    seo_breadcrumb_schema([
        ['name' => 'Home', 'url' => $base . '/'],
        ['name' => 'Case Studies', 'url' => $base . '/case-studies'],
        ['name' => $cs['title'], 'url' => $pageUrl],
    ]),
];

$related = [];
try {
    $stmt = $pdo->prepare('SELECT title, slug, industry, service_line, results FROM case_studies WHERE is_published = 1 AND id != ? ORDER BY created_at DESC LIMIT 3');
    $stmt->execute([$cs['id']]);
    $related = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}

if ($related === [] && case_study_demo_by_slug($cs['slug'] ?? '')) {
    foreach (case_study_demo_entries() as $demo) {
        if ($demo['slug'] === ($cs['slug'] ?? '')) {
            continue;
        }
        $related[] = [
            'title'        => $demo['title'],
            'slug'         => $demo['slug'],
            'industry'     => $demo['industry'],
            'service_line' => $demo['service_line'],
            'results'      => $demo['results'],
        ];
        if (count($related) >= 3) {
            break;
        }
    }
}

include 'includes/header.php';
?>

<section class="py-16 md:py-24 bg-[#173978] text-white">
    <div class="max-w-4xl mx-auto px-4">
        <nav class="text-sm text-blue-200 mb-4"><a href="./" class="hover:text-white">Home</a> / <a href="case-studies" class="hover:text-white">Case Studies</a></nav>
        <div class="flex flex-wrap gap-2 mb-4">
            <?php if ($cs['industry']): ?><span class="text-xs font-bold bg-white/10 px-3 py-1 rounded-full"><?php echo htmlspecialchars($cs['industry']); ?></span><?php endif; ?>
            <?php if ($cs['service_line']): ?><span class="text-xs font-bold bg-[#2fcaf0]/20 text-[#2fcaf0] px-3 py-1 rounded-full"><?php echo htmlspecialchars($cs['service_line']); ?></span><?php endif; ?>
        </div>
        <h1 class="text-3xl md:text-4xl font-extrabold leading-tight"><?php echo htmlspecialchars($cs['title']); ?></h1>
        <?php if ($cs['client_name']): ?><p class="text-blue-100 mt-3"><?php echo htmlspecialchars($cs['client_name']); ?></p><?php endif; ?>
    </div>
</section>

<section class="py-12 bg-white">
    <div class="max-w-4xl mx-auto px-4">
        <?php if (!empty($cs['image_path'])): ?>
        <figure class="mb-10 rounded-2xl overflow-hidden border border-gray-200">
            <img src="<?php echo htmlspecialchars(ltrim($cs['image_path'], '/')); ?>" alt="<?php echo htmlspecialchars($cs['title']); ?>" class="w-full h-auto">
        </figure>
        <?php endif; ?>

        <?php if ($cs['quote']): ?>
        <blockquote class="border-l-4 border-[#2fcaf0] pl-6 py-2 mb-10 text-xl text-gray-700 italic">&ldquo;<?php echo htmlspecialchars($cs['quote']); ?>&rdquo;</blockquote>
        <?php endif; ?>

        <?php
        $blocks = [
            'The Challenge' => $cs['challenge'],
            'Our Approach'  => $cs['approach'],
            'Results'       => $cs['results'],
        ];
        foreach ($blocks as $label => $html):
            if (empty(trim(strip_tags($html ?? '')))) continue;
        ?>
        <div class="mb-10">
            <h2 class="text-xl font-bold text-[#173978] mb-3"><?php echo $label; ?></h2>
            <div class="prose prose-slate max-w-none text-gray-600"><?php echo $html; ?></div>
        </div>
        <?php endforeach; ?>

        <?php if (!empty(trim(strip_tags($cs['content'] ?? '')))): ?>
        <div class="mb-10 prose prose-slate max-w-none"><?php echo $cs['content']; ?></div>
        <?php endif; ?>

        <div class="mt-12 p-8 bg-gray-50 rounded-2xl border text-center">
            <h3 class="text-xl font-bold text-[#173978] mb-2">Want similar results for your business?</h3>
            <p class="text-gray-500 mb-4 text-sm">Talk to our team about <?php echo htmlspecialchars($cs['service_line'] ?? 'execution'); ?> solutions.</p>
            <a href="contact" class="inline-flex px-8 py-3 bg-[#173978] text-white font-bold rounded-lg hover:bg-[#2fcaf0] hover:text-[#173978] transition-colors">Get in Touch</a>
        </div>
    </div>
</section>

<?php if ($related): ?>
<section class="py-12 bg-gray-50 border-t">
    <div class="max-w-4xl mx-auto px-4">
        <h2 class="text-xl font-bold text-[#173978] mb-6">More Success Stories</h2>
        <div class="grid sm:grid-cols-3 gap-4">
            <?php foreach ($related as $r): ?>
            <a href="case-studies/<?php echo htmlspecialchars($r['slug']); ?>" class="bg-white rounded-xl border p-4 hover:border-[#2fcaf0] transition-colors">
                <p class="font-bold text-sm text-[#173978] leading-snug"><?php echo htmlspecialchars($r['title']); ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
