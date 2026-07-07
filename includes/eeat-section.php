<?php
/**
 * Sitewide additive E-E-A-T trust block (Experience, Expertise, Authoritativeness, Trustworthiness).
 */

function eeat_page_slug(): string
{
    $slug = page_slug();
    $map = [
        'blog-details'        => 'blog',
        'case-study-details'  => 'case-studies',
        'job-details'         => 'careers',
        'industry'            => 'industries',
    ];

    return $map[$slug] ?? $slug;
}

function eeat_page_t(string $key, ?string $fallback = null): string
{
    locale_init();
    $slug = eeat_page_slug();
    $pages = $GLOBALS['_locale_strings']['page'] ?? [];

    if (isset($pages[$slug][$key]) && is_string($pages[$slug][$key]) && $pages[$slug][$key] !== '') {
        return $pages[$slug][$key];
    }
    if (isset($pages['_common'][$key]) && is_string($pages['_common'][$key]) && $pages['_common'][$key] !== '') {
        return $pages['_common'][$key];
    }

    return $fallback ?? $key;
}

function eeat_page_te(string $key, ?string $fallback = null): string
{
    return htmlspecialchars(eeat_page_t($key, $fallback), ENT_QUOTES, 'UTF-8');
}

function eeat_should_render(): bool
{
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
    if ($script === '404') {
        return false;
    }

    $path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    return !str_contains($path, '/admin/');
}

function eeat_render_section(): void
{
    if (!eeat_should_render()) {
        return;
    }

    if (!function_exists('page_slug')) {
        require_once __DIR__ . '/page-i18n.php';
    }
    locale_init();

    $focus = eeat_page_t('eeat_keywords_p', '');
    if ($focus === 'eeat_keywords_p') {
        $focus = '';
    }

    $servicesUrl = htmlspecialchars(locale_url('sales-growth'), ENT_QUOTES, 'UTF-8');
    $caseStudiesUrl = htmlspecialchars(locale_url('case-studies'), ENT_QUOTES, 'UTF-8');
    $contactUrl = htmlspecialchars(locale_url('contact'), ENT_QUOTES, 'UTF-8');
    ?>
<section class="py-14 sm:py-16 bg-white border-t border-gray-100" id="trust-eeat" aria-labelledby="eeat-heading">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center mb-10 sm:mb-12">
            <h2 id="eeat-heading" class="text-2xl sm:text-3xl font-bold text-[#173978] tracking-tight mb-3">
                <?php echo eeat_page_te('eeat_title'); ?>
            </h2>
            <p class="text-gray-600 text-sm sm:text-base leading-relaxed">
                <?php echo eeat_page_te('eeat_subtitle'); ?>
            </p>
            <?php if ($focus !== ''): ?>
            <p class="mt-4 text-gray-600 text-sm sm:text-base leading-relaxed text-left sm:text-center">
                <?php echo htmlspecialchars($focus, ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
            <article class="rounded-2xl border border-gray-100 bg-[#f4f7fc] p-6 shadow-sm">
                <div class="w-11 h-11 rounded-xl bg-[#173978] text-white flex items-center justify-center mb-4 text-lg" aria-hidden="true">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h3 class="text-lg font-bold text-[#173978] mb-2"><?php echo eeat_page_te('eeat_experience_title'); ?></h3>
                <p class="text-gray-600 text-sm leading-relaxed"><?php echo eeat_page_te('eeat_experience_text'); ?></p>
            </article>

            <article class="rounded-2xl border border-gray-100 bg-[#f4f7fc] p-6 shadow-sm">
                <div class="w-11 h-11 rounded-xl bg-[#173978] text-white flex items-center justify-center mb-4 text-lg" aria-hidden="true">
                    <i class="fa-solid fa-users-gear"></i>
                </div>
                <h3 class="text-lg font-bold text-[#173978] mb-2"><?php echo eeat_page_te('eeat_expertise_title'); ?></h3>
                <p class="text-gray-600 text-sm leading-relaxed"><?php echo eeat_page_te('eeat_expertise_text'); ?></p>
            </article>

            <article class="rounded-2xl border border-gray-100 bg-[#f4f7fc] p-6 shadow-sm">
                <div class="w-11 h-11 rounded-xl bg-[#173978] text-white flex items-center justify-center mb-4 text-lg" aria-hidden="true">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <h3 class="text-lg font-bold text-[#173978] mb-2"><?php echo eeat_page_te('eeat_authority_title'); ?></h3>
                <p class="text-gray-600 text-sm leading-relaxed"><?php echo eeat_page_te('eeat_authority_text'); ?></p>
            </article>

            <article class="rounded-2xl border border-gray-100 bg-[#f4f7fc] p-6 shadow-sm">
                <div class="w-11 h-11 rounded-xl bg-[#173978] text-white flex items-center justify-center mb-4 text-lg" aria-hidden="true">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="text-lg font-bold text-[#173978] mb-2"><?php echo eeat_page_te('eeat_trust_title'); ?></h3>
                <p class="text-gray-600 text-sm leading-relaxed"><?php echo eeat_page_te('eeat_trust_text'); ?></p>
            </article>
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-4 text-sm font-semibold">
            <a href="<?php echo $servicesUrl; ?>" class="text-[#173978] hover:text-[#2fcaf0] transition-colors underline-offset-2 hover:underline">
                <?php echo eeat_page_te('eeat_learn_more'); ?>
            </a>
            <span class="text-gray-300 hidden sm:inline" aria-hidden="true">|</span>
            <a href="<?php echo $caseStudiesUrl; ?>" class="text-[#173978] hover:text-[#2fcaf0] transition-colors underline-offset-2 hover:underline">
                <?php echo eeat_page_te('eeat_case_studies'); ?>
            </a>
            <span class="text-gray-300 hidden sm:inline" aria-hidden="true">|</span>
            <a href="<?php echo $contactUrl; ?>" class="text-[#173978] hover:text-[#2fcaf0] transition-colors underline-offset-2 hover:underline">
                <?php echo eeat_page_te('eeat_contact'); ?>
            </a>
        </div>
    </div>
</section>
    <?php
}
