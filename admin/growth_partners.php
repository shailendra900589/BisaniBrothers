<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin', 'marketer']);

require '../db.php';
require_once __DIR__ . '/../includes/assets.php';
require_once __DIR__ . '/../includes/growth-partner-helpers.php';

admin_handle_post_action(function (int $id) use ($pdo) {
    $stmt = $pdo->prepare('DELETE FROM growth_partners WHERE id = ?');
    $stmt->execute([$id]);
}, 'growth_partners.php?msg=Deleted', 'delete_id');

$stmt = $pdo->query('SELECT * FROM growth_partners ORDER BY created_at DESC');
$partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($partners);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Growth Partners | Bisani Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
    <style>
        .gp-table-wrap { overflow-x: auto; max-width: 100%; }
        .gp-table { table-layout: fixed; width: 100%; }
        .gp-table th, .gp-table td {
            padding: 0.5rem 0.45rem;
            vertical-align: top;
            font-size: 0.75rem;
            line-height: 1.35;
        }
        .gp-table th { font-size: 0.65rem; letter-spacing: 0.04em; }
        .cell-clip {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: block;
            max-width: 100%;
        }
        .cell-clip-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }
        .gp-view-btn {
            cursor: pointer;
            text-align: left;
            width: 100%;
            border: 0;
            background: transparent;
            padding: 0;
            color: inherit;
        }
        .gp-view-btn:hover { color: #173978; text-decoration: underline; }
        #gp-modal:not(.hidden) { display: flex; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden">

<?php include 'includes/sidebar.php'; ?>

<main class="flex-1 overflow-y-auto">
    <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 border-b border-slate-200 px-6 lg:px-8 h-20 flex items-center justify-between">
        <div class="flex items-center gap-3 flex-wrap">
            <h2 class="text-2xl font-bold text-[#173978]">Growth Partners</h2>
            <span class="bg-teal-100 text-teal-700 text-xs font-bold px-3 py-1 rounded-full"><?php echo (int) $total; ?> applications</span>
        </div>
    </header>

    <div class="p-6 lg:p-8 max-w-[100%] mx-auto">
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'Deleted'): ?>
        <div class="bg-green-100 text-green-800 p-3 rounded-lg mb-4 border border-green-200 text-sm">Application deleted.</div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
            <div class="gp-table-wrap">
                <table class="gp-table text-left">
                    <thead class="bg-slate-50 text-slate-500 uppercase">
                        <tr>
                            <th style="width:10%">Date</th>
                            <th style="width:12%">Name</th>
                            <th style="width:18%">Contact</th>
                            <th style="width:8%">Type</th>
                            <th style="width:12%">Location</th>
                            <th style="width:14%">Experience</th>
                            <th style="width:10%">Work</th>
                            <th style="width:22%">Details</th>
                            <th style="width:4%"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php if ($partners): foreach ($partners as $row):
                        $parsed = growth_partner_parse_message($row['message'] ?? '');
                        $modalData = htmlspecialchars(json_encode([
                            'name'    => $row['name'] ?? '',
                            'email'   => $row['email'] ?? '',
                            'mobile'  => $row['mobile'] ?? '',
                            'date'    => date('M d, Y h:i A', strtotime($row['created_at'])),
                            'type'    => growth_partner_type_label($row['applicant_type'] ?? ''),
                            'city'    => $parsed['city'] ?: '-',
                            'occupation' => $parsed['occupation'] ?: '-',
                            'experience' => $parsed['experience'] ?: '-',
                            'work_type'  => $parsed['work_type'] ?: '-',
                            'team_size'  => $parsed['team_size'] ?: '-',
                            'regions'    => $parsed['regions'] ?: '-',
                            'message' => $parsed['raw'] ?: '-',
                        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                        $summary = growth_partner_summary($parsed);
                    ?>
                        <tr class="hover:bg-slate-50">
                            <td class="text-slate-500">
                                <span class="block font-medium"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                                <span class="block text-[10px] text-slate-400"><?php echo date('h:i A', strtotime($row['created_at'])); ?></span>
                            </td>
                            <td class="font-bold text-[#173978]">
                                <span class="cell-clip" title="<?php echo htmlspecialchars($row['name']); ?>"><?php echo htmlspecialchars($row['name']); ?></span>
                            </td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="cell-clip text-slate-600 hover:text-blue-600"><?php echo htmlspecialchars($row['email']); ?></a>
                                <a href="tel:<?php echo htmlspecialchars($row['mobile']); ?>" class="cell-clip text-[10px] text-slate-400"><?php echo htmlspecialchars($row['mobile']); ?></a>
                            </td>
                            <td>
                                <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded border <?php echo growth_partner_type_badge($row['applicant_type'] ?? ''); ?>">
                                    <?php echo htmlspecialchars(growth_partner_type_label($row['applicant_type'] ?? '')); ?>
                                </span>
                            </td>
                            <td class="text-slate-600">
                                <span class="cell-clip" title="<?php echo htmlspecialchars($parsed['city']); ?>"><?php echo htmlspecialchars($parsed['city'] ?: '-'); ?></span>
                            </td>
                            <td class="text-slate-600">
                                <span class="cell-clip"><?php echo htmlspecialchars($parsed['experience'] ?: '-'); ?></span>
                                <?php if ($parsed['occupation'] !== ''): ?>
                                <span class="cell-clip text-[10px] text-slate-400" title="<?php echo htmlspecialchars($parsed['occupation']); ?>"><?php echo htmlspecialchars($parsed['occupation']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-slate-600">
                                <span class="cell-clip"><?php echo htmlspecialchars($parsed['work_type'] ?: '-'); ?></span>
                            </td>
                            <td class="text-slate-600">
                                <button type="button" class="gp-view-btn gp-detail-btn cell-clip-2" data-partner="<?php echo $modalData; ?>" title="Click for full details">
                                    <?php echo htmlspecialchars($summary); ?>
                                </button>
                            </td>
                            <td class="text-right">
                                <?php echo admin_post_button('growth_partners.php', (int) $row['id'], 'delete_id', '<span class="text-red-400 hover:text-red-600 p-1 inline-block" title="Delete"><i class="fa-regular fa-trash-can text-xs"></i></span>', 'Delete this application?'); ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="9" class="p-10 text-center text-slate-400">No partner applications yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div id="gp-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-slate-200">
            <h3 class="font-bold text-[#173978] text-lg">Partner Application</h3>
            <button type="button" id="gp-modal-close" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
        </div>
        <div class="p-5 overflow-y-auto text-sm" id="gp-modal-body"></div>
        <div class="p-4 border-t border-slate-100 flex justify-end">
            <button type="button" id="gp-modal-close-btn" class="px-5 py-2 bg-[#173978] text-white rounded-lg font-bold text-sm hover:bg-[#2fcaf0] hover:text-[#173978]">Close</button>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('gp-modal');
    var body = document.getElementById('gp-modal-body');
    if (!modal || !body) return;

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    function openModal(data) {
        body.innerHTML =
            '<div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">' +
            '<div><span class="text-slate-400 uppercase font-bold text-[10px]">Name</span><p class="font-bold text-[#173978]">' + esc(data.name) + '</p></div>' +
            '<div><span class="text-slate-400 uppercase font-bold text-[10px]">Date</span><p>' + esc(data.date) + '</p></div>' +
            '<div class="col-span-2"><span class="text-slate-400 uppercase font-bold text-[10px]">Email</span><p><a href="mailto:' + esc(data.email) + '" class="text-blue-600 break-all">' + esc(data.email) + '</a></p></div>' +
            '<div class="col-span-2"><span class="text-slate-400 uppercase font-bold text-[10px]">Mobile</span><p><a href="tel:' + esc(data.mobile) + '">' + esc(data.mobile) + '</a></p></div>' +
            '<div><span class="text-slate-400 uppercase font-bold text-[10px]">Type</span><p>' + esc(data.type) + '</p></div>' +
            '<div><span class="text-slate-400 uppercase font-bold text-[10px]">Location</span><p>' + esc(data.city) + '</p></div>' +
            '<div><span class="text-slate-400 uppercase font-bold text-[10px]">Occupation</span><p>' + esc(data.occupation) + '</p></div>' +
            '<div><span class="text-slate-400 uppercase font-bold text-[10px]">Experience</span><p>' + esc(data.experience) + '</p></div>' +
            '<div><span class="text-slate-400 uppercase font-bold text-[10px]">Work Mode</span><p>' + esc(data.work_type) + '</p></div>' +
            '<div><span class="text-slate-400 uppercase font-bold text-[10px]">Team Size</span><p>' + esc(data.team_size) + '</p></div>' +
            '<div class="col-span-2"><span class="text-slate-400 uppercase font-bold text-[10px]">Regions</span><p>' + esc(data.regions) + '</p></div>' +
            '</div>' +
            '<div class="mt-4 pt-4 border-t border-slate-100">' +
            '<span class="text-slate-400 uppercase font-bold text-[10px]">Full Message</span>' +
            '<p class="mt-2 text-slate-700 whitespace-pre-wrap break-words leading-relaxed">' + esc(data.message) + '</p>' +
            '</div>';
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.gp-detail-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            try {
                openModal(JSON.parse(btn.getAttribute('data-partner') || '{}'));
            } catch (e) {}
        });
    });

    document.getElementById('gp-modal-close')?.addEventListener('click', closeModal);
    document.getElementById('gp-modal-close-btn')?.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });
})();
</script>
</body>
</html>
