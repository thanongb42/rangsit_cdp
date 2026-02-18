<?php
/**
 * CCTV Cameras Management & Display
 * Integrated with GIS Markers system
 */

require_once __DIR__ . '/../config/database.php';
$db = getDB();
$B = BASE_URL;

// Get all cameras from gis_markers (layer_id = CCTV layer)
// This assumes cameras are stored as markers in a CCTV layer

$cameras = $db->query("
    SELECT m.*, l.marker_color, l.icon_class
    FROM gis_markers m
    JOIN gis_layers l ON l.id = m.layer_id
    WHERE l.layer_slug = 'cctv'
    AND m.status = 'active'
    ORDER BY m.title
")->fetchAll();

// Get camera data from properties JSON
$cameraList = [];
foreach ($cameras as $camera) {
    $props = json_decode($camera['properties'], true) ?? [];
    $cameraList[] = [
        'id' => $camera['id'],
        'title' => $camera['title'],
        'lat' => $camera['latitude'],
        'lng' => $camera['longitude'],
        'description' => $camera['description'],
        'stream_url' => $props['stream_url'] ?? '',
        'is_active' => $camera['status'] === 'active',
        'color' => $camera['marker_color']
    ];
}
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
                    <h1 class="text-2xl font-semibold text-gray-900">
                        <i class="fas fa-camera text-red-600 mr-2"></i>
                        จัดการกล้อง CCTV
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">CCTV Cameras Management & Monitoring</p>
                </div>
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="<?= $B ?>/admin/index.php" class="hover:text-gray-700">หน้าหลัก</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900 font-medium">CCTV</span>
                </nav>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl p-6 border border-gray-200 flex items-center gap-4">
                <div class="w-12 h-12 bg-red-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-camera text-red-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-3xl font-bold text-gray-900"><?= count($cameraList) ?></p>
                    <p class="text-sm text-gray-500">กล้องทั้งหมด</p>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6 border border-gray-200 flex items-center gap-4">
                <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-3xl font-bold text-gray-900"><?= count(array_filter($cameraList, fn($c) => $c['is_active'])) ?></p>
                    <p class="text-sm text-gray-500">ใช้งาน</p>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6 border border-gray-200 flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-map-location-dot text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-3xl font-bold text-gray-900"><?= count(array_filter($cameraList, fn($c) => !empty($c['stream_url']))) ?></p>
                    <p class="text-sm text-gray-500">มี Stream URL</p>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6 border border-gray-200 flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-signal text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-3xl font-bold text-gray-900">Ready</p>
                    <p class="text-sm text-gray-500">สถานะระบบ</p>
                </div>
            </div>
        </div>

        <!-- Cameras Table -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ชื่อกล้อง</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">พิกัด</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">รายละเอียด</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Stream URL</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">สถานะ</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($cameraList)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-video-slash text-4xl mb-2 text-gray-300"></i>
                                <p>ไม่มีกล้อง CCTV</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($cameraList as $camera): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-600">#<?= $camera['id'] ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs" style="background-color: <?= htmlspecialchars($camera['color']) ?>">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($camera['title']) ?></p>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <?= number_format($camera['lat'], 6) ?>, <?= number_format($camera['lng'], 6) ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <?= htmlspecialchars(substr($camera['description'], 0, 50)) ?>...
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php if (!empty($camera['stream_url'])): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700">
                                    <i class="fas fa-link mr-1"></i> URL
                                </span>
                                <?php else: ?>
                                <span class="text-xs text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs <?= $camera['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                                    <i class="fas fa-circle text-[6px] mr-1.5"></i>
                                    <?= $camera['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="<?= $B ?>/public/map.php?camera_id=<?= $camera['id'] ?>" 
                                       class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors" 
                                       title="ดูบนแผนที่">
                                        <i class="fas fa-map"></i>
                                    </a>
                                    <?php if (!empty($camera['stream_url'])): ?>
                                    <a href="<?= $B ?>/public/proxy_camera_stream.php?id=<?= $camera['id'] ?>" 
                                       class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors" 
                                       title="ดู Live Stream"
                                       target="_blank">
                                        <i class="fas fa-video"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="<?= $B ?>/admin/marker-form.php?id=<?= $camera['id'] ?>" 
                                       class="p-1.5 text-green-600 hover:bg-green-50 rounded transition-colors" 
                                       title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <?php include __DIR__ . "/../template-layout/footer.php"; ?>

</div>

<?php include __DIR__ . "/../template-layout/scripts.php"; ?>

</body>
</html>
