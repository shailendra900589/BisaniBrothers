<?php
require 'db.php';
require_once 'includes/blog-helpers.php';

$filterCategory = isset($_GET['category']) ? trim($_GET['category']) : '';
$filterTag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$filterSearch = isset($_GET['search']) ? trim($_GET['search']) : (isset($_GET['q']) ? trim($_GET['q']) : '');

$pageTitle = "Knowledge Hub | Bisani Brothers";
$pageDesc = "FinTech insights, staffing trends, BTL marketing, merchant onboarding, and business growth articles from Bisani Brothers — your knowledge hub for on-ground execution in India.";

if ($filterSearch !== '') {
    $pageTitle = "Search: " . $filterSearch . " | Bisani Brothers Blog";
    $pageDesc = "Blog articles matching " . $filterSearch . " from Bisani Brothers.";
} elseif ($filterCategory !== '') {
    $pageTitle = $filterCategory . " Articles | Bisani Brothers Blog";
    $pageDesc = "Read all " . $filterCategory . " articles and business insights from Bisani Brothers.";
} elseif ($filterTag !== '') {
    $pageTitle = "Articles tagged " . $filterTag . " | Bisani Brothers Blog";
    $pageDesc = "Browse blog posts tagged with " . $filterTag . " from Bisani Brothers.";
}

$posts = blog_fetch_list($pdo, array_filter([
    'category' => $filterCategory !== '' ? $filterCategory : null,
    'tag'      => $filterTag !== '' ? $filterTag : null,
    'search'   => $filterSearch !== '' ? $filterSearch : null,
]));

include 'includes/header.php';
?>

<section class="relative w-full py-24 md:py-32 flex items-center overflow-hidden">
    <div class="absolute inset-0 z-0" style="min-height: 600px;">
        <img src="assets/bg/Blog_page.webp"
             alt="News & Knowledge Hub" 
             class="w-full h-full object-cover object-center">
        <div class="absolute inset-0 bg-black/40"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div class="max-w-3xl w-full bg-[#173978]/50 backdrop-blur-md rounded-[2rem] p-10 md:p-14 text-left text-white shadow-2xl border border-white/10" data-aos="fade-right">
            <h1 class="text-4xl md:text-5xl font-extrabold mb-4 tracking-tight">
                <?php echo page_te('hero_title'); ?>
            </h1>
            <p class="text-blue-100 text-lg md:text-xl font-light leading-relaxed">
                <?php echo page_te('hero_desc'); ?>
            </p>
            <?php if ($filterCategory !== '' || $filterTag !== ''): ?>
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <?php if ($filterCategory !== ''): ?>
                <span class="inline-flex items-center px-4 py-2 rounded-full bg-white/15 border border-white/20 text-sm font-bold">
                    Category: <?php echo htmlspecialchars($filterCategory); ?>
                </span>
                <?php endif; ?>
                <?php if ($filterTag !== ''): ?>
                <span class="inline-flex items-center px-4 py-2 rounded-full bg-white/15 border border-white/20 text-sm font-bold">
                    Tag: #<?php echo htmlspecialchars($filterTag); ?>
                </span>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars(locale_url('blog')); ?>" class="text-sm font-bold text-[#2fcaf0] hover:text-white transition underline underline-offset-4">
                    <?php echo page_te('view_all'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="py-20 bg-gray-50 blog-page">
    <div class="blog-container">
        <div class="blog-list-grid grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php
            if (count($posts) > 0) {
                foreach ($posts as $row) {
                    $date = date("M d, Y", strtotime($row['created_at']));
                    $img = blog_get_safe_path($row['image_path']);
            ?>
            <article class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col h-full group">

                <a href="<?php echo htmlspecialchars(blog_detail_url($row['slug'])); ?>" class="block">
                    <div class="blog-card-thumb">
                        <div class="blog-card-thumb-media">
                            <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='assets/bg/Blog_page.webp';">
                        </div>
                        <span class="blog-card-thumb-badge"><?php echo htmlspecialchars(blog_translate_category($row['category'])); ?></span>
                    </div>
                </a>

                <div class="p-6 flex flex-col flex-1">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs text-gray-400"><?php echo $date; ?></span>
                    </div>

                    <h2 class="text-xl font-bold text-[#173978] mb-3 leading-tight">
                        <a href="<?php echo htmlspecialchars(blog_detail_url($row['slug'])); ?>" class="transition-colors hover:text-[#2fcaf0]">
                            <?php echo htmlspecialchars($row['title']); ?>
                        </a>
                    </h2>

                    <p class="text-gray-600 text-sm line-clamp-3 mb-6 flex-1">
                        <?php
                            if (!empty($row['_listing_excerpt'])) {
                                $excerpt = $row['_listing_excerpt'];
                            } elseif (!empty($row['meta_desc'])) {
                                $excerpt = $row['meta_desc'];
                            } else {
                                $excerpt = blog_excerpt($row['content'] ?? '', 156);
                            }
                            echo htmlspecialchars($excerpt !== '' ? $excerpt : 'Read more to see the details.');
                        ?>
                    </p>

                    <a href="<?php echo htmlspecialchars(blog_detail_url($row['slug'])); ?>" class="text-[#173978] font-bold hover:text-[#2fcaf0] text-sm flex items-center gap-2">
                        <?php echo page_te('read_full'); ?> <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                </div>
            </article>
            <?php 
                }
            } else {
                $emptyMsg = page_t('empty');
                if ($filterCategory !== '' || $filterTag !== '') {
                    $emptyMsg = page_t('no_match');
                }
                echo "<div class='col-span-3 text-center py-12'>
                        <p class='text-gray-500 text-lg mb-4'>" . htmlspecialchars($emptyMsg) . "</p>
                        <a href='" . htmlspecialchars(locale_url('blog')) . "' class='text-[#173978] font-bold hover:text-[#2fcaf0]'>" . page_te('browse_all') . "</a>
                      </div>";
            }
            ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>