<?php
require_once __DIR__ . '/includes/init.php';

require '../db.php';
require_once __DIR__ . '/../includes/assets.php';
require_once __DIR__ . '/../includes/enquiry-helpers.php';

$role = strtolower($_SESSION['role']);
$username = $_SESSION['username'] ?? 'Admin';

function dash_count(PDO $pdo, string $sql): int
{
    try {
        $stmt = $pdo->query($sql);
        return (int) ($stmt ? $stmt->fetchColumn() : 0);
    } catch (Exception $e) {
        return 0;
    }
}

$blogCount = 0;
$caseStudyCount = 0;
$jobCount = 0;
$jobAppCount = 0;
$growthPartnerCount = 0;
$newsletterCount = 0;
$popupActive = 0;
$campaignsSent = 0;
$enquiryStats = ['total' => 0, 'new' => 0, 'contacted' => 0, 'closed' => 0, 'general' => 0, 'business' => 0, 'recent' => []];

if ($role === 'admin' || $role === 'writer') {
    $blogCount = dash_count($pdo, 'SELECT COUNT(*) FROM blogs');
    $caseStudyCount = dash_count($pdo, 'SELECT COUNT(*) FROM case_studies WHERE is_published = 1');
}

if ($role === 'admin' || $role === 'hr') {
    $jobCount = dash_count($pdo, 'SELECT COUNT(*) FROM jobs WHERE status = 1');
    $jobAppCount = dash_count($pdo, 'SELECT COUNT(*) FROM applications');
}

if ($role === 'admin' || $role === 'marketer') {
    $growthPartnerCount = dash_count($pdo, 'SELECT COUNT(*) FROM growth_partners');
    $newsletterCount = dash_count($pdo, "SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active'");
    $popupActive = dash_count($pdo, 'SELECT COUNT(*) FROM popups WHERE is_active = 1');
    $campaignsSent = dash_count($pdo, "SELECT COUNT(*) FROM marketing_campaigns WHERE status = 'sent'");
    $enquiryStats = enquiry_dashboard_stats($pdo);
}

$titleMap = [
    'hr'       => 'Recruitment Overview',
    'writer'   => 'Content Overview',
    'marketer' => 'Marketing Overview',
    'admin'    => 'Master Dashboard',
];
$pageTitle = $titleMap[$role] ?? 'Dashboard';

$enquiryTotal = max(1, (int) $enquiryStats['total']);
$pipeline = [
    'new'       => ['label' => 'New', 'count' => (int) $enquiryStats['new'], 'pct' => round($enquiryStats['new'] / $enquiryTotal * 100)],
    'contacted' => ['label' => 'Contacted', 'count' => (int) $enquiryStats['contacted'], 'pct' => round($enquiryStats['contacted'] / $enquiryTotal * 100)],
    'closed'    => ['label' => 'Closed', 'count' => (int) $enquiryStats['closed'], 'pct' => round($enquiryStats['closed'] / $enquiryTotal * 100)],
];

