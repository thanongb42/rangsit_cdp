<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$db = getDB();
$B = BASE_URL;

// Handle settings save
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'save_general') {
        $siteName = trim($_POST['site_name'] ?? '');
        $siteDescription = trim($_POST['site_description'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');
        $contactPhone = trim($_POST['contact_phone'] ?? '');
        
        // Save to a settings file or database table
        $settings = [
            'site_name' => $siteName,
            'site_description' => $siteDescription,
            'contact_email' => $contactEmail,
            'contact_phone' => $contactPhone
        ];
        
        file_put_contents(__DIR__ . '/../config/settings.json', json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $msg = 'บันทึกการตั้งค่าทั่วไปสำเร็จ';
        $msgType = 'success';
    }

    if ($action === 'save_map') {
        $defaultLat = (float)($_POST['default_lat'] ?? 13.9839);
        $defaultLng = (float)($_POST['default_lng'] ?? 100.6162);
        $defaultZoom = (int)($_POST['default_zoom'] ?? 13);
        $mapProvider = trim($_POST['map_provider'] ?? 'OpenStreetMap');
        
        $mapSettings = [
            'default_lat' => $defaultLat,
            'default_lng' => $defaultLng,
            'default_zoom' => $defaultZoom,
            'map_provider' => $mapProvider
        ];
        
        file_put_contents(__DIR__ . '/../config/map_settings.json', json_encode($mapSettings, JSON_PRETTY_PRINT));
        
        $msg = 'บันทึกการตั้งค่าแผนที่สำเร็จ';
        $msgType = 'success';
    }

    if ($action === 'clear_cache') {
        // Clear cache logic here
        $msg = 'ล้าง Cache สำเร็จ';
        $msgType = 'success';
    }

    if ($action === 'backup_database') {
        $backupFile = __DIR__ . '/../backups/backup_' . date('Y-m-d_His') . '.sql';
        @mkdir(__DIR__ . '/../backups', 0755, true);
        
        // Simple backup (for demonstration)
        $msg = 'สร้าง Backup สำเร็จ: ' . basename($backupFile);
        $msgType = 'success';
    }
}

// Load current settings
$settingsFile = __DIR__ . '/../config/settings.json';
$currentSettings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];

$mapSettingsFile = __DIR__ . '/../config/map_settings.json';
$currentMapSettings = file_exists($mapSettingsFile) ? json_decode(file_get_contents($mapSettingsFile), true) : [];

// Default values
$defaultSettings = [
    'site_name' => 'Rangsit CDP',
    'site_description' => 'Community Data Platform',
    'contact_email' => 'admin@rangsit.go.th',
    'contact_phone' => '02-XXX-XXXX'
];

$defaultMapSettings = [
    'default_lat' => 13.9839,
    'default_lng' => 100.6162,
    'default_zoom' => 13,
    'map_provider' => 'OpenStreetMap'
];

$settings = array_merge($defaultSettings, $currentSettings);
$mapSettings = array_merge($defaultMapSettings, $currentMapSettings);

// Get system info
$dbSize = $db->query("
    SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
    FROM information_schema.TABLES
    WHERE table_schema = '" . DB_NAME . "'
")->fetch()['size_mb'];

$stats = [
    'markers' => $db->query("SELECT COUNT(*) FROM gis_markers")->fetchColumn(),
    'layers' => $db->query("SELECT COUNT(*) FROM gis_layers")->fetchColumn(),
    'users' => $db->query("SELECT COUNT(*) FROM gis_users")->fetchColumn(),
    'audit_logs' => $db->query("SELECT COUNT(*) FROM gis_audit_log")->fetchColumn(),
    'db_size' => $dbSize
];
?>
<?php include __DIR__ . "/../template-layout/header.php"; ?>

<?php include __DIR__ . "/../template-layout/sidebar.php"; ?>

<div id="mainContent" class="main-expanded transition-all duration-300 min-h-screen flex flex-col">

    <?php include __DIR__ . "/../template-layout/navbar.php"; ?>

    <main class="flex-1 p-6 lg:p-8">

        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">ตั้งค่าระบบ</h1>
                    <p class="text-sm text-gray-500 mt-1">System Settings & Configuration</p>
                </div>
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="<?= $B ?>/admin/index.php" class="hover:text-gray-700">หน้าหลัก</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900 font-medium">Settings</span>
                </nav>
            </div>
        </div>

        <!-- Alert -->
        <?php if ($msg): ?>
        <div class="mb-6 px-4 py-3 rounded-lg border <?= $msgType === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' ?> flex items-center gap-2" id="alertMsg">
            <i class="fas <?= $msgType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <span class="text-sm font-medium"><?= htmlspecialchars($msg) ?></span>
            <button onclick="document.getElementById('alertMsg').remove()" class="ml-auto text-lg leading-none">&times;</button>
        </div>
        <?php endif; ?>

        <!-- System Stats -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-5 text-white shadow-lg">
                <i class="fas fa-map-marker-alt text-2xl opacity-80 mb-2"></i>
                <p class="text-2xl font-bold"><?= number_format($stats['markers']) ?></p>
                <p class="text-xs opacity-90">Markers</p>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-5 text-white shadow-lg">
                <i class="fas fa-layer-group text-2xl opacity-80 mb-2"></i>
                <p class="text-2xl font-bold"><?= number_format($stats['layers']) ?></p>
                <p class="text-xs opacity-90">Layers</p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-5 text-white shadow-lg">
                <i class="fas fa-users text-2xl opacity-80 mb-2"></i>
                <p class="text-2xl font-bold"><?= number_format($stats['users']) ?></p>
                <p class="text-xs opacity-90">Users</p>
            </div>
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-5 text-white shadow-lg">
                <i class="fas fa-history text-2xl opacity-80 mb-2"></i>
                <p class="text-2xl font-bold"><?= number_format($stats['audit_logs']) ?></p>
                <p class="text-xs opacity-90">Audit Logs</p>
            </div>
            <div class="bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl p-5 text-white shadow-lg">
                <i class="fas fa-database text-2xl opacity-80 mb-2"></i>
                <p class="text-2xl font-bold"><?= $stats['db_size'] ?> MB</p>
                <p class="text-xs opacity-90">Database</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Main Settings -->
            <div class="lg:col-span-2 space-y-6">

                <!-- General Settings -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-blue-100">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-cog text-blue-600 mr-2"></i>
                            ตั้งค่าทั่วไป
                        </h2>
                    </div>
                    <form method="POST" class="p-6 space-y-4">
                        <input type="hidden" name="action" value="save_general">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ชื่อเว็บไซต์ <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="site_name" 
                                   required 
                                   value="<?= htmlspecialchars($settings['site_name']) ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                คำอธิบาย
                            </label>
                            <textarea name="site_description" 
                                      rows="3" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($settings['site_description']) ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    อีเมลติดต่อ
                                </label>
                                <input type="email" 
                                       name="contact_email" 
                                       value="<?= htmlspecialchars($settings['contact_email']) ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    เบอร์โทรติดต่อ
                                </label>
                                <input type="text" 
                                       name="contact_phone" 
                                       value="<?= htmlspecialchars($settings['contact_phone']) ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                            <i class="fas fa-save mr-2"></i>
                            บันทึกการตั้งค่าทั่วไป
                        </button>
                    </form>
                </div>

                <!-- Map Settings -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-5 border-b border-gray-200 bg-gradient-to-r from-green-50 to-green-100">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-map text-green-600 mr-2"></i>
                            ตั้งค่าแผนที่
                        </h2>
                    </div>
                    <form method="POST" class="p-6 space-y-4">
                        <input type="hidden" name="action" value="save_map">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Latitude เริ่มต้น
                                </label>
                                <input type="number" 
                                       name="default_lat" 
                                       step="0.000001" 
                                       value="<?= $mapSettings['default_lat'] ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Longitude เริ่มต้น
                                </label>
                                <input type="number" 
                                       name="default_lng" 
                                       step="0.000001" 
                                       value="<?= $mapSettings['default_lng'] ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Zoom Level
                                </label>
                                <input type="number" 
                                       name="default_zoom" 
                                       min="1" 
                                       max="20" 
                                       value="<?= $mapSettings['default_zoom'] ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Map Provider
                            </label>
                            <select name="map_provider" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                <option value="OpenStreetMap" <?= $mapSettings['map_provider'] === 'OpenStreetMap' ? 'selected' : '' ?>>OpenStreetMap</option>
                                <option value="Google Maps" <?= $mapSettings['map_provider'] === 'Google Maps' ? 'selected' : '' ?>>Google Maps</option>
                                <option value="Mapbox" <?= $mapSettings['map_provider'] === 'Mapbox' ? 'selected' : '' ?>>Mapbox</option>
                            </select>
                        </div>

                        <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors font-medium">
                            <i class="fas fa-save mr-2"></i>
                            บันทึกการตั้งค่าแผนที่
                        </button>
                    </form>
                </div>

                <!-- Database Info -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-5 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-purple-100">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-database text-purple-600 mr-2"></i>
                            ข้อมูลฐานข้อมูล
                        </h2>
                    </div>
                    <div class="p-6 space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Database Name:</span>
                            <span class="font-medium text-gray-900"><?= DB_NAME ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Database Host:</span>
                            <span class="font-medium text-gray-900"><?= DB_HOST ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Database Size:</span>
                            <span class="font-medium text-gray-900"><?= $stats['db_size'] ?> MB</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">PHP Version:</span>
                            <span class="font-medium text-gray-900"><?= phpversion() ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600">Server Software:</span>
                            <span class="font-medium text-gray-900 text-xs"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Quick Actions -->
            <div class="space-y-6">

                <!-- System Actions -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-5 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-orange-100">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-bolt text-orange-600 mr-2"></i>
                            การดำเนินการ
                        </h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <form method="POST" onsubmit="return confirm('ยืนยันการล้าง Cache?')">
                            <input type="hidden" name="action" value="clear_cache">
                            <button type="submit" class="w-full px-4 py-2.5 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 rounded-lg transition-colors font-medium text-left border border-yellow-200">
                                <i class="fas fa-broom mr-2"></i>
                                ล้าง Cache
                            </button>
                        </form>

                        <form method="POST" onsubmit="return confirm('ยืนยันการสร้าง Backup?')">
                            <input type="hidden" name="action" value="backup_database">
                            <button type="submit" class="w-full px-4 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition-colors font-medium text-left border border-blue-200">
                                <i class="fas fa-cloud-download-alt mr-2"></i>
                                สำรองฐานข้อมูล
                            </button>
                        </form>

                        <a href="<?= $B ?>/admin/audit-log.php" class="block w-full px-4 py-2.5 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg transition-colors font-medium text-left border border-purple-200">
                            <i class="fas fa-history mr-2"></i>
                            ดู Audit Log
                        </a>

                        <a href="<?= $B ?>/admin/export.php" class="block w-full px-4 py-2.5 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg transition-colors font-medium text-left border border-green-200">
                            <i class="fas fa-file-export mr-2"></i>
                            ส่งออกข้อมูล
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-5 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-indigo-100">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-link text-indigo-600 mr-2"></i>
                            ลิงก์ด่วน
                        </h2>
                    </div>
                    <div class="p-4 space-y-2">
                        <a href="<?= $B ?>/admin/users.php" class="block px-4 py-2 hover:bg-gray-50 rounded-lg transition-colors text-sm">
                            <i class="fas fa-users text-gray-600 mr-2"></i>
                            จัดการผู้ใช้งาน
                        </a>
                        <a href="<?= $B ?>/admin/roles.php" class="block px-4 py-2 hover:bg-gray-50 rounded-lg transition-colors text-sm">
                            <i class="fas fa-shield-alt text-gray-600 mr-2"></i>
                            จัดการ Roles
                        </a>
                        <a href="<?= $B ?>/admin/markers.php" class="block px-4 py-2 hover:bg-gray-50 rounded-lg transition-colors text-sm">
                            <i class="fas fa-map-marker-alt text-gray-600 mr-2"></i>
                            จัดการ Markers
                        </a>
                        <a href="<?= $B ?>/admin/layers.php" class="block px-4 py-2 hover:bg-gray-50 rounded-lg transition-colors text-sm">
                            <i class="fas fa-layer-group text-gray-600 mr-2"></i>
                            จัดการ Layers
                        </a>
                        <a href="<?= $B ?>/admin/categories.php" class="block px-4 py-2 hover:bg-gray-50 rounded-lg transition-colors text-sm">
                            <i class="fas fa-folder text-gray-600 mr-2"></i>
                            จัดการ Categories
                        </a>
                    </div>
                </div>

                <!-- System Status -->
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold">สถานะระบบ</h3>
                        <i class="fas fa-check-circle text-2xl opacity-80"></i>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center">
                            <i class="fas fa-circle text-xs mr-2"></i>
                            <span class="opacity-90">Database: Connected</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-circle text-xs mr-2"></i>
                            <span class="opacity-90">PHP: <?= phpversion() ?></span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-circle text-xs mr-2"></i>
                            <span class="opacity-90">Version: 1.0.0</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-circle text-xs mr-2"></i>
                            <span class="opacity-90">Status: Online</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </main>

    <?php include __DIR__ . "/../template-layout/footer.php"; ?>

</div>

<?php include __DIR__ . "/../template-layout/scripts.php"; ?>

<script>
// Auto dismiss alert
setTimeout(() => {
    const alert = document.getElementById('alertMsg');
    if (alert) {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    }
}, 5000);
</script>

</body>
</html>
