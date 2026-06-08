<?php
require 'db.php';
require_once 'includes/seo.php';
require_once 'includes/blog-helpers.php';

@set_time_limit(180);

$post = null;
if (isset($_GET['slug'])) {
    $slug = trim(urldecode((string) $_GET['slug']));
    if ($slug !== '' && str_contains($slug, ' ')) {
        $slug = blog_normalize_slug($slug);
    }
    $post = blog_fetch_by_slug($pdo, $slug);
}

if (!$post) {
    http_response_code(404);
    $isArticle404 = true;
    $pageTitle = "Article Not Found | Bisani Brothers";
    $pageDesc = "The article you are looking for does not exist or may have been moved.";
    include '404.php';
    exit();
}

$isOrphanPost = blog_is_orphan($post);

if (!$isOrphanPost) {
    $relatedPosts = blog_fetch_related($pdo, (int) $post['id'], $post['category'] ?? '', 3);
    $sidebarPosts = blog_fetch_sidebar_posts($pdo, (int) $post['id'], $post['category'] ?? null, 6, true);
    $navPosts = blog_fetch_prev_next($pdo, (int) $post['id'], $post['created_at'], true);
    $allPublishedPosts = blog_fetch_linkable_posts($pdo);
} else {
    $relatedPosts = [];
    $sidebarPosts = [];
    $navPosts = ['prev' => null, 'next' => null];
    $allPublishedPosts = [];
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$project_root = str_replace('\\', '/', realpath(__DIR__) ?: __DIR__);
$doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: ($_SERVER['DOCUMENT_ROOT'] ?? ''));
$relative_path = ($doc_root !== '' && str_starts_with($project_root, $doc_root))
    ? substr($project_root, strlen($doc_root))
    : '';
$folder_path = ($relative_path === '' || $relative_path === '/') ? '/' : rtrim($relative_path, '/') . '/';
$base_for_schema = $protocol . '://' . $host . $folder_path;
$articleUrl = blog_post_url($post['slug'], rtrim($base_for_schema, '/'));

$metaTitle = trim($post['meta_title'] ?? '');
$metaDesc = trim($post['meta_desc'] ?? '');
$pageTitle = ($metaTitle !== '' ? $metaTitle : $post['title']) . " | Bisani Brothers";
$pageDesc = $metaDesc !== '' ? $metaDesc : seo_strip_text($post['content'], 160);
$pageKeywords = seo_suggest_blog_keywords($post);
$pageImg = seo_absolute_image($post['image_path'] ?? '', $base_for_schema);
$ogType = 'article';
$articlePublished = date('c', strtotime($post['created_at']));
$articleModified = $articlePublished;
$articleSection = blog_translate_category($post['category'] ?? 'Business Insights');

$tags = blog_parse_tags($post['tags'] ?? '', $post['keywords'] ?? '');
$faqItems = blog_parse_faq($post['faq_json'] ?? '');
$readingMins = blog_reading_time($post['content'] ?? '');
$displayCategory = blog_translate_category($post['category'] ?? '');
$clean_content = blog_clean_content($post['content'] ?? '', $post['title'], $post['image_path'] ?? null);
if (!$isOrphanPost && $allPublishedPosts) {
    $clean_content = blog_inject_internal_links($clean_content, array_slice($allPublishedPosts, 0, 40), $post['slug'], 3);
}
$clean_content = blog_add_heading_ids($clean_content);
$tocItems = blog_build_toc($clean_content);
if ($faqItems) {
    $tocItems[] = ['level' => 2, 'text' => blog_page_t('faq_title'), 'id' => 'blog-faq-section'];
}