function dash_stat_card(string $href, string $label, string $value, string $hint, string $icon, string $accent, string $iconBg, ?string $badge = null): void
{
    ?>
    <a href="<?php echo htmlspecialchars($href); ?>" class="dash-stat-card" style="--dash-accent: <?php echo htmlspecialchars($accent); ?>">
        <div class="flex items-start justify-between gap-3 pl-1">
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400"><?php echo htmlspecialchars($label); ?></p>
                <p class="text-2xl font-extrabold text-[#173978] mt-1 leading-none"><?php echo htmlspecialchars($value); ?></p>
                <?php if ($badge): ?>
                <span class="dash-badge dash-badge-warn mt-2"><?php echo htmlspecialchars($badge); ?></span>
                <?php endif; ?>
                <p class="text-[11px] font-semibold text-slate-400 mt-3"><?php echo htmlspecialchars($hint); ?> &rarr;</p>
            </div>
            <div class="icon-wrap shrink-0 <?php echo htmlspecialchars($iconBg); ?>">
                <i class="fa-solid <?php echo htmlspecialchars($icon); ?>"></i>
            </div>
        </div>
    </a>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Bisani Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
</head>
<body class="admin-dashboard text-slate-800 flex h-screen overflow-hidden">

<?php include 'includes/sidebar.php'; ?>

<main class="flex-1 overflow-y-auto">
    <div class="p-6 lg:p-8 max-w-[1400px] mx-auto space-y-6">

        <?php if ($role === 'admin'): ?>
        <section class="dash-hero">
            <div class="relative z-10 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                <div>
                    <p class="text-[#2fcaf0] text-xs font-bold uppercase tracking-widest mb-1">Bisani Portal</p>
                    <h1 class="text-2xl lg:text-3xl font-extrabold tracking-tight"><?php echo htmlspecialchars($pageTitle); ?></h1>
                    <p class="text-blue-200/90 text-sm mt-2"><?php echo htmlspecialchars($username); ?> &middot; <?php echo date('l, M d, Y'); ?></p>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 min-w-0 lg:max-w-xl w-full">
                    <div class="dash-hero-kpi">
                        <div class="value"><?php echo (int) $enquiryStats['new']; ?></div>
                        <div class="label">New Leads</div>
                    </div>
                    <div class="dash-hero-kpi">
                        <div class="value"><?php echo (int) $enquiryStats['total']; ?></div>
                        <div class="label">Valid Enquiries</div>
                    </div>
                    <div class="dash-hero-kpi">
                        <div class="value"><?php echo (int) $jobAppCount; ?></div>
                        <div class="label">Applications</div>
                    </div>
                    <div class="dash-hero-kpi">
                        <div class="value"><?php echo (int) $newsletterCount; ?></div>
                        <div class="label">Subscribers</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="flex flex-wrap gap-2">
            <a href="enquiries.php?filter=new" class="dash-quick-action"><i class="fa-solid fa-inbox text-amber-500"></i> Review Leads</a>
            <a href="marketing-campaigns.php" class="dash-quick-action"><i class="fa-solid fa-paper-plane text-rose-500"></i> Email Campaign</a>
            <a href="jobs.php" class="dash-quick-action"><i class="fa-solid fa-briefcase text-orange-500"></i> Manage Jobs</a>
            <a href="blogs.php" class="dash-quick-action"><i class="fa-solid fa-pen-nib text-blue-500"></i> Write Blog</a>
            <a href="enquiries.php" class="dash-quick-action"><i class="fa-solid fa-envelope text-[#173978]"></i> All Enquiries</a>
        </section>
        <?php else: ?>
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold text-[#173978]"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <p class="text-sm text-slate-500 mt-1"><?php echo htmlspecialchars($username); ?> &middot; <?php echo date('M d, Y'); ?></p>
            </div>
            <span class="text-sm font-bold text-slate-500 bg-white border border-slate-200 px-3 py-1.5 rounded-full capitalize self-start">
                <i class="fa-solid fa-user-shield mr-2 text-[#2fcaf0]"></i><?php echo htmlspecialchars($role); ?>
            </span>
        </header>
        <?php endif; ?>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            <div class="xl:col-span-8 space-y-6">

                <?php if ($role === 'admin' || $role === 'marketer'): ?>
                <section>
                    <h3 class="dash-section-title">Leads &amp; Marketing</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php
                        dash_stat_card('enquiries.php?filter=new', 'New Leads', (string) $enquiryStats['new'], 'Review pending enquiries', 'fa-inbox', '#f59e0b', 'bg-amber-50 text-amber-600', $enquiryStats['new'] > 0 ? 'Action needed' : null);
                        dash_stat_card('enquiries.php', 'Valid Enquiries', (string) $enquiryStats['total'], 'Excludes blank spam', 'fa-envelope-open-text', '#173978', 'bg-blue-50 text-[#173978]');
                        dash_stat_card('growth_partners.php', 'Growth Partners', (string) $growthPartnerCount, 'Partner applications', 'fa-handshake', '#0d9488', 'bg-teal-50 text-teal-600');
                        dash_stat_card('newsletter.php', 'Newsletter', (string) $newsletterCount, 'Active subscribers', 'fa-envelope-circle-check', '#6366f1', 'bg-indigo-50 text-indigo-600');
                        dash_stat_card('popups.php', 'Site Popup', $popupActive ? 'Live' : 'Off', 'Website popup banner', 'fa-bullhorn', '#22c55e', $popupActive ? 'bg-green-50 text-green-600' : 'bg-slate-100 text-slate-500', $popupActive ? 'Live' : null);
                        dash_stat_card('marketing-campaigns.php', 'Campaigns Sent', (string) $campaignsSent, 'Email marketing', 'fa-paper-plane', '#e11d48', 'bg-rose-50 text-rose-600');
                        ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($role === 'admin' || $role === 'hr'): ?>
                <section>
                    <h3 class="dash-section-title">Recruitment</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php
                        dash_stat_card('jobs.php', 'Active Jobs', (string) $jobCount, 'Manage listings', 'fa-briefcase', '#ea580c', 'bg-orange-50 text-orange-600');
                        dash_stat_card('applications.php', 'Applications', (string) $jobAppCount, 'View candidates', 'fa-users', '#9333ea', 'bg-purple-50 text-purple-600');
                        ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($role === 'admin' || $role === 'writer'): ?>
                <section>
                    <h3 class="dash-section-title">Content</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php
                        dash_stat_card('blogs.php', 'Articles', (string) $blogCount, 'Blog posts', 'fa-newspaper', '#173978', 'bg-blue-50 text-[#173978]');
                        dash_stat_card('case-studies.php', 'Case Studies', (string) $caseStudyCount, 'Published stories', 'fa-trophy', '#0891b2', 'bg-cyan-50 text-cyan-700');
                        ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php if (($role === 'admin' || $role === 'marketer') && !empty($enquiryStats['recent'])): ?>
                <section class="dash-panel">
                    <div class="dash-panel-head">
                        <h4><i class="fa-solid fa-clock-rotate-left mr-2 text-[#2fcaf0]"></i>Recent Enquiries</h4>
                        <a href="enquiries.php" class="text-xs font-bold text-[#2fcaf0] hover:underline">View all</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="dash-table w-full text-left">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Name</th>
                                    <th>Source</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($enquiryStats['recent'] as $row):
                                $st = $row['status'] ?? 'new';
                            ?>
                                <tr>
                                    <td class="text-slate-500 whitespace-nowrap"><?php echo date('M d, h:i A', strtotime($row['created_at'])); ?></td>
                                    <td class="font-bold text-[#173978]"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="text-slate-500 max-w-[140px] truncate"><?php echo htmlspecialchars($row['source_page'] ?? '-'); ?></td>
                                    <td><span class="text-[10px] font-bold uppercase text-slate-500"><?php echo htmlspecialchars($row['type'] ?? 'General'); ?></span></td>
                                    <td>
                                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded border <?php echo enquiry_status_badge_class($st); ?>">
                                            <?php echo htmlspecialchars(enquiry_statuses()[$st] ?? $st); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <?php endif; ?>
            </div>

            <?php if ($role === 'admin' || $role === 'marketer'): ?>
            <aside class="xl:col-span-4 space-y-5">

                <?php if ($role === 'admin'): ?>
                <div class="dash-panel p-5">
                    <h4 class="font-bold text-[#173978] text-sm mb-4"><i class="fa-solid fa-chart-simple mr-2 text-[#2fcaf0]"></i>Lead Pipeline</h4>
                    <?php foreach ($pipeline as $key => $item): ?>
                    <div class="dash-pipeline-row">
                        <span class="text-xs font-bold text-slate-500 w-20 shrink-0"><?php echo htmlspecialchars($item['label']); ?></span>
                        <div class="dash-pipeline-bar">
                            <div class="dash-pipeline-fill" style="width: <?php echo max(4, (int) $item['pct']); ?>%"></div>
                        </div>
                        <span class="text-sm font-extrabold text-[#173978] w-8 text-right"><?php echo (int) $item['count']; ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-3 text-center">
                        <div class="bg-slate-50 rounded-lg py-2 px-2">
                            <p class="text-[10px] font-bold uppercase text-slate-400">General</p>
                            <p class="text-lg font-extrabold text-[#173978]"><?php echo (int) $enquiryStats['general']; ?></p>
                        </div>
                        <div class="bg-slate-50 rounded-lg py-2 px-2">
                            <p class="text-[10px] font-bold uppercase text-slate-400">Business</p>
                            <p class="text-lg font-extrabold text-[#173978]"><?php echo (int) $enquiryStats['business']; ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="dash-panel">
                    <div class="dash-panel-head">
                        <h4>Quick Links</h4>
                    </div>
                    <div class="p-2">
                        <a href="enquiries.php?filter=new" class="dash-link-item"><i class="fa-solid fa-inbox"></i> New enquiries</a>
                        <a href="marketing-campaigns.php" class="dash-link-item"><i class="fa-solid fa-envelope"></i> Email campaigns</a>
                        <a href="growth_partners.php" class="dash-link-item"><i class="fa-solid fa-handshake"></i> Growth partners</a>
                        <a href="newsletter.php" class="dash-link-item"><i class="fa-solid fa-users"></i> Newsletter list</a>
                        <a href="mail-outbox.php" class="dash-link-item"><i class="fa-solid fa-folder-open"></i> Mail outbox</a>
                        <a href="popups.php" class="dash-link-item"><i class="fa-solid fa-bullhorn"></i> Site popups</a>
                    </div>
                </div>

                <?php if ($role === 'admin'): ?>
                <div class="dash-panel p-5 bg-gradient-to-br from-[#173978] to-[#0f2655] text-white border-0">
                    <h4 class="font-bold text-sm flex items-center gap-2">
                        <i class="fa-solid fa-satellite-dish text-[#2fcaf0]"></i> SEO Reindex
                    </h4>
                    <p class="text-blue-200/80 text-xs mt-2 leading-relaxed">Ping search engines for blogs, jobs, case studies, and sitemap URLs.</p>
                    <a href="seo-reindex.php" class="mt-4 inline-flex items-center justify-center w-full bg-[#2fcaf0] text-[#173978] px-4 py-2.5 rounded-xl font-bold text-sm hover:bg-white transition">
                        <i class="fa-solid fa-bolt mr-2"></i> Reindex Now
                    </a>
                </div>
                <?php endif; ?>

            </aside>
            <?php endif; ?>

        </div>
    </div>
</main>
</body>
</html>
