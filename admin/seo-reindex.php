<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin']);

require '../db.php';
require_once __DIR__ . '/../includes/assets.php';
require_once __DIR__ . '/../includes/seo.php';
require_once __DIR__ . '/../includes/job-helpers.php';

$result = null;
$jobRefresh = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_reindex'])) {
    $result = seo_run_bulk_reindex($pdo);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh_jobs'])) {
    $jobRefresh = job_refresh_seo_signals($pdo);
}

$lastJobRefresh = job_seo_last_refresh();
$sitemapUrl = seo_site_url_rtrim() . '/sitemap.xml';
$gscVerified = SEO_GOOGLE_SITE_VERIFICATION !== '';
$bingVerified = SEO_BING_SITE_VERIFICATION !== '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SEO Reindex | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden">
<?php include 'includes/sidebar.php'; ?>
<main class="flex-1 overflow-y-auto">
    <header class="bg-white border-b px-8 h-20 flex items-center sticky top-0 z-10">
        <h2 class="text-2xl font-bold text-[#173978]">Search Engine &amp; Job SEO</h2>
    </header>
    <div class="p-8 max-w-4xl mx-auto space-y-8">

        <?php if ($result): ?>
        <div class="p-5 bg-green-50 border border-green-200 rounded-2xl text-green-800">
            <p class="font-bold flex items-center gap-2 mb-2"><i class="fa-solid fa-circle-check"></i> Full reindex complete</p>
            <ul class="text-sm space-y-1 list-disc list-inside">
                <?php foreach ($result['messages'] as $line): ?>
                <li><?php echo htmlspecialchars($line); ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="mt-3 text-sm font-bold text-[#173978]"><?php echo (int) $result['url_count']; ?> URLs submitted to IndexNow</p>
        </div>
        <?php endif; ?>

        <?php if ($jobRefresh): ?>
        <div class="p-5 bg-green-50 border border-green-200 rounded-2xl text-green-800">
            <p class="font-bold flex items-center gap-2 mb-2"><i class="fa-solid fa-briefcase"></i> Job listings refreshed</p>
            <p class="text-sm"><?php echo (int) $jobRefresh['job_count']; ?> active job(s) · <?php echo (int) $jobRefresh['url_count']; ?> URLs pinged · <?php echo htmlspecialchars($jobRefresh['refreshed_at']); ?></p>
        </div>
        <?php endif; ?>

        <!-- Job auto-refresh -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8">
            <div class="flex items-start gap-4 mb-6">
                <div class="w-14 h-14 bg-[#2fcaf0]/15 text-[#173978] rounded-2xl flex items-center justify-center shrink-0 text-2xl">
                    <i class="fa-solid fa-briefcase"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-[#173978]">Job listing auto-refresh</h3>
                    <p class="text-sm text-slate-500 mt-1 leading-relaxed">
                        Active jobs use a rolling <strong><?php echo (int) SEO_JOB_VALID_DAYS; ?>-day</strong> expiry in Google Jobs schema
                        (<code class="text-xs bg-slate-100 px-1 rounded">validThrough</code>) — refreshed on every page load.
                        Search engines are re-pinged every <strong><?php echo (int) SEO_JOB_REFRESH_DAYS; ?> days</strong> via cron.
                    </p>
                </div>
            </div>

            <ul class="text-sm text-slate-600 space-y-2 mb-6">
                <li><i class="fa-solid fa-check text-[#2fcaf0] mr-2"></i>Auto-runs when you add, edit, or delete a job in Admin</li>
                <li><i class="fa-solid fa-check text-[#2fcaf0] mr-2"></i>All active jobs in <code class="text-xs bg-slate-100 px-1 rounded">sitemap.xml</code> and <code class="text-xs bg-slate-100 px-1 rounded">/jobs-index</code></li>
                <li><i class="fa-solid fa-check text-[#2fcaf0] mr-2"></i>JobPosting JSON-LD on careers + each job detail page</li>
            </ul>

            <?php if ($lastJobRefresh): ?>
            <p class="text-xs text-slate-400 mb-4">Last job SEO ping: <strong class="text-slate-600"><?php echo htmlspecialchars($lastJobRefresh['refreshed_at'] ?? '—'); ?></strong>
                · <?php echo (int) ($lastJobRefresh['job_count'] ?? 0); ?> job(s)</p>
            <?php endif; ?>

            <form method="POST" class="inline" onsubmit="return confirm('Ping all active job URLs to search engines now?');">
                <?php echo security_csrf_field(); ?>
                <input type="hidden" name="refresh_jobs" value="1">
                <button type="submit" class="bg-[#173978] text-white px-6 py-3 rounded-xl font-bold hover:bg-[#2fcaf0] hover:text-[#173978] transition-all text-sm">
                    <i class="fa-solid fa-rotate mr-2"></i> Refresh Job Listings Now
                </button>
            </form>

            <div class="mt-6 p-4 bg-slate-50 rounded-xl border border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Weekly cron (recommended)</p>
                <code class="text-xs text-slate-700 break-all block">php <?php echo htmlspecialchars(realpath(dirname(__DIR__) . '/scripts/refresh-job-seo.php') ?: 'scripts/refresh-job-seo.php'); ?></code>
                <p class="text-[11px] text-slate-400 mt-2">Add to cPanel cron → once per week. Use <code class="bg-white px-1 rounded">--force</code> to skip the 7-day interval.</p>
            </div>
        </div>

        <!-- Google Search Console -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8">
            <div class="flex items-start gap-4 mb-6">
                <div class="w-14 h-14 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center shrink-0 text-2xl">
                    <i class="fa-brands fa-google"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-[#173978]">Google Search Console setup</h3>
                    <p class="text-sm text-slate-500 mt-1">One-time setup so Google indexes jobs, blogs, and all pages.</p>
                </div>
            </div>

            <ol class="text-sm text-slate-700 space-y-4 list-decimal list-inside mb-6">
                <li class="leading-relaxed">Open <a href="https://search.google.com/search-console" target="_blank" rel="noopener" class="text-[#173978] font-semibold underline">Google Search Console</a> → add property <strong>https://www.bisanibrothers.com</strong></li>
                <li class="leading-relaxed">Choose <strong>HTML tag</strong> verification → copy the <code class="text-xs bg-slate-100 px-1 rounded">content="..."</code> value only</li>
                <li class="leading-relaxed">Paste it in <code class="text-xs bg-slate-100 px-1 rounded">includes/seo-config.php</code> → <code class="text-xs bg-slate-100 px-1 rounded">SEO_GOOGLE_SITE_VERIFICATION</code></li>
                <li class="leading-relaxed">Deploy / upload → click <strong>Verify</strong> in Search Console</li>
                <li class="leading-relaxed">Go to <strong>Sitemaps</strong> → submit: <a href="<?php echo htmlspecialchars($sitemapUrl); ?>" target="_blank" class="text-[#173978] font-semibold underline break-all"><?php echo htmlspecialchars($sitemapUrl); ?></a></li>
                <li class="leading-relaxed">Optional: <strong>URL Inspection</strong> → test any job URL e.g. <code class="text-xs bg-slate-100 px-1 rounded">/jobs/your-job-slug</code> → Request Indexing</li>
            </ol>

            <div class="flex items-center gap-2 text-sm <?php echo $gscVerified ? 'text-green-700' : 'text-amber-700'; ?>">
                <i class="fa-solid <?php echo $gscVerified ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
                <?php if ($gscVerified): ?>
                Google verification token is configured.
                <?php else: ?>
                Google verification token <strong>not set yet</strong> — add it in seo-config.php after step 2.
                <?php endif; ?>
            </div>
        </div>

        <!-- Bing Webmaster -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8">
            <div class="flex items-start gap-4 mb-4">
                <div class="w-14 h-14 bg-teal-50 text-teal-700 rounded-2xl flex items-center justify-center shrink-0 text-2xl">
                    <i class="fa-brands fa-microsoft"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-[#173978]">Bing Webmaster Tools</h3>
                    <p class="text-sm text-slate-500 mt-1">IndexNow already pings Bing automatically. Verify ownership for full reports.</p>
                </div>
            </div>
            <ol class="text-sm text-slate-700 space-y-3 list-decimal list-inside mb-4">
                <li><a href="https://www.bing.com/webmasters" target="_blank" rel="noopener" class="text-[#173978] font-semibold underline">Bing Webmaster</a> → add site → Meta tag → paste token into <code class="text-xs bg-slate-100 px-1 rounded">SEO_BING_SITE_VERIFICATION</code></li>
                <li>Submit sitemap: <strong><?php echo htmlspecialchars($sitemapUrl); ?></strong></li>
            </ol>
            <div class="flex items-center gap-2 text-sm <?php echo $bingVerified ? 'text-green-700' : 'text-slate-500'; ?>">
                <i class="fa-solid <?php echo $bingVerified ? 'fa-circle-check' : 'fa-circle-info'; ?>"></i>
                <?php echo $bingVerified ? 'Bing verification token is configured.' : 'Bing token optional — IndexNow pings work without it.'; ?>
            </div>
        </div>

        <!-- Full reindex -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8">
            <div class="flex items-start gap-4 mb-6">
                <div class="w-14 h-14 bg-[#173978]/10 text-[#173978] rounded-2xl flex items-center justify-center shrink-0 text-2xl">
                    <i class="fa-solid fa-satellite-dish"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-[#173978]">Ping all indexable pages</h3>
                    <p class="text-sm text-slate-500 mt-1 leading-relaxed">
                        Submits every public URL to IndexNow (Bing, Yandex) and pings the sitemap.
                        Use after deploy or bulk content changes.
                    </p>
                </div>
            </div>

            <form method="POST" onsubmit="return confirm('Submit all site URLs to IndexNow now?');">
                <?php echo security_csrf_field(); ?>
                <input type="hidden" name="run_reindex" value="1">
                <button type="submit" class="w-full sm:w-auto bg-[#173978] text-white px-8 py-4 rounded-xl font-bold hover:bg-[#2fcaf0] hover:text-[#173978] transition-all shadow-md text-base">
                    <i class="fa-solid fa-bolt mr-2"></i> Reindex All URLs Now
                </button>
            </form>
            <p class="text-[11px] text-slate-400 mt-4">CLI: <code class="bg-slate-100 px-1 rounded">php seo-reindex.php</code></p>
        </div>
    </div>
</main>
</body>
</html>
