<?php
$_B = defined('BASE_URL') ? BASE_URL : '/cdp';
$_user = currentUser();
$_displayName = $_user ? htmlspecialchars($_user['full_name']) : 'Guest';
$_roleName = $_user ? htmlspecialchars($_user['role_name']) : '';
$_avatarUrl = $_user && $_user['avatar']
    ? $_B . '/' . htmlspecialchars($_user['avatar'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($_user['full_name'] ?? 'U') . '&background=4f46e5&color=fff';
?>
<header class="bg-white border-b border-gray-200 sticky top-0 z-20">
    <div class="flex items-center justify-between px-6 h-16">
        <div class="flex items-center space-x-4">
            <button id="sidebarToggle" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                <i class="fas fa-bars text-lg"></i>
            </button>

            <div class="relative hidden md:block">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" placeholder="Search or type command..."
                    class="pl-10 pr-16 py-2 w-80 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-gray-50">
                <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-xs text-gray-400 font-medium">⌘K</span>
            </div>
        </div>

        <div class="flex items-center space-x-4">
            <button id="themeToggle" onclick="toggleTheme()" class="text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-100 transition-colors" title="สลับโหมดมืด/สว่าง">
                <i id="themeIcon" class="text-lg"></i>
            </button>
            <script>
                // Set icon immediately (before page fully renders)
                document.getElementById('themeIcon').className = 'text-lg ' + (document.documentElement.classList.contains('dark') ? 'fas fa-moon text-indigo-400' : 'far fa-sun');
            </script>

            <button class="relative text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <i class="far fa-bell text-lg"></i>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>

            <!-- User dropdown -->
            <div class="relative" id="userDropdown">
                <button onclick="document.getElementById('userMenu').classList.toggle('hidden')"
                        class="flex items-center space-x-3 pl-3 border-l border-gray-200 cursor-pointer hover:bg-gray-50 rounded-lg px-2 py-1 transition-colors">
                    <img id="navbarAvatar" src="<?= $_avatarUrl ?>"
                        alt="Avatar" class="w-9 h-9 rounded-full ring-2 ring-white object-cover">
                    <div class="hidden lg:block text-left">
                        <p class="text-sm font-medium text-gray-900"><?= $_displayName ?></p>
                        <p class="text-xs text-gray-500"><?= $_roleName ?></p>
                    </div>
                    <i class="fas fa-chevron-down text-xs text-gray-400 hidden lg:block"></i>
                </button>

                <!-- Dropdown menu -->
                <div id="userMenu" class="hidden absolute right-0 top-full mt-2 w-56 bg-white rounded-xl shadow-lg border border-gray-200 py-2 z-50">
                    <div class="px-4 py-2 border-b border-gray-100">
                        <p class="text-sm font-medium text-gray-900"><?= $_displayName ?></p>
                        <p class="text-xs text-gray-500"><?= $_user ? htmlspecialchars($_user['email']) : '' ?></p>
                    </div>
                    <a href="<?= $_B ?>/admin/profile.php" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-user-circle text-gray-400 w-4"></i> โปรไฟล์ของฉัน
                    </a>
                    <a href="<?= $_B ?>/admin/settings.php" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-cog text-gray-400 w-4"></i> ตั้งค่าระบบ
                    </a>
                    <a href="<?= $_B ?>/admin/audit-log.php" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-history text-gray-400 w-4"></i> ประวัติการใช้งาน
                    </a>
                    <div class="border-t border-gray-100 mt-1 pt-1">
                        <a href="<?= $_B ?>/logout.php" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                            <i class="fas fa-sign-out-alt text-red-400 w-4"></i> ออกจากระบบ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('userDropdown');
    const menu = document.getElementById('userMenu');
    if (dropdown && menu && !dropdown.contains(e.target)) {
        menu.classList.add('hidden');
    }
});

function toggleTheme() {
    const html = document.documentElement;
    const icon = document.getElementById('themeIcon');
    const isDark = html.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    icon.className = 'text-lg ' + (isDark ? 'fas fa-moon text-indigo-400' : 'far fa-sun');
}
</script>
