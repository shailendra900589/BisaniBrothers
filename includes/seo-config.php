<?php
/**
 * Bisani Brothers — SEO configuration
 */
define('SEO_SITE_NAME', 'Bisani Brothers Pvt. Ltd.');
define('SEO_SITE_DOMAIN', 'www.bisanibrothers.com');
define('SEO_PRODUCTION_URL', 'https://www.bisanibrothers.com');
define('SEO_INDEXNOW_KEY', 'bisanibrothers2026indexnow');
define('SEO_DEFAULT_KEYWORDS', 'Bisani Brothers, sales execution, staffing solutions, market research India, BTL activation, lending collection, business growth, FinTech India, on-ground execution');
define('SEO_TWITTER_HANDLE', '@bisanibrothers');
/** Meta Pixel ID — XOR-encrypted hex (never store plain ID here). Regenerate: php scripts/gen-pixel-enc.php */
define('SEO_META_PIXEL_ENC', 'dc4f2585d030cead98cfe8da079fb1');
define('SEO_GEO_REGION', 'IN-UP');
define('SEO_GEO_PLACENAME', 'Lucknow, Uttar Pradesh, India');

/**
 * Webmaster verification tokens (meta tag method).
 * Google Search Console → Settings → Ownership verification → HTML tag → copy content="..." value.
 * Bing Webmaster Tools → Settings → Verify ownership → Meta tag → copy content value.
 * Leave empty until you have the token; tags render on live domain only when set.
 */
define('SEO_GOOGLE_SITE_VERIFICATION', '');
define('SEO_BING_SITE_VERIFICATION', '');
define('SEO_YANDEX_SITE_VERIFICATION', '');

/** Rolling validThrough window for active JobPosting schema (days from today). */
define('SEO_JOB_VALID_DAYS', 90);

/** Re-ping search engines for active jobs when cron runs (0 = disabled). */
define('SEO_JOB_REFRESH_DAYS', 7);

define('SEO_STATIC_PAGES', [
    '/'                    => ['changefreq' => 'weekly',  'priority' => '1.00'],
    '/about'               => ['changefreq' => 'monthly', 'priority' => '0.90'],
    '/sales-growth'        => ['changefreq' => 'monthly', 'priority' => '0.85'],
    '/survey-market-research' => ['changefreq' => 'monthly', 'priority' => '0.85'],
    '/staffing-solutions'  => ['changefreq' => 'monthly', 'priority' => '0.85'],
    '/btl-atl'             => ['changefreq' => 'monthly', 'priority' => '0.85'],
    '/lending-collection'  => ['changefreq' => 'monthly', 'priority' => '0.85'],
    '/ev-infrastructure'   => ['changefreq' => 'monthly', 'priority' => '0.85'],
    '/partner-with-us'     => ['changefreq' => 'monthly', 'priority' => '0.80'],
    '/growth-partner'      => ['changefreq' => 'monthly', 'priority' => '0.80'],
    '/why-work-with-us'    => ['changefreq' => 'monthly', 'priority' => '0.80'],
    '/contact'             => ['changefreq' => 'monthly', 'priority' => '0.80'],
    '/careers'             => ['changefreq' => 'daily',   'priority' => '0.90'],
    '/blog'                => ['changefreq' => 'daily',   'priority' => '0.85'],
    '/orphan-index'        => ['changefreq' => 'weekly',  'priority' => '0.55'],
    '/jobs-index'          => ['changefreq' => 'daily',   'priority' => '0.85'],
    '/faqs'                => ['changefreq' => 'monthly', 'priority' => '0.75'],
    '/search'              => ['changefreq' => 'monthly', 'priority' => '0.50'],
    '/privacy'             => ['changefreq' => 'yearly',  'priority' => '0.40'],
    '/terms'               => ['changefreq' => 'yearly',  'priority' => '0.40'],
    '/case-studies'        => ['changefreq' => 'weekly',  'priority' => '0.80'],
    '/industries'          => ['changefreq' => 'monthly', 'priority' => '0.80'],
]);

define('SEO_SERVICE_PAGES', [
    'sales-growth',
    'survey-market-research',
    'staffing-solutions',
    'btl-atl',
    'lending-collection',
    'ev-infrastructure',
    'partner-with-us',
    'growth-partner',
    'why-work-with-us',
]);

define('SEO_PAGE_KEYWORDS', [
    'index'                  => 'Bisani Brothers, sales and growth partner India, business solutions Lucknow, customer acquisition, sales targets, merchant onboarding, field execution, staffing solutions, market research India',
    'about'                  => 'about Bisani Brothers, business execution partner India, scalable operations, on-ground teams, Lucknow business solutions, sales staffing company, trusted growth partner',
    'sales-growth'           => 'sales execution India, merchant onboarding, customer acquisition, regional sales growth, field sales teams, B2B sales Lucknow, sales outsourcing India, revenue growth partner',
    'survey-market-research' => 'market research India, survey execution, on-ground data collection, consumer insights, field research, market survey Lucknow, retail audit India, data collection agency',
    'staffing-solutions'     => 'staffing solutions India, bulk hiring, deployment-ready workforce, manpower supply, HR outsourcing, temporary staffing Lucknow, field staff hiring, workforce deployment',
    'btl-atl'                => 'BTL activation India, ATL marketing, brand activation, on-ground promotions, field marketing, experiential marketing, retail activation Lucknow, below the line marketing',
    'lending-collection'     => 'lending sales India, loan collection, NBFC field teams, financial services execution, recovery operations, credit sales Lucknow, collection agency India, loan DSA',
    'ev-infrastructure'      => 'EV infrastructure India, charging station rollout, EV partner onboarding, location identification, field verification, EV charging deployment, green mobility India',
    'partner-with-us'        => 'partner with Bisani Brothers, business partnership India, execution partners, channel partners, field associate program, become a partner Lucknow, BBPL partner network',
    'growth-partner'         => 'growth partner program, flexible work India, BBPL partner, field associate opportunities, part time field jobs, earn with Bisani Brothers, partner income India',
    'why-work-with-us'       => 'why work with Bisani Brothers, trusted execution partner, structured teams, disciplined processes, reliable field operations, business process outsourcing India',
    'contact'                => 'contact Bisani Brothers, business enquiry Lucknow, sales staffing contact, get quote India, BBPL contact, Bisani Brothers phone email, Lucknow office',
    'careers'                => 'Bisani Brothers careers, jobs Lucknow, field sales jobs India, HR jobs, business development jobs, telesales jobs, collection executive jobs, BBPL vacancies',
    'blog'                   => 'Bisani Brothers blog, FinTech insights India, staffing trends, BTL marketing tips, business growth articles, merchant onboarding, workforce India, execution strategy',
    'blog-details'           => SEO_DEFAULT_KEYWORDS,
    'search'                 => 'search Bisani Brothers, find services jobs articles FAQ',
    'faqs'                   => 'Bisani Brothers FAQ, staffing questions, FinTech execution FAQ, careers FAQ India',
    'privacy'                => 'privacy policy Bisani Brothers',
    'terms'                  => 'terms of service Bisani Brothers website',
    'case-studies'           => 'Bisani Brothers case studies, client success stories, FinTech execution results India',
    'industries'             => 'industries served Bisani Brothers FinTech BFSI retail EV',
    'industry'               => SEO_DEFAULT_KEYWORDS,
    '404'                    => SEO_DEFAULT_KEYWORDS,
]);