$pageSchemas = [
    seo_blog_posting_schema($post, $base_for_schema),
    seo_breadcrumb_schema($isOrphanPost ? [
        ['name' => 'Home', 'url' => rtrim($base_for_schema, '/') . '/'],
        ['name' => $post['title'], 'url' => $articleUrl],
    ] : [
        ['name' => 'Home', 'url' => rtrim($base_for_schema, '/') . '/'],
        ['name' => 'Blog', 'url' => rtrim($base_for_schema, '/') . '/blog'],
        ['name' => $post['title'], 'url' => $articleUrl],
    ]),
];
if ($faqSchema = blog_faq_schema($faqItems, $articleUrl)) {
    $pageSchemas[] = $faqSchema;
}

$shareUrl = urlencode($articleUrl);
$shareTitle = urlencode($post['title']);
$leadText = trim($metaDesc !== '' ? $metaDesc : '');

include 'includes/header.php';
?>

<div class="blog-page">

    <!-- Centered hero (reference style) -->
    <section class="blog-hero blog-hero--centered">
        <div class="blog-container blog-hero-inner">
            <nav class="blog-breadcrumb blog-breadcrumb--center" aria-label="Breadcrumb">
                <a href="<?php echo htmlspecialchars(locale_url('')); ?>"><?php echo htmlspecialchars(t('nav.home')); ?></a>
                <?php if (!$isOrphanPost): ?>
                <i class="fa-solid fa-chevron-right text-[10px] opacity-50"></i>
                <a href="<?php echo htmlspecialchars(locale_url('blog')); ?>"><?php echo htmlspecialchars(t('nav.blog')); ?></a>
                <?php endif; ?>
            </nav>

            <h1 class="blog-hero-title blog-hero-title--center"><?php echo htmlspecialchars($post['title']); ?></h1>

            <div class="blog-meta-row blog-meta-row--center">
                <span class="blog-meta-item"><i class="fa-regular fa-user"></i> Bisani Brothers</span>
                <?php if (!$isOrphanPost): ?>
                <a href="<?php echo blog_esc_attr(blog_filter_url($post['category'])); ?>" class="blog-meta-category" title="<?php echo blog_esc_attr(blog_page_t_vars('view_category_articles', ['category' => $displayCategory])); ?>">
                    <i class="fa-solid fa-layer-group"></i> <?php echo htmlspecialchars($displayCategory); ?>
                </a>
                <?php else: ?>
                <span class="blog-meta-category" style="cursor:default;"><i class="fa-solid fa-layer-group"></i> <?php echo htmlspecialchars($displayCategory); ?></span>
                <?php endif; ?>
                <span class="blog-meta-item"><i class="fa-regular fa-calendar"></i><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                <span class="blog-meta-item"><i class="fa-regular fa-clock"></i><?php echo $readingMins; ?> <?php echo blog_page_te('min_read'); ?></span>
            </div>
        </div>
    </section>

    <!-- Magazine layout: main + sidebar (no sponsored) -->
    <div class="blog-body-wrap">
        <div class="blog-container">
            <div class="blog-magazine<?php echo $isOrphanPost ? ' blog-magazine--orphan' : ''; ?>">

                <!-- Left: featured image -->
                <?php if (!empty($post['image_path'])): ?>
                <figure class="blog-magazine-featured">
                    <img src="<?php echo htmlspecialchars(blog_get_safe_path($post['image_path'])); ?>"
                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                         class="blog-magazine-featured-img"
                         loading="eager"
                         decoding="async">
                </figure>
                <?php endif; ?>

                <!-- Right: useful sidebar (hidden for orphan SEO-only posts) -->
                <?php if (!$isOrphanPost): ?>
                <aside class="blog-magazine-aside" aria-label="Article sidebar">
                    <div class="blog-aside-sticky">
                    <?php if (count($sidebarPosts) > 0): ?>
                    <div class="blog-aside-panel">
                        <h3 class="blog-aside-panel-title"><?php echo blog_page_te('related_articles'); ?></h3>
                        <ul class="blog-aside-links">
                            <?php foreach (array_slice($sidebarPosts, 0, 5) as $sp): ?>
                            <li>
                                <a href="<?php echo blog_esc_attr(blog_detail_url($sp['slug'])); ?>" title="<?php echo blog_esc_attr($sp['title']); ?>">
                                    <?php echo htmlspecialchars($sp['title']); ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?php echo blog_esc_attr(blog_filter_url($post['category'])); ?>" class="blog-aside-viewall">
                            <?php echo blog_page_te_vars('all_category_posts', ['category' => $displayCategory]); ?> <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>

                    <div class="blog-aside-panel">
                        <h3 class="blog-aside-panel-title"><?php echo blog_page_te('share'); ?></h3>
                        <div class="blog-share-row">
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $shareUrl; ?>" target="_blank" rel="noopener noreferrer" class="blog-share-btn blog-share-btn--linkedin" aria-label="Share on LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo $shareUrl; ?>&text=<?php echo $shareTitle; ?>" target="_blank" rel="noopener noreferrer" class="blog-share-btn blog-share-btn--twitter" aria-label="Share on X"><i class="fa-brands fa-twitter"></i></a>
                            <a href="https://wa.me/?text=<?php echo $shareTitle . '%20' . $shareUrl; ?>" target="_blank" rel="noopener noreferrer" class="blog-share-btn blog-share-btn--whatsapp" aria-label="Share on WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                            <a href="mailto:?subject=<?php echo $shareTitle; ?>&body=<?php echo $shareUrl; ?>" class="blog-share-btn blog-share-btn--email" aria-label="Share via Email"><i class="fa-regular fa-envelope"></i></a>
                        </div>
                    </div>

                    <?php if ($tags): ?>
                    <div class="blog-aside-panel">
                        <h3 class="blog-aside-panel-title"><?php echo blog_page_te('tags'); ?></h3>
                        <div class="blog-tags-list">
                            <?php foreach ($tags as $tag): ?>
                            <a href="<?php echo blog_esc_attr(blog_filter_url(null, $tag)); ?>" class="blog-tag blog-tag--link" title="<?php echo blog_esc_attr(blog_page_t_vars('browse_tagged', ['tag' => $tag])); ?>">#<?php echo htmlspecialchars($tag); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <a href="<?php echo htmlspecialchars(locale_url('contact')); ?>" class="blog-aside-cta"><?php echo htmlspecialchars(t('nav.contact')); ?></a>
                    </div>
                </aside>
                <?php endif; ?>

                <!-- Main content column -->
                <div class="blog-magazine-main">

                    <header class="blog-author-card">
                        <div class="blog-author-avatar" aria-hidden="true">B</div>
                        <div class="blog-author-info">
                            <p class="blog-author-label">
                                <?php echo blog_page_te('written_by'); ?>
                                <span class="blog-author-badge"><?php echo blog_page_te('author_badge'); ?></span>
                            </p>
                            <p class="blog-author-name"><?php echo blog_page_te('author_name'); ?></p>
                            <p class="blog-author-bio"><?php echo blog_page_te('author_bio'); ?></p>
                            <div class="blog-author-meta">
                                <span><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                                <span><i class="fa-regular fa-clock"></i> <?php echo $readingMins; ?> <?php echo blog_page_te('min_read'); ?></span>
                            </div>
                        </div>
                    </header>

                    <?php if (count($tocItems) > 0): ?>
                    <nav class="blog-toc" aria-label="Table of contents">
                        <h2 class="blog-toc-title"><i class="fa-solid fa-list-ul"></i> <?php echo blog_page_te('in_this_article'); ?></h2>
                        <ol class="blog-toc-list">
                            <?php foreach ($tocItems as $i => $item): ?>
                            <li class="blog-toc-item blog-toc-item--h<?php echo (int) $item['level']; ?>">
                                <a href="#<?php echo blog_esc_attr($item['id']); ?>"><?php echo ($i + 1) . '. ' . htmlspecialchars($item['text']); ?></a>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                    <?php endif; ?>

                    <?php if ($leadText !== ''): ?>
                    <p class="blog-lead"><?php echo htmlspecialchars($leadText); ?></p>
                    <?php endif; ?>

                    <div class="blog-article blog-article--full">
                        <?php echo $clean_content; ?>
                    </div>

                    <?php if ($faqItems): ?>
                    <section class="blog-faq-section" id="blog-faq-section" aria-labelledby="blog-faq-heading">
                        <div class="blog-faq-head">
                            <div class="blog-faq-icon"><i class="fa-solid fa-circle-question"></i></div>
                            <div>
                                <h2 id="blog-faq-heading" class="blog-faq-title"><?php echo blog_page_te('faq_title'); ?></h2>
                                <p class="blog-faq-sub"><?php echo blog_page_te('faq_sub'); ?></p>
                            </div>
                        </div>
                        <div>
                            <?php foreach ($faqItems as $i => $faq): ?>
                            <details class="faq-item" <?php echo $i === 0 ? 'open' : ''; ?>>
                                <summary>
                                    <span><?php echo htmlspecialchars($faq['question']); ?></span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </summary>
                                <div class="faq-answer"><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></div>
                            </details>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if ($tags): ?>
                    <div class="blog-tags-block">
                        <p class="blog-tags-label"><?php echo blog_page_te('article_tags'); ?></p>
                        <div class="blog-tags-list">
                            <?php foreach ($tags as $tag): ?>
                            <?php if ($isOrphanPost): ?>
                            <span class="blog-tag">#<?php echo htmlspecialchars($tag); ?></span>
                            <?php else: ?>
                            <a href="<?php echo blog_esc_attr(blog_filter_url(null, $tag)); ?>" class="blog-tag blog-tag--link" title="<?php echo blog_esc_attr(blog_page_t_vars('browse_tagged', ['tag' => $tag])); ?>">#<?php echo htmlspecialchars($tag); ?></a>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($navPosts['prev'] || $navPosts['next']): ?>
                    <nav class="blog-post-nav" aria-label="Article navigation">
                        <?php if ($navPosts['prev']): ?>
                        <a href="<?php echo blog_esc_attr(blog_detail_url($navPosts['prev']['slug'])); ?>" class="blog-post-nav-item blog-post-nav-item--prev" title="<?php echo blog_esc_attr($navPosts['prev']['title']); ?>">
                            <span class="blog-post-nav-label"><i class="fa-solid fa-arrow-left"></i> <?php echo blog_page_te('previous'); ?></span>
                            <span class="blog-post-nav-title"><?php echo htmlspecialchars($navPosts['prev']['title']); ?></span>
                        </a>
                        <?php else: ?>
                        <span class="blog-post-nav-item blog-post-nav-item--empty"></span>
                        <?php endif; ?>
                        <?php if ($navPosts['next']): ?>
                        <a href="<?php echo blog_esc_attr(blog_detail_url($navPosts['next']['slug'])); ?>" class="blog-post-nav-item blog-post-nav-item--next" title="<?php echo blog_esc_attr($navPosts['next']['title']); ?>">
                            <span class="blog-post-nav-label"><?php echo blog_page_te('next'); ?> <i class="fa-solid fa-arrow-right"></i></span>
                            <span class="blog-post-nav-title"><?php echo htmlspecialchars($navPosts['next']['title']); ?></span>
                        </a>
                        <?php endif; ?>
                    </nav>
                    <?php endif; ?>

                    <div class="blog-actions">
                        <?php if ($isOrphanPost): ?>
                        <a href="<?php echo htmlspecialchars(locale_url('')); ?>" class="blog-btn blog-btn--outline"><i class="fa-solid fa-arrow-left mr-2 text-[#2fcaf0]"></i> <?php echo htmlspecialchars(t('nav.home')); ?></a>
                        <?php else: ?>
                        <a href="<?php echo htmlspecialchars(locale_url('blog')); ?>" class="blog-btn blog-btn--outline"><i class="fa-solid fa-arrow-left mr-2 text-[#2fcaf0]"></i> <?php echo blog_page_te('view_all'); ?></a>
                        <?php endif; ?>
                        <a href="<?php echo htmlspecialchars(locale_url('contact')); ?>" class="blog-btn blog-btn--primary"><?php echo htmlspecialchars(t('nav.contact')); ?> <i class="fa-solid fa-arrow-right ml-2"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (count($relatedPosts) > 0): ?>
    <section class="blog-related">
        <div class="blog-container">
            <div class="blog-related-head">
                <div>
                    <h2 class="blog-related-title"><?php echo blog_page_te('related_articles'); ?></h2>
                    <p class="text-slate-500 mt-1 text-sm"><?php echo blog_page_te('more_from'); ?> <a href="<?php echo blog_esc_attr(blog_filter_url($post['category'])); ?>" class="text-[#173978] font-semibold hover:text-[#2fcaf0] transition"><?php echo htmlspecialchars($displayCategory); ?></a></p>
                </div>
                <a href="<?php echo htmlspecialchars(locale_url('blog')); ?>" class="blog-btn blog-btn--outline"><?php echo blog_page_te('view_all'); ?> <i class="fa-solid fa-arrow-right ml-2"></i></a>
            </div>
            <div class="blog-related-grid">
                <?php foreach ($relatedPosts as $related):
                    if (!empty($related['_listing_excerpt'])) {
                        $excerpt = $related['_listing_excerpt'];
                    } elseif (!empty($related['meta_desc'])) {
                        $excerpt = $related['meta_desc'];
                    } else {
                        $excerpt = blog_excerpt($related['content'] ?? '', 120);
                    }
                    if ($excerpt === '') {
                        $excerpt = blog_page_t_vars('read_more_about', ['title' => $related['title']]);
                    }
                ?>
                <a href="<?php echo blog_esc_attr(blog_detail_url($related['slug'])); ?>" title="<?php echo blog_esc_attr($related['title']); ?>" class="group block h-full">
                    <article class="bg-white rounded-2xl overflow-hidden border border-slate-200 shadow-sm hover:shadow-xl hover:border-[#2fcaf0] transition-all duration-300 h-full flex flex-col hover:-translate-y-1">
                        <div class="blog-card-thumb">
                            <div class="blog-card-thumb-media">
                            <?php if (!empty($related['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars(blog_get_safe_path($related['image_path'])); ?>" alt="<?php echo htmlspecialchars($related['title']); ?>" loading="lazy">
                            <?php else: ?>
                            <div class="blog-card-thumb-placeholder"><i class="fa-regular fa-image text-4xl"></i></div>
                            <?php endif; ?>
                            </div>
                            <span class="blog-card-thumb-badge"><?php echo htmlspecialchars(blog_translate_category($related['category'] ?? '')); ?></span>
                        </div>
                        <div class="p-5 flex flex-col flex-grow">
                            <time class="text-xs text-slate-400 font-semibold uppercase tracking-wider"><?php echo date('M d, Y', strtotime($related['created_at'])); ?></time>
                            <h3 class="text-base font-extrabold text-[#173978] group-hover:text-[#2fcaf0] transition mt-2 mb-2 line-clamp-2 leading-snug"><?php echo htmlspecialchars($related['title']); ?></h3>
                            <p class="text-slate-600 text-sm flex-1 line-clamp-3 leading-relaxed"><?php echo htmlspecialchars($excerpt); ?></p>
                            <span class="mt-4 pt-3 border-t border-slate-100 text-[#173978] font-bold text-sm group-hover:text-[#2fcaf0] transition inline-flex items-center"><?php echo blog_page_te('read_article'); ?> <i class="fa-solid fa-arrow-right ml-2 text-xs group-hover:translate-x-1 transition-transform"></i></span>
                        </div>
                    </article>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
