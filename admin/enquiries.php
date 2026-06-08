<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin', 'marketer']);

require '../db.php';
require_once __DIR__ . '/../includes/assets.php';
require_once '../includes/enquiry-helpers.php';

$params = enquiry_filter_params();
$querySuffix = enquiry_build_query($params);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['delete_enquiry'])) {
    $table = ($_POST['type'] ?? '') === 'Business' ? 'contact_enquiries' : 'enquiries';
    $pdo->prepare("DELETE FROM {$table} WHERE id = ?")->execute([(int) ($_POST['delete_id'] ?? 0)]);
    $redirect = enquiry_build_query($params, ['msg' => 'Deleted']);
    header('Location: enquiries.php' . $redirect);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $ok = enquiry_update_status($pdo, $_POST['type'] ?? '', (int) ($_POST['id'] ?? 0), $_POST['status'] ?? '');
    $redirectParams = [
        'filter'    => trim((string) ($_POST['redirect_filter'] ?? 'all')),
        'q'         => trim((string) ($_POST['redirect_q'] ?? '')),
        'type'      => trim((string) ($_POST['redirect_type'] ?? '')),
        'source'    => trim((string) ($_POST['redirect_source'] ?? '')),
        'date_from' => trim((string) ($_POST['redirect_date_from'] ?? '')),
        'date_to'   => trim((string) ($_POST['redirect_date_to'] ?? '')),
        'page'      => max(1, (int) ($_POST['redirect_page'] ?? 1)),
    ];
    $redirect = enquiry_build_query($redirectParams, ['msg' => $ok ? 'StatusUpdated' : 'Error']);
    header('Location: enquiries.php' . $redirect);
    exit();
}

$filter = $params['filter'];
$statuses = enquiry_statuses();
$counts = enquiry_status_counts($pdo);
$filtered = enquiry_apply_filters(enquiry_fetch_merged($pdo), $params);
$pagination = enquiry_paginate($filtered, $params['page'], 25);
$rows = $pagination['rows'];
$sourceOptions = enquiry_source_options($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Enquiries | Bisani Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
    <style>
        .enquiry-table-wrap {
            overflow-x: auto;
            max-width: 100%;
        }
        .enquiry-table {
            table-layout: fixed;
            width: 100%;
        }
        .enquiry-table th,
        .enquiry-table td {
            padding: 0.5rem 0.4rem;
            vertical-align: top;
            font-size: 0.75rem;
            line-height: 1.35;
        }
        .enquiry-table th {
            font-size: 0.65rem;
            letter-spacing: 0.04em;
        }
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
        .enquiry-view-btn {
            cursor: pointer;
            text-align: left;
            width: 100%;
            border: 0;
            background: transparent;
            padding: 0;
            color: inherit;
        }
        .enquiry-view-btn:hover {
            color: #173978;
            text-decoration: underline;
        }
        #enquiry-modal:not(.hidden) {
            display: flex;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden">
<?php include 'includes/sidebar.php'; ?>
<main class="flex-1 overflow-y-auto">
    <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 border-b border-slate-200 px-8 h-20 flex items-center justify-between">
        <div class="flex items-center gap-4 flex-wrap">
            <h2 class="text-2xl font-bold text-[#173978]">All Enquiries</h2>
            <span class="bg-blue-100 text-blue-700 text-xs font-bold px-3 py-1 rounded-full"><?php echo (int) $pagination['total']; ?> total</span>
            <?php if ($pagination['total'] > 0): ?>
            <span class="bg-slate-100 text-slate-600 text-xs font-bold px-3 py-1 rounded-full">
                Showing <?php echo (int) $pagination['from']; ?>–<?php echo (int) $pagination['to']; ?>
            </span>
            <?php endif; ?>
        </div>
    </header>
    <div class="p-8 max-w-[100%] mx-auto">
        <?php if (isset($_GET['msg'])): ?>
        <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-6 border border-green-200 text-sm">
            <?php
            echo match ($_GET['msg']) {
                'Deleted' => 'Record deleted.',
                'StatusUpdated' => 'Lead status updated.',
                default => 'Done.',
            };
            ?>
        </div>
        <?php endif; ?>

        <div class="flex flex-wrap gap-2 mb-4">
            <?php
            $tabParams = $params;
            $tabParams['filter'] = 'all';
            $tabParams['page'] = 1;
            ?>
            <a href="enquiries.php<?php echo enquiry_build_query($tabParams); ?>" class="px-4 py-2 rounded-lg text-sm font-bold <?php echo $filter === 'all' ? 'bg-[#173978] text-white' : 'bg-white border text-slate-600'; ?>">All (<?php echo $counts['all']; ?>)</a>
            <?php foreach ($statuses as $key => $label):
                $tabParams['filter'] = $key;
            ?>
            <a href="enquiries.php<?php echo enquiry_build_query($tabParams); ?>" class="px-4 py-2 rounded-lg text-sm font-bold <?php echo $filter === $key ? 'bg-[#173978] text-white' : 'bg-white border text-slate-600'; ?>"><?php echo $label; ?> (<?php echo $counts[$key]; ?>)</a>
            <?php endforeach; ?>
        </div>

        <form method="GET" class="bg-white rounded-2xl border border-slate-200 p-5 mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
            <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>"><?php endif; ?>
            <div class="lg:col-span-2">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Search</label>
                <input type="text" name="q" value="<?php echo htmlspecialchars($params['q']); ?>" placeholder="Name, email, phone, message..." class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Type</label>
                <select name="type" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">All types</option>
                    <option value="General" <?php echo $params['type'] === 'General' ? 'selected' : ''; ?>>General</option>
                    <option value="Business" <?php echo $params['type'] === 'Business' ? 'selected' : ''; ?>>Business</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Source Page</label>
                <input type="text" name="source" list="source-list" value="<?php echo htmlspecialchars($params['source']); ?>" placeholder="e.g. Home Page" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <datalist id="source-list">
                    <?php foreach ($sourceOptions as $src): ?>
                    <option value="<?php echo htmlspecialchars($src); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">From Date</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($params['date_from']); ?>" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">To Date</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($params['date_to']); ?>" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex gap-2 lg:col-span-6">
                <button type="submit" class="bg-[#173978] text-white px-5 py-2 rounded-lg text-sm font-bold hover:bg-[#2fcaf0] hover:text-[#173978] transition"><i class="fa-solid fa-filter mr-1"></i> Apply Filters</button>
                <a href="enquiries.php" class="px-5 py-2 rounded-lg text-sm font-bold border border-slate-200 text-slate-600 hover:bg-slate-50">Clear All</a>
            </div>
        </form>

        <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
            <div class="enquiry-table-wrap">
                <table class="enquiry-table text-left">
                    <thead class="bg-slate-50 text-slate-500 uppercase">
                        <tr>
                            <th style="width:9%">Date</th>
                            <th style="width:9%">Name</th>
                            <th style="width:16%">Contact</th>
                            <th style="width:14%">Subject</th>
                            <th style="width:7%">Type</th>
                            <th style="width:9%">Status</th>
                            <th style="width:28%">Message</th>
                            <th style="width:4%" class="text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php if ($rows): foreach ($rows as $row):
                        $st = $row['status'] ?? 'new';
                        $deleteQs = enquiry_build_query($params, [
                            'delete_id' => $row['id'],
                            'type'      => $row['type'],
                        ]);
                        $modalData = htmlspecialchars(json_encode([
                            'name'    => $row['name'] ?? '',
                            'email'   => $row['email'] ?? '',
                            'phone'   => $row['phone'] ?? '',
                            'date'    => date('M d, Y h:i A', strtotime($row['created_at'])),
                            'subject' => $row['subject'] ?? '-',
                            'source'  => $row['source_page'] ?? '',
                            'type'    => $row['type'] ?? '',
                            'status'  => ucfirst($st),
                            'message' => $row['message'] ?? '',
                        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                    ?>
                        <tr class="hover:bg-slate-50">
                            <td class="text-slate-500">
                                <span class="block font-medium"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                                <span class="block text-[10px] text-slate-400"><?php echo date('h:i A', strtotime($row['created_at'])); ?></span>
                            </td>
                            <td class="font-bold text-[#173978]"><span class="cell-clip" title="<?php echo htmlspecialchars($row['name']); ?>"><?php echo htmlspecialchars($row['name']); ?></span></td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="cell-clip text-slate-600 hover:text-blue-600" title="<?php echo htmlspecialchars($row['email']); ?>"><?php echo htmlspecialchars($row['email']); ?></a>
                                <a href="tel:<?php echo htmlspecialchars($row['phone']); ?>" class="cell-clip text-[10px] text-slate-400" title="<?php echo htmlspecialchars($row['phone']); ?>"><?php echo htmlspecialchars($row['phone']); ?></a>
                            </td>
                            <td>
                                <button type="button" class="enquiry-view-btn enquiry-detail-btn" data-enquiry="<?php echo $modalData; ?>">
                                    <span class="cell-clip-2 font-medium text-slate-700"><?php echo htmlspecialchars($row['subject'] ?? '-'); ?></span>
                                    <span class="cell-clip text-[10px] text-slate-400"><?php echo htmlspecialchars($row['source_page'] ?? ''); ?></span>
                                </button>
                            </td>
                            <td><span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded border <?php echo $row['type'] === 'Business' ? 'bg-indigo-50 text-indigo-600' : 'bg-orange-50 text-orange-600'; ?>"><?php echo $row['type']; ?></span></td>
                            <td>
                                <form method="POST" class="inline">
                                    <?php echo security_csrf_field(); ?>
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($row['type']); ?>">
                                    <input type="hidden" name="redirect_filter" value="<?php echo htmlspecialchars($filter); ?>">
                                    <input type="hidden" name="redirect_q" value="<?php echo htmlspecialchars($params['q']); ?>">
                                    <input type="hidden" name="redirect_type" value="<?php echo htmlspecialchars($params['type']); ?>">
                                    <input type="hidden" name="redirect_source" value="<?php echo htmlspecialchars($params['source']); ?>">
                                    <input type="hidden" name="redirect_date_from" value="<?php echo htmlspecialchars($params['date_from']); ?>">
                                    <input type="hidden" name="redirect_date_to" value="<?php echo htmlspecialchars($params['date_to']); ?>">
                                    <input type="hidden" name="redirect_page" value="<?php echo (int) $params['page']; ?>">
                                    <select name="status" onchange="this.form.submit()" class="text-[10px] font-bold border rounded px-1 py-1 w-full max-w-[88px] <?php echo enquiry_status_badge_class($st); ?>">
                                        <?php foreach ($statuses as $k => $lbl): ?>
                                        <option value="<?php echo $k; ?>" <?php echo $st === $k ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td class="text-slate-600">
                                <button type="button" class="enquiry-view-btn enquiry-detail-btn cell-clip-2" data-enquiry="<?php echo $modalData; ?>" title="Click to read full message">
                                    <?php echo htmlspecialchars($row['message']); ?>
                                </button>
                            </td>
                            <td class="text-right">
                                <form method="POST" action="enquiries.php<?php echo enquiry_build_query($params); ?>" class="inline" onsubmit="return confirm('Delete this enquiry?')">
                                    <?php echo security_csrf_field(); ?>
                                    <input type="hidden" name="delete_enquiry" value="1">
                                    <input type="hidden" name="delete_id" value="<?php echo (int) $row['id']; ?>">
                                    <input type="hidden" name="type" value="<?php echo security_e($row['Type']); ?>">
                                    <button type="submit" class="text-red-400 hover:text-red-600 p-1 inline-block border-0 bg-transparent cursor-pointer" title="Delete"><i class="fa-regular fa-trash-can text-xs"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="8" class="p-12 text-center text-slate-400">No enquiries match your filters.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="flex flex-wrap items-center justify-between gap-4 mt-6">
            <p class="text-sm text-slate-500">
                Page <?php echo (int) $pagination['page']; ?> of <?php echo (int) $pagination['total_pages']; ?>
                · <?php echo (int) $pagination['per_page']; ?> per page
            </p>
            <div class="flex flex-wrap gap-2">
                <?php if ($pagination['page'] > 1):
                    $prevQs = enquiry_build_query($params, ['page' => $pagination['page'] - 1]);
                ?>
                <a href="enquiries.php<?php echo $prevQs; ?>" class="px-4 py-2 rounded-lg text-sm font-bold border bg-white text-slate-600 hover:bg-slate-50"><i class="fa-solid fa-chevron-left mr-1"></i> Previous</a>
                <?php endif; ?>

                <?php
                $start = max(1, $pagination['page'] - 2);
                $end = min($pagination['total_pages'], $pagination['page'] + 2);
                for ($p = $start; $p <= $end; $p++):
                    $pageQs = enquiry_build_query($params, ['page' => $p]);
                ?>
                <a href="enquiries.php<?php echo $pageQs; ?>" class="px-3 py-2 rounded-lg text-sm font-bold border <?php echo $p === $pagination['page'] ? 'bg-[#173978] text-white border-[#173978]' : 'bg-white text-slate-600 hover:bg-slate-50'; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>

                <?php if ($pagination['page'] < $pagination['total_pages']):
                    $nextQs = enquiry_build_query($params, ['page' => $pagination['page'] + 1]);
                ?>
                <a href="enquiries.php<?php echo $nextQs; ?>" class="px-4 py-2 rounded-lg text-sm font-bold border bg-white text-slate-600 hover:bg-slate-50">Next <i class="fa-solid fa-chevron-right ml-1"></i></a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<div id="enquiry-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-slate-200">
            <h3 class="font-bold text-[#173978] text-lg">Enquiry Details</h3>
            <button type="button" id="enquiry-modal-close" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
        </div>
        <div class="p-5 overflow-y-auto text-sm space-y-3" id="enquiry-modal-body"></div>
        <div class="p-4 border-t border-slate-100 flex justify-end">
            <button type="button" id="enquiry-modal-close-btn" class="px-5 py-2 bg-[#173978] text-white rounded-lg font-bold text-sm hover:bg-[#2fcaf0] hover:text-[#173978]">Close</button>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('enquiry-modal');
    var body = document.getElementById('enquiry-modal-body');
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
            '<div class="col-span-2"><span class="text-slate-400 uppercase font-bold text-[10px]">Phone</span><p><a href="tel:' + esc(data.phone) + '">' + esc(data.phone) + '</a></p></div>' +
            '<div><span class="text-slate-400 uppercase font-bold text-[10px]">Type</span><p>' + esc(data.type) + '</p></div>' +
            '<div><span class="text-slate-400 uppercase font-bold text-[10px]">Status</span><p>' + esc(data.status) + '</p></div>' +
            '<div class="col-span-2"><span class="text-slate-400 uppercase font-bold text-[10px]">Subject</span><p class="font-medium">' + esc(data.subject) + '</p></div>' +
            '<div class="col-span-2"><span class="text-slate-400 uppercase font-bold text-[10px]">Source</span><p>' + esc(data.source) + '</p></div>' +
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

    document.querySelectorAll('.enquiry-detail-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            try {
                openModal(JSON.parse(btn.getAttribute('data-enquiry') || '{}'));
            } catch (e) {}
        });
    });

    document.getElementById('enquiry-modal-close')?.addEventListener('click', closeModal);
    document.getElementById('enquiry-modal-close-btn')?.addEventListener('click', closeModal);
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
