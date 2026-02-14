<?php
/**
 * Login Page - Rangsit CDP
 */
require_once __DIR__ . '/config/auth.php';

// Already logged in? redirect to admin
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$error = '';
$identity = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identity === '' || $password === '') {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        $result = attemptLogin($identity, $password);
        if ($result['success']) {
            $redirect = $_GET['redirect'] ?? (BASE_URL . '/admin/index.php');
            header('Location: ' . $redirect);
            exit;
        }
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ — Rangsit CDP</title>
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, body, button, input { font-family: 'Sarabun', 'Inter', sans-serif; }
        .fa, .fas, .far, .fab, .fa-solid, .fa-regular, .fa-brands { font-family: 'Font Awesome 6 Free', 'Font Awesome 6 Brands' !important; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-slate-900 min-h-screen flex items-center justify-center p-4 transition-colors">

<div class="w-full max-w-md">
    <!-- Logo & Title -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-200 mb-4">
            <i class="fas fa-map-marked-alt text-white text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Rangsit CDP</h1>
        <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Community Data Platform — เทศบาลนครรังสิต</p>
    </div>

    <!-- Login Card -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-8">
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">เข้าสู่ระบบ</h2>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">กรุณาเข้าสู่ระบบเพื่อใช้งาน Admin Panel</p>
        </div>

        <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2">
            <i class="fas fa-exclamation-circle text-red-500"></i>
            <span class="text-sm text-red-700"><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="space-y-4">
                <!-- Username / Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                        ชื่อผู้ใช้ หรือ อีเมล
                    </label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" name="identity" value="<?= htmlspecialchars($identity) ?>"
                               placeholder="username หรือ email@example.com"
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               required autofocus>
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                        รหัสผ่าน
                    </label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="password" name="password" id="passwordInput"
                               placeholder="กรอกรหัสผ่าน"
                               class="w-full pl-10 pr-10 py-2.5 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               required>
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i id="eyeIcon" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember me -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="text-sm text-gray-600 dark:text-slate-400">จดจำการเข้าสู่ระบบ</span>
                    </label>
                </div>

                <!-- Submit -->
                <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-sign-in-alt"></i>
                    เข้าสู่ระบบ
                </button>
            </div>
        </form>
    </div>

    <!-- Footer -->
    <p class="text-center text-xs text-gray-400 dark:text-slate-500 mt-6">
        &copy; 2025 Rangsit CDP — Community Data Platform
    </p>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('passwordInput');
    const icon = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

</body>
</html>
