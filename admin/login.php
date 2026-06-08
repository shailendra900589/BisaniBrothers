<?php
require_once __DIR__ . '/../includes/security.php';
security_bootstrap();
require __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/assets.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!security_login_allowed()) {
        $error = security_login_lockout_message();
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            security_regenerate_session();
            security_login_succeeded();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = strtolower($user['role']);
            $_SESSION['admin_logged_in'] = true;

            if ($_SESSION['role'] === 'hr') {
                header('Location: jobs.php');
            } elseif ($_SESSION['role'] === 'writer') {
                header('Location: blogs.php');
            } elseif ($_SESSION['role'] === 'marketer') {
                header('Location: popups.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        }

        security_login_failed();
        $error = 'Invalid credentials. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Bisani Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo bb_admin_stylesheet(); ?>">
    <style>
        .login-field-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            font-size: 0.95rem;
        }
        .login-field-input {
            padding-left: 2.75rem;
            padding-right: 2.75rem;
        }
        .login-toggle-password {
            position: absolute;
            right: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.25rem;
            line-height: 1;
        }
        .login-toggle-password:hover { color: #173978; }
    </style>
</head>
<body class="bg-[#173978] h-screen flex items-center justify-center">
    <div class="bg-white p-10 rounded-2xl shadow-2xl w-96 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-[#2fcaf0] to-blue-600"></div>
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#173978]">Bisani<span class="text-[#2fcaf0]">Portal</span></h1>
            <p class="text-gray-400 text-sm mt-2">Secure Employee Access</p>
        </div>
        
        <?php if ($error) echo "<div class='bg-red-50 text-red-500 text-sm text-center p-3 rounded mb-6 border border-red-100 font-medium'>".security_e($error)."</div>"; ?>
        
        <form method="POST" autocomplete="on">
            <div class="mb-5">
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2" for="username">Username</label>
                <div class="relative">
                    <i class="fa-regular fa-user login-field-icon" aria-hidden="true"></i>
                    <input type="text" id="username" name="username" autocomplete="username"
                           class="login-field-input w-full py-3 border border-gray-200 rounded-lg focus:border-[#2fcaf0] focus:ring-2 focus:ring-[#2fcaf0]/20 outline-none transition-all"
                           placeholder="Enter username" required>
                </div>
            </div>
            <div class="mb-8">
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2" for="password">Password</label>
                <div class="relative">
                    <i class="fa-solid fa-lock login-field-icon" aria-hidden="true"></i>
                    <input type="password" id="password" name="password" autocomplete="current-password"
                           class="login-field-input w-full py-3 border border-gray-200 rounded-lg focus:border-[#2fcaf0] focus:ring-2 focus:ring-[#2fcaf0]/20 outline-none transition-all"
                           placeholder="Enter password" required>
                    <button type="button" class="login-toggle-password" id="toggle-password" aria-label="Show password">
                        <i class="fa-regular fa-eye" id="toggle-password-icon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="w-full bg-[#173978] text-white font-bold py-3.5 rounded-xl hover:bg-[#2fcaf0] hover:text-[#173978] hover:shadow-lg transition-all transform hover:-translate-y-0.5">
                Access Dashboard
            </button>
        </form>
    </div>
    <script>
        (function () {
            var input = document.getElementById('password');
            var btn = document.getElementById('toggle-password');
            var icon = document.getElementById('toggle-password-icon');
            if (!input || !btn || !icon) return;
            btn.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                icon.classList.toggle('fa-eye', !show);
                icon.classList.toggle('fa-eye-slash', show);
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        })();
    </script>
</body>
</html>