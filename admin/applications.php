<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin', 'hr']);

require '../db.php';
require_once __DIR__ . '/../includes/assets.php';

$apps = $pdo->query("SELECT a.*, j.title as job_title FROM applications a JOIN jobs j ON a.job_id = j.id ORDER BY a.applied_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Applications | HR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto relative">
        <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 border-b border-slate-200 px-8 h-20 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-[#173978]">Job Applications</h2>
        </header>

        <div class="p-8 max-w-7xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase">
                        <tr>
                            <th class="p-4">Candidate Name</th>
                            <th class="p-4">Applied Position</th>
                            <th class="p-4">Contact Info</th>
                            <th class="p-4">Date</th>
                            <th class="p-4">Resume</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <?php foreach($apps as $app): ?>
                        <tr class="border-b last:border-0 hover:bg-slate-50">
                                <td class="p-4 font-bold text-[#173978]"><?php echo security_e($app['applicant_name']); ?></td>
                                <td class="p-4">
                                    <span class="bg-orange-50 text-orange-600 px-2 py-1 rounded font-bold text-xs uppercase">
                                        <?php echo security_e($app['job_title']); ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="text-slate-600"><?php echo security_e($app['email']); ?></div>
                                    <div class="text-slate-400 text-xs"><?php echo security_e($app['phone']); ?></div>
                                </td>
                                <td class="p-4 text-slate-500"><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></td>
                                <td class="p-4">
                                    <a href="download-resume.php?id=<?php echo (int) $app['id']; ?>" class="inline-flex items-center bg-[#173978] text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-[#2fcaf0] hover:text-[#173978] transition-colors">
                                    <i class="fa-solid fa-download mr-2"></i> Download CV
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($apps)) echo "<tr><td colspan='5' class='p-6 text-center text-slate-400'>No applications received yet.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>