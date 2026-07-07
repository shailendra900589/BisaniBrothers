<?php
// ==========================================
//  MANUAL BASE URL CONFIGURATION (FIXED)
// ==========================================
require_once __DIR__ . '/seo-config.php';
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/assets.php';
require_once __DIR__ . '/locale.php';
require_once __DIR__ . '/meta-pixel.php';
require_once __DIR__ . '/security.php';
security_send_headers();

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];

// 1. Auto-detect project folder (works on local XAMPP subfolder and live root)
$project_root = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__));
$doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: ($_SERVER['DOCUMENT_ROOT'] ?? ''));
$relative_path = ($doc_root !== '' && str_starts_with($project_root, $doc_root))
    ? substr($project_root, strlen($doc_root))
    : '';
$folder_path = ($relative_path === '' || $relative_path === '/') ? '/' : rtrim($relative_path, '/') . '/';

// 2. Final Base URL
$base_url = $protocol . "://" . $host . $folder_path . "";

// ==========================================
//  DYNAMIC SEO VARIABLES & CURRENT URL
// ==========================================
$scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
$canonical_url = seo_build_canonical_url($base_url);

$pageLocaleMeta = locale_page_meta($scriptName);
if ($pageLocaleMeta) {
    if (!isset($pageTitle) && !empty($pageLocaleMeta['title'])) {
        $pageTitle = $pageLocaleMeta['title'];
    }
    if (!isset($pageDesc) && !empty($pageLocaleMeta['desc'])) {
        $pageDesc = $pageLocaleMeta['desc'];
    }
}

// Default SEO values if not set on individual pages
if (!isset($pageTitle)) { $pageTitle = "Offline Service Provider & Sales Execution Company | Bisani Brothers"; }
if (!isset($pageDesc))  { $pageDesc = SEO_HOMEPAGE_META_DESC; }
if (!isset($pageImg))   { $pageImg = seo_absolute_image(null, $base_url); }
if (!isset($robotsMeta)) { $robotsMeta = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1'; }
if (!isset($ogType)) { $ogType = 'website'; }
if (!isset($pageSchemas)) { $pageSchemas = []; }

if (empty($pageSchemas) && $scriptName !== '404' && $scriptName !== 'blog-details') {
    if ($scriptName === 'about') {
        $pageSchemas[] = seo_about_page_schema($pageTitle, $pageDesc, $canonical_url);
    } elseif ($scriptName === 'contact') {
        $pageSchemas[] = seo_contact_page_schema($pageTitle, $pageDesc, $canonical_url);
    } else {
        $pageSchemas[] = seo_webpage_schema($pageTitle, $pageDesc, $canonical_url);
    }
    if (in_array($scriptName, SEO_SERVICE_PAGES, true)) {
        $serviceType = SEO_SERVICE_TYPES[$scriptName] ?? null;
        $pageSchemas[] = seo_service_schema($pageTitle, $pageDesc, $canonical_url, $serviceType);
    }
}
if (!empty($appendPageSchemas) && is_array($appendPageSchemas)) {
    foreach ($appendPageSchemas as $schemaBlock) {
        if (is_array($schemaBlock) && $schemaBlock !== []) {
            $pageSchemas[] = $schemaBlock;
        }
    }
}
if ($scriptName === 'index') {
    $pageSchemas[] = seo_local_business_schema($base_url);
}

$orgSchema = seo_organization_schema($base_url);
$websiteSchema = seo_website_schema($base_url);

// ==========================================
//  LOGO & NAVBAR SETTINGS
// ==========================================
$logo_height = "h-12"; 
$logo_width = "w-auto";
$navbar_height = "h-16"; 
// ==========================================
?>
<!DOCTYPE html>
<html lang="<?php echo locale_html_lang(); ?>"<?php echo locale_is_rtl() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

<?php foreach (seo_verification_meta_tags() as $verifyTag): ?>
    <meta name="<?php echo seo_escape($verifyTag['name']); ?>" content="<?php echo seo_escape($verifyTag['content']); ?>">
<?php endforeach; ?>
    
    <title><?php echo seo_escape($pageTitle); ?></title>
    <meta name="title" content="<?php echo seo_escape($pageTitle); ?>">
    <meta name="description" content="<?php echo seo_escape($pageDesc); ?>">
    <meta name="author" content="Bisani Brothers Pvt. Ltd.">
    <meta name="robots" content="<?php echo seo_escape($robotsMeta); ?>">
    <meta name="googlebot" content="<?php echo seo_escape($robotsMeta); ?>">
    <meta name="bingbot" content="<?php echo seo_escape($robotsMeta); ?>">
    <meta name="language" content="English">
    <meta name="geo.region" content="<?php echo SEO_GEO_REGION; ?>">
    <meta name="geo.placename" content="<?php echo SEO_GEO_PLACENAME; ?>">
    <meta name="rating" content="general">
    <meta name="referrer" content="strict-origin-when-cross-origin">

    <base href="<?php echo $base_url; ?>">

    <link rel="canonical" href="<?php echo seo_escape($canonical_url); ?>">
<?php foreach (locale_hreflang_alternates(rtrim($base_url, '/')) as $alt): ?>
    <link rel="alternate" hreflang="<?php echo seo_escape($alt['hreflang']); ?>" href="<?php echo seo_escape($alt['href']); ?>">
<?php endforeach; ?>
    <link rel="alternate" type="application/rss+xml" title="<?php echo seo_escape(SEO_SITE_NAME); ?> RSS Feed" href="<?php echo seo_escape(rtrim($base_url, '/') . '/rss.xml'); ?>">
    <link rel="search" type="application/opensearchdescription+xml" title="<?php echo seo_escape(SEO_SITE_NAME); ?>" href="<?php echo seo_escape(rtrim($base_url, '/') . '/opensearch.xml'); ?>">
    <link rel="alternate" type="text/plain" title="LLMs.txt" href="<?php echo seo_escape(rtrim($base_url, '/') . '/llms.txt'); ?>">

    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon/apple-touch-icon.png">
    <link rel="manifest" href="assets/favicon/site.webmanifest">
    <link rel="shortcut icon" href="assets/favicon/favicon.ico">

    <meta property="og:type" content="<?php echo seo_escape($ogType); ?>">
    <meta property="og:url" content="<?php echo seo_escape($canonical_url); ?>">
    <meta property="og:title" content="<?php echo seo_escape($pageTitle); ?>">
    <meta property="og:description" content="<?php echo seo_escape($pageDesc); ?>">
    <meta property="og:image" content="<?php echo seo_escape($pageImg); ?>">
    <meta property="og:image:alt" content="<?php echo seo_escape($pageTitle); ?>">
    <meta property="og:site_name" content="Bisani Brothers">
    <meta property="og:locale" content="<?php echo locale_og_locale(); ?>">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="<?php echo SEO_TWITTER_HANDLE; ?>">
    <meta name="twitter:url" content="<?php echo seo_escape($canonical_url); ?>">
    <meta name="twitter:title" content="<?php echo seo_escape($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo seo_escape($pageDesc); ?>">
    <meta name="twitter:image" content="<?php echo seo_escape($pageImg); ?>">
    <meta name="twitter:image:alt" content="<?php echo seo_escape($pageTitle); ?>">

<?php if ($ogType === 'article' && !empty($articlePublished)): ?>
    <meta property="article:published_time" content="<?php echo seo_escape($articlePublished); ?>">
    <meta property="article:modified_time" content="<?php echo seo_escape($articleModified ?? $articlePublished); ?>">
    <meta property="article:author" content="Bisani Brothers Editorial Team">
<?php if (!empty($articleSection)): ?>
    <meta property="article:section" content="<?php echo seo_escape($articleSection); ?>">
<?php endif; ?>
<?php endif; ?>

    <script type="application/ld+json"><?php echo seo_output_json_ld($orgSchema); ?></script>
    <script type="application/ld+json"><?php echo seo_output_json_ld($websiteSchema); ?></script>
<?php foreach ($pageSchemas as $schemaBlock): ?>
    <script type="application/ld+json"><?php echo seo_output_json_ld($schemaBlock); ?></script>
<?php endforeach; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Mulish:wght@400;600;700;800&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Mulish:wght@400;600;700;800&display=swap" rel="stylesheet"></noscript>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>

<?php if ($scriptName === 'index'): ?>
    <link rel="preload" as="image" href="assets/images/bg_image.webp" fetchpriority="high">
<?php endif; ?>
    <link rel="stylesheet" href="<?php echo bb_stylesheet('tailwind.css'); ?>">
    <link rel="stylesheet" href="<?php echo bb_stylesheet('styles.css'); ?>">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" media="print" onload="this.media='all'">
    <noscript><link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet"></noscript>
<?php if (in_array($scriptName, ['blog', 'blog-details'], true)): ?>
    <link rel="stylesheet" href="<?php echo bb_stylesheet('blog.css'); ?>">
<?php endif; ?>

    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-HXR4B7F4YT');
    gtag('set', 'user_properties', { site_language: '<?php echo locale_current(); ?>' });
    </script>
    <script defer src="https://www.googletagmanager.com/gtag/js?id=G-HXR4B7F4YT"></script>

<?php if (meta_pixel_active() && !in_array($scriptName, ['blog', 'blog-details'], true)): ?>
    <script async src="<?php echo seo_escape(meta_pixel_script_url($base_url)); ?>"></script>
<?php endif; ?>
	
</head>

<body class="font-sans text-gray-800 antialiased bg-gray-50 <?php echo in_array($scriptName, ['blog', 'blog-details'], true) ? 'blog-layout' : 'overflow-x-hidden'; ?>">
<?php if (meta_pixel_active()): ?>
    <noscript><img height="1" width="1" class="bb-pixel-hidden" alt="" src="<?php echo seo_escape(meta_pixel_beacon_url($base_url)); ?>" /></noscript>
<?php endif; ?>

    <header id="main-header" class="fixed w-full z-50 top-0 transition-transform duration-300 ease-in-out bg-white shadow-sm">
        
        <div id="top-bar" class="hidden md:flex w-full border-b border-white/10 relative z-50">
            <div class="marquee-container flex-1 min-w-0">
                <div class="marquee-track">
                    <span class="marquee-item"><?php echo htmlspecialchars(t('nav.marquee')); ?></span>
                    <span class="marquee-item text-[#2fcaf0]">&bull;</span>
                    <span class="marquee-item"><?php echo htmlspecialchars(t('nav.marquee')); ?></span>
                    <span class="marquee-item text-[#2fcaf0]">&bull;</span>
                    <span class="marquee-item"><?php echo htmlspecialchars(t('nav.marquee')); ?></span>
                    <span class="marquee-item text-[#2fcaf0]">&bull;</span>
                    <span class="marquee-item"><?php echo htmlspecialchars(t('nav.marquee')); ?></span>
                    <span class="marquee-item text-[#2fcaf0]">&bull;</span>
                    <span class="marquee-item"><?php echo htmlspecialchars(t('nav.marquee')); ?></span>
                    <span class="marquee-item text-[#2fcaf0]">&bull;</span>
                    <span class="marquee-item"><?php echo htmlspecialchars(t('nav.marquee')); ?></span>
                    <span class="marquee-item text-[#2fcaf0]">&bull;</span>
                </div>
            </div>
            <div class="top-bar-social" aria-label="<?php echo htmlspecialchars(t('footer.follow_us', 'Follow us')); ?>">
                <a href="https://www.linkedin.com/company/bisani-brothers" target="_blank" rel="noopener noreferrer" class="top-bar-social-link" aria-label="LinkedIn">
                    <i class="fa-brands fa-linkedin-in"></i>
                </a>
                <a href="https://www.facebook.com/profile.php?id=61582749106777" target="_blank" rel="noopener noreferrer" class="top-bar-social-link" aria-label="Facebook">
                    <i class="fa-brands fa-facebook-f"></i>
                </a>
                <a href="https://www.instagram.com/bisanibrothers/" target="_blank" rel="noopener noreferrer" class="top-bar-social-link" aria-label="Instagram">
                    <i class="fa-brands fa-instagram"></i>
                </a>
                <a href="<?php echo seo_escape(SEO_YOUTUBE_CHANNEL); ?>" target="_blank" rel="noopener noreferrer" class="top-bar-social-link" aria-label="YouTube">
                    <i class="fa-brands fa-youtube"></i>
                </a>
            </div>
        </div>

        <nav class="bg-white w-full <?php echo $navbar_height; ?> flex items-center relative z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                <div class="flex justify-between items-center">
                    
                    <div class="flex-shrink-0 flex items-center py-2">
                        <a href="<?php echo htmlspecialchars(locale_url('')); ?>" class="flex items-center">
                            <div class="w-16 h-16 rounded-full bg-white shadow-md border border-gray-100 flex items-center justify-center overflow-hidden hover:scale-105 transition-transform duration-300">
                                <img src="assets/images/logos.png" 
                                     alt="Bisani Brothers Logo" 
                                     class="w-17 h-17 object-contain"> 
                            </div>
                        </a>
                    </div>

                    <div class="hidden xl:flex items-center gap-4">
                        <a href="<?php echo htmlspecialchars(locale_url('')); ?>" class="nav-link text-[#173978]"><?php echo htmlspecialchars(t('nav.home')); ?></a>
                        <a href="<?php echo htmlspecialchars(locale_url('about')); ?>" class="nav-link"><?php echo htmlspecialchars(t('nav.about')); ?></a>
                        
                        <div class="relative group h-full flex items-center">
                            <button class="nav-link flex items-center gap-1 focus:outline-none">
                                <?php echo htmlspecialchars(t('nav.services')); ?>
                                <i class="fa-solid fa-chevron-down text-xs transition-transform group-hover:rotate-180 text-gray-400 group-hover:text-[#173978]"></i>
                            </button>
                            
                            <div class="absolute left-0 top-full w-64 pt-4 hidden group-hover:block dropdown-menu">
                                <div class="bg-white shadow-xl py-2 rounded-b-lg border-t-4 border-[#173978]">
                                    <a href="<?php echo htmlspecialchars(locale_url('sales-growth')); ?>" class="block px-5 py-3 text-sm font-semibold text-gray-700 hover:text-[#173978] hover:bg-gray-50 transition-all"><?php echo htmlspecialchars(t('services.sales')); ?></a>
                                    <a href="<?php echo htmlspecialchars(locale_url('survey-market-research')); ?>" class="block px-5 py-3 text-sm font-semibold text-gray-700 hover:text-[#173978] hover:bg-gray-50 transition-all"><?php echo htmlspecialchars(t('services.survey')); ?></a>
                                    <a href="<?php echo htmlspecialchars(locale_url('staffing-solutions')); ?>" class="block px-5 py-3 text-sm font-semibold text-gray-700 hover:text-[#173978] hover:bg-gray-50 transition-all"><?php echo htmlspecialchars(t('services.staffing')); ?></a>
                                    <a href="<?php echo htmlspecialchars(locale_url('btl-atl')); ?>" class="block px-5 py-3 text-sm font-semibold text-gray-700 hover:text-[#173978] hover:bg-gray-50 transition-all"><?php echo htmlspecialchars(t('services.btl')); ?></a>
                                    <a href="<?php echo htmlspecialchars(locale_url('lending-collection')); ?>" class="block px-5 py-3 text-sm font-semibold text-gray-700 hover:text-[#173978] hover:bg-gray-50 transition-all"><?php echo htmlspecialchars(t('services.lending')); ?></a>
									<a href="<?php echo htmlspecialchars(locale_url('ev-infrastructure')); ?>" class="block px-5 py-3 text-sm font-semibold text-gray-700 hover:text-[#173978] hover:bg-gray-50 transition-all"><?php echo htmlspecialchars(t('services.ev')); ?></a>
                                </div>
                            </div>
                        </div>

                        <a href="<?php echo htmlspecialchars(locale_url('partner-with-us')); ?>" class="nav-link"><?php echo htmlspecialchars(t('nav.partner')); ?></a>
                        <a href="<?php echo htmlspecialchars(locale_url('why-work-with-us')); ?>" class="nav-link"><?php echo htmlspecialchars(t('nav.why_us')); ?></a>
                        <a href="<?php echo htmlspecialchars(locale_url('contact')); ?>" class="nav-link"><?php echo htmlspecialchars(t('nav.contact')); ?></a>

                        <form action="<?php echo htmlspecialchars(locale_url('search')); ?>" method="get" class="relative shrink-0" role="search">
                            <input type="search" name="q" placeholder="<?php echo htmlspecialchars(t('nav.search')); ?>" class="w-32 focus:w-40 transition-all pl-3 pr-7 py-1 text-xs border border-gray-200 rounded-full focus:outline-none focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0]" aria-label="<?php echo htmlspecialchars(t('nav.search')); ?>">
                            <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#173978]"><i class="fa-solid fa-magnifying-glass text-[10px]"></i></button>
                        </form>
                    </div>

                    <div class="flex items-center gap-1.5 xl:hidden">
                        <button type="button" onclick="toggleMenu()" class="text-[#173978] hover:text-[#2fcaf0] focus:outline-none p-2">
                            <i class="fa-solid fa-bars text-2xl"></i>
                        </button>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <div id="mobile-menu" class="fixed left-0 w-full bg-white shadow-xl z-30 hidden xl:hidden border-t border-gray-100 overflow-y-auto max-h-[80vh]">
        <div class="px-5 py-6 space-y-3">
            <a href="<?php echo htmlspecialchars(locale_url('')); ?>" class="block px-4 py-3 rounded-lg font-bold text-[#173978] bg-blue-50"><?php echo htmlspecialchars(t('nav.home')); ?></a>
            <a href="<?php echo htmlspecialchars(locale_url('about')); ?>" class="block px-4 py-3 rounded-lg font-bold text-gray-700 hover:bg-gray-50 hover:text-[#173978]"><?php echo htmlspecialchars(t('nav.about')); ?></a>
            
            <div class="px-4 py-2">
                <span class="block font-bold text-gray-400 text-xs uppercase mb-2"><?php echo htmlspecialchars(t('nav.services')); ?></span>
                <a href="<?php echo htmlspecialchars(locale_url('sales-growth')); ?>" class="block py-2 text-sm font-semibold text-gray-600 hover:text-[#173978]"><?php echo htmlspecialchars(t('services.sales_short')); ?></a>
                <a href="<?php echo htmlspecialchars(locale_url('lending-collection')); ?>" class="block py-2 text-sm font-semibold text-gray-600 hover:text-[#173978]"><?php echo htmlspecialchars(t('services.lending')); ?></a>
                <a href="<?php echo htmlspecialchars(locale_url('staffing-solutions')); ?>" class="block py-2 text-sm font-semibold text-gray-600 hover:text-[#173978]"><?php echo htmlspecialchars(t('services.staffing')); ?></a>
                <a href="<?php echo htmlspecialchars(locale_url('btl-atl')); ?>" class="block py-2 text-sm font-semibold text-gray-600 hover:text-[#173978]"><?php echo htmlspecialchars(t('services.btl')); ?></a>
            </div>

            <a href="<?php echo htmlspecialchars(locale_url('growth-partner')); ?>" class="block px-4 py-3 rounded-lg font-bold text-gray-700 hover:bg-gray-50 hover:text-[#173978]"><?php echo htmlspecialchars(t('nav.growth_partner')); ?></a>
            <a href="<?php echo htmlspecialchars(locale_url('faqs')); ?>" class="block px-4 py-3 rounded-lg font-bold text-gray-700 hover:bg-gray-50 hover:text-[#173978]"><?php echo htmlspecialchars(t('nav.faqs')); ?></a>
            
            <a href="<?php echo htmlspecialchars(locale_url('why-work-with-us')); ?>" class="block px-4 py-3 rounded-lg font-bold text-gray-700 hover:bg-gray-50 hover:text-[#173978]"><?php echo htmlspecialchars(t('nav.why_us')); ?></a>

            <form action="<?php echo htmlspecialchars(locale_url('search')); ?>" method="get" class="px-4 pt-2">
                <input type="search" name="q" placeholder="<?php echo htmlspecialchars(t('nav.search_mobile')); ?>" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm" required minlength="2">
            </form>

            <div class="pt-4 border-t border-gray-100">
                <a href="<?php echo htmlspecialchars(locale_url('contact')); ?>" class="block w-full text-center py-3 bg-[#173978] text-white font-bold rounded shadow-md"><?php echo htmlspecialchars(t('nav.contact')); ?></a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const header = document.getElementById('main-header');
            const topBar = document.getElementById('top-bar');
            const mobileMenu = document.getElementById('mobile-menu');
            const body = document.body; 
            
            function setBodyPadding() {
                const headerHeight = header.offsetHeight;
                body.style.paddingTop = headerHeight + 'px';
            }

            function updateHeader() {
                const currentScrollY = window.scrollY;
                const topBarHeight = (topBar && topBar.offsetParent !== null) ? topBar.offsetHeight : 0;
                const navHeight = 80; 

                if (currentScrollY > 20) {
                    if(topBarHeight > 0) {
                        header.style.transform = `translateY(-${topBarHeight}px)`;
                    }
                    if(mobileMenu) mobileMenu.style.top = navHeight + 'px';
                } else {
                    header.style.transform = 'translateY(0)';
                    if(mobileMenu) mobileMenu.style.top = (navHeight + topBarHeight) + 'px';
                }
            }

            setBodyPadding();

            window.addEventListener('scroll', updateHeader);
            window.addEventListener('resize', function() {
                updateHeader();
                setBodyPadding(); 
            });
            
            updateHeader();
        });

        function toggleMenu() {
            const menu = document.getElementById('mobile-menu');
            if(menu) menu.classList.toggle('hidden');
        }
    </script>