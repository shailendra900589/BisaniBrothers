<?php
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin']);

require '../db.php';
require_once __DIR__ . '/../includes/assets.php';

$msg = '';
$msg_type = '';

admin_handle_post_action(function (int $id) use ($pdo) {
    $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
}, 'users.php?msg=User+Deleted&type=success', 'delete');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['captcha']) || ($_POST['captcha'] ?? '') !== ($_SESSION['custom_captcha'] ?? '')) {
        $msg = 'Error: Invalid Captcha Code!';
        $msg_type = 'error';
    } else {
        if (isset($_POST['create_user'])) {
            $username = trim($_POST['username'] ?? '');
            $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
            $role = $_POST['role'] ?? 'writer';
            $full_name = trim($_POST['full_name'] ?? '');

            $check = $pdo->prepare('SELECT id FROM users WHERE username=?');
            $check->execute([$username]);
            if ($check->rowCount() > 0) {
                $msg = 'Error: Username already exists!';
                $msg_type = 'error';
            } else {
                $pdo->prepare('INSERT INTO users (username, password, role, full_name) VALUES (?, ?, ?, ?)')->execute([$username, $password, $role, $full_name]);
                $msg = 'User Created Successfully!';
                $msg_type = 'success';
            }
        }

        if (isset($_POST['update_password'])) {
            $user_id = (int) ($_POST['user_id'] ?? 0);
            $new_password = password_hash($_POST['new_password'] ?? '', PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([$new_password, $user_id]);
            $msg = 'Password Updated Successfully!';
            $msg_type = 'success';
        }
    }
}

