<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$db = getDB();

// Stats
$totalMarkers   = $db->query("SELECT COUNT(*) FROM gis_markers")->fetchColumn();
$activeMarkers  = $db->query("SELECT COUNT(*) FROM gis_markers WHERE status='active'")->fetchColumn();
$totalLayers    = $db->query("SELECT COUNT(*) FROM gis_layers")->fetchColumn();
$totalCategories= $db->query("SELECT COUNT(*) FROM gis_categories")->fetchColumn();
$totalUsers     = $db->query("SELECT COUNT(*) FROM gis_users WHERE is_active=1")->fetchColumn();

// Layers with marker count
$layers = $db->query("
    SELECT l.*, c.name as category_name, c.color as category_color,
           COUNT(m.id) as marker_count,
           SUM(CASE WHEN m.status='active' THEN 1 ELSE 0 END) as active_count,
           SUM(CASE WHEN m.status='inactive' THEN 1 ELSE 0 END) as inactive_count,
           SUM(CASE WHEN m.status='maintenance' THEN 1 ELSE 0 END) as maintenance_count
    FROM gis_layers l
    LEFT JOIN gis_categories c ON c.id = l.category_id
    LEFT JOIN gis_markers m ON m.layer_id = l.id
    GROUP BY l.id
    ORDER BY l.sort_order
")->fetchAll();

// Recent markers
$recentMarkers = $db->query("
    SELECT m.*, l.layer_name, l.marker_color, l.icon_class
    FROM gis_markers m
    JOIN gis_layers l ON l.id = m.layer_id
    ORDER BY m.created_at DESC
    LIMIT 10
")->fetchAll();

// Data for chart — markers per layer
$chartLabels = [];
$chartData   = [];
$chartColors = [];
foreach ($layers as $l) {
    $chartLabels[] = $l['layer_name'];
    $chartData[]   = (int)$l['marker_count'];
    $chartColors[] = $l['marker_color'];
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
                    <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
                    <p class="text-sm text-gray-500 mt-1">Community Data Platform — ภาพรวมข้อมูลเชิงพื้นที่</p>
                </div>
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="index.php" class="hover:text-gray-700">Home</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900 font-medium">Dashboard</span>
                </nav>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Markers -->
            <div class="bg-white rounded-xl p-6 border border-gray-200 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="w-12 h-12 bg-indigo-50 rounded-lg flex items-center justify-center mb-3">
                            <i class="fas fa-map-pin text-indigo-600 text-xl"></i>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 mb-1"><?= number_format($totalMarkers) ?></p>
                        <p class="text-sm text-gray-500 font-medium">Total Markers</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700">
                            <i class="fas fa-check-circle mr-1"></i> <?= number_format($activeMarkers) ?> Active
                        </span>
                    </div>
                </div>
            </div>

            <!-- Layers -->
            <div class="bg-white rounded-xl p-6 border border-gray-200 hover:shadow-lg transition-shadow">
                <div>
                    <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center mb-3">
                        <i class="fas fa-layer-group text-blue-600 text-xl"></i>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mb-1"><?= number_format($totalLayers) ?></p>
                    <p class="text-sm text-gray-500 font-medium">Layers</p>
                </div>
            </div>

            <!-- Categories -->
            <div class="bg-white rounded-xl p-6 border border-gray-200 hover:shadow-lg transition-shadow">
                <div>
                    <div class="w-12 h-12 bg-amber-50 rounded-lg flex items-center justify-center mb-3">
                        <i class="fas fa-folder-open text-amber-600 text-xl"></i>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mb-1"><?= number_format($totalCategories) ?></p>
                    <p class="text-sm text-gray-500 font-medium">Categories</p>
                </div>
            </div>

            <!-- Users -->
            <div class="bg-white rounded-xl p-6 border border-gray-200 hover:shadow-lg transition-shadow">
                <div>
                    <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center mb-3">
                        <i class="fas fa-users text-green-600 text-xl"></i>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mb-1"><?= number_format($totalUsers) ?></p>
                    <p class="text-sm text-gray-500 font-medium">Active Users</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

            <!-- Chart -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Markers per Layer</h3>
                    <p class="text-sm text-gray-500 mt-1">จำนวนหมุดในแต่ละชั้นข้อมูล</p>
                </div>
                <div class="p-6">
                    <canvas id="markersChart" height="260"></canvas>
                </div>
            </div>

            <!-- Layer Summary -->
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Layer Summary</h3>
                    <p class="text-sm text-gray-500 mt-1">สรุปชั้นข้อมูลทั้งหมด</p>
                </div>
                <div class="p-4">
                    <?php foreach ($layers as $layer): ?>
                    <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                             style="background:<?= htmlspecialchars($layer['marker_color']) ?>20;">
                            <i class="fas <?= htmlspecialchars($layer['icon_class']) ?>"
                               style="color:<?= htmlspecialchars($layer['marker_color']) ?>;"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate"><?= htmlspecialchars($layer['layer_name']) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($layer['category_name'] ?? '-') ?></p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-lg font-bold text-gray-900"><?= number_format($layer['marker_count']) ?></p>
                            <div class="flex gap-1 justify-end">
                                <?php if ($layer['active_count'] > 0): ?>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-green-50 text-green-700"><?= $layer['active_count'] ?> active</span>
                                <?php endif; ?>
                                <?php if ($layer['maintenance_count'] > 0): ?>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-yellow-50 text-yellow-700"><?= $layer['maintenance_count'] ?> maint</span>
                                <?php endif; ?>
                                <?php if ($layer['inactive_count'] > 0): ?>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-red-50 text-red-700"><?= $layer['inactive_count'] ?> off</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Markers Table -->
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Recent Markers</h3>
                        <p class="text-sm text-gray-500 mt-1">หมุดที่เพิ่มล่าสุด 10 รายการ</p>
                    </div>
                    <a href="markers.php" class="inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                        <i class="fas fa-list mr-2"></i> View All
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Layer</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Coordinates</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php foreach ($recentMarkers as $marker): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900">#<?= $marker['id'] ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:<?= htmlspecialchars($marker['marker_color']) ?>"></span>
                                    <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($marker['title']) ?></span>
                                </div>
                                <?php if ($marker['description']): ?>
                                    <p class="text-xs text-gray-500 mt-0.5 max-w-xs truncate"><?= htmlspecialchars($marker['description']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border"
                                      style="color:<?= htmlspecialchars($marker['marker_color']) ?>;
                                             border-color:<?= htmlspecialchars($marker['marker_color']) ?>40;
                                             background:<?= htmlspecialchars($marker['marker_color']) ?>10;">
                                    <i class="fas <?= htmlspecialchars($marker['icon_class']) ?> text-[10px]"></i>
                                    <?= htmlspecialchars($marker['layer_name']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs text-gray-500 font-mono">
                                    <?= number_format($marker['latitude'], 6) ?>, <?= number_format($marker['longitude'], 6) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $statusClass = match($marker['status']) {
                                    'active' => 'bg-green-50 text-green-700 border-green-200',
                                    'inactive' => 'bg-red-50 text-red-700 border-red-200',
                                    'maintenance' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                };
                                ?>
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium border <?= $statusClass ?>">
                                    <?= ucfirst($marker['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('d M Y', strtotime($marker['created_at'])) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <?php include __DIR__ . "/../template-layout/footer.php"; ?>

</div>

<?php include __DIR__ . "/../template-layout/scripts.php"; ?>

<script>
// Markers per Layer Chart
const ctx = document.getElementById('markersChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
            label: 'Markers',
            data: <?= json_encode($chartData) ?>,
            backgroundColor: <?= json_encode(array_map(fn($c) => $c . '40', $chartColors)) ?>,
            borderColor: <?= json_encode($chartColors) ?>,
            borderWidth: 2,
            borderRadius: 8,
            barPercentage: 0.5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                titleFont: { size: 13, weight: '600' },
                bodyFont: { size: 12 },
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    label: ctx => ` ${ctx.parsed.y} markers`
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0, font: { size: 12 }, color: '#94a3b8' },
                grid: { color: '#f1f5f9' }
            },
            x: {
                ticks: { font: { size: 11 }, color: '#64748b', maxRotation: 0 },
                grid: { display: false }
            }
        }
    }
});
</script>