$users = $pdo->query('SELECT * FROM users ORDER BY id DESC')->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users | Bisani Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto relative">
        <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 border-b border-slate-200 px-8 h-20 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-[#173978]">User Management</h2>
        </header>

        <div class="p-8 max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-[#173978] mb-4 text-lg">Current Staff & Admins</h3>
                
                <?php if($msg || isset($_GET['msg'])): ?>
                    <?php 
                        $m = $msg ?: $_GET['msg'];
                        $t = $msg_type ?: ($_GET['type'] ?? 'success');
                        $color = ($t == 'error') ? 'red' : 'green';
                    ?>
                    <div class="mb-4 p-3 bg-<?php echo $color; ?>-50 text-<?php echo $color; ?>-700 rounded-lg text-sm font-bold border border-<?php echo $color; ?>-200 flex items-center">
                        <i class="fa-solid fa-circle-info mr-2"></i> <?php echo security_e($m); ?>
                    </div>
                <?php endif; ?>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-xs text-slate-400 uppercase border-b bg-slate-50/50">
                                <th class="p-3">Name</th>
                                <th class="p-3">Username</th>
                                <th class="p-3">Role</th>
                                <th class="p-3">Password</th>
                                <th class="p-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php foreach($users as $u): ?>
                            <tr class="border-b last:border-0 hover:bg-slate-50 transition-colors">
                                <td class="p-3 font-bold text-slate-700"><?php echo htmlspecialchars($u['full_name']); ?></td>
                                <td class="p-3 text-slate-500 font-mono text-xs"><?php echo htmlspecialchars($u['username']); ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider border
                                        <?php echo $u['role']=='admin'?'bg-red-50 text-red-600 border-red-100':($u['role']=='hr'?'bg-purple-50 text-purple-600 border-purple-100':'bg-blue-50 text-blue-600 border-blue-100'); ?>">
                                        <?php echo $u['role']; ?>
                                    </span>
                                </td>
                                <td class="p-3 text-xs text-slate-400">
                                    <span title="Encrypted for security">••••••••</span>
                                </td>
                                <td class="p-3 text-right">
                                    <button type="button" onclick="openPasswordModal(<?php echo (int) $u['id']; ?>, <?php echo json_encode($u['username'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)" class="text-blue-500 hover:text-blue-700 px-2" title="Change Password">
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                    
                                    <?php if ($u['role'] !== 'admin' || $u['username'] !== $_SESSION['username']): ?>
                                        <?php echo admin_post_button('users.php', (int) $u['id'], 'delete', '<span class="text-red-400 hover:text-red-600 px-2" title="Delete User"><i class="fa-solid fa-trash"></i></span>', 'Permanently delete this user?'); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 h-fit sticky top-24">
                <h3 class="font-bold text-[#173978] mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-[#2fcaf0]"></i> Create Account
                </h3>
                
                <form method="POST" class="space-y-4">
                    <?php echo security_csrf_field(); ?>
                    <input type="hidden" name="create_user" value="1">
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Full Name</label>
                        <input type="text" name="full_name" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:border-[#2fcaf0] focus:ring-1 focus:ring-[#2fcaf0] outline-none transition-all" placeholder="John Doe" required>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Role Permission</label>
                        <select name="role" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white focus:border-[#2fcaf0] outline-none">
                            <option value="hr">HR Manager (Jobs & Apps)</option>
                            <option value="writer">Blog Writer (Content Only)</option>
                            <option value="marketer">Marketing (Leads & Popups)</option>
                            <option value="admin">Super Admin (Full Access)</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Username</label>
                            <input type="text" name="username" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:border-[#2fcaf0] outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Password</label>
                            <input type="password" name="password" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:border-[#2fcaf0] outline-none" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="bg-slate-50 p-3 rounded-lg border border-slate-200">
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Security Captcha</label>
                        <div class="flex gap-2 mb-2">
                            <img src="users-captcha.php?generate_captcha=1" id="captcha_img_create" alt="Captcha" class="rounded border border-slate-300 h-10 w-full object-cover">
                            <button type="button" onclick="refreshCaptcha('captcha_img_create')" class="bg-white border border-slate-300 px-3 rounded text-slate-500 hover:text-[#2fcaf0] transition">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </button>
                        </div>
                        <input type="text" name="captcha" placeholder="Enter code above" class="w-full border border-slate-200 rounded px-3 py-2 text-sm focus:border-[#2fcaf0] outline-none" required autocomplete="off">
                    </div>

                    <button type="submit" class="w-full bg-[#173978] text-white font-bold py-3 rounded-lg hover:bg-blue-900 transition-all shadow-lg shadow-blue-900/20">
                        Create User
                    </button>
                </form>
            </div>
        </div>
    </main>

    <div id="passwordModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 m-4 transform transition-all scale-100">
            <h3 class="text-xl font-bold text-[#173978] mb-1">Reset Password</h3>
            <p class="text-sm text-slate-500 mb-6">Update password for user: <span id="modalUser" class="font-bold text-blue-600"></span></p>
            
            <form method="POST" class="space-y-5">
                <?php echo security_csrf_field(); ?>
                <input type="hidden" name="update_password" value="1">
                <input type="hidden" name="user_id" id="modalUserId">
                
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">New Password</label>
                    <input type="password" name="new_password" class="w-full border border-slate-200 rounded-lg px-4 py-3 text-sm focus:border-[#2fcaf0] outline-none bg-slate-50 focus:bg-white transition-all" placeholder="Enter new strong password" required autocomplete="new-password">
                </div>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Security Check</label>
                    <div class="flex gap-2 mb-3">
                        <img src="users-captcha.php?generate_captcha=1" id="captcha_img_modal" alt="Captcha" class="rounded border border-slate-300 h-10 w-full object-cover">
                        <button type="button" onclick="refreshCaptcha('captcha_img_modal')" class="bg-white border border-slate-300 px-3 rounded text-slate-500 hover:text-[#2fcaf0] transition">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </button>
                    </div>
                    <input type="text" name="captcha" placeholder="Enter code shown" class="w-full border border-slate-200 rounded px-3 py-2 text-sm focus:border-[#2fcaf0] outline-none" required autocomplete="off">
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('passwordModal').classList.add('hidden')" class="flex-1 px-4 py-3 border border-slate-200 rounded-lg text-slate-600 font-bold hover:bg-slate-50 transition">Cancel</button>
                    <button type="submit" class="flex-1 bg-[#2fcaf0] text-[#173978] font-bold py-3 rounded-lg hover:bg-[#173978] hover:text-white transition-all">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Refresh Captcha Image Logic
        function refreshCaptcha(imgId) {
            document.getElementById(imgId).src = 'users-captcha.php?generate_captcha=1&t=' + new Date().getTime();
        }

        // Open Password Modal
        function openPasswordModal(id, username) {
            document.getElementById('modalUserId').value = id;
            document.getElementById('modalUser').innerText = username;
            document.getElementById('passwordModal').classList.remove('hidden');
            refreshCaptcha('captcha_img_modal'); // Get fresh captcha for modal
        }
    </script>
</body>
</html>