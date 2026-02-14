<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$db = getDB();
$B = BASE_URL;

// Handle export actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Export Markers to CSV
    if ($action === 'export_markers_csv') {
        $layerId = isset($_POST['layer_id']) ? (int)$_POST['layer_id'] : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        
        $where = [];
        if ($layerId > 0) $where[] = "m.layer_id = {$layerId}";
        if ($status) $where[] = "m.status = '{$status}'";
        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT m.id, m.title, m.description, m.latitude, m.longitude, m.status, m.properties, 
                       l.layer_name, m.created_at, m.updated_at
                FROM gis_markers m
                JOIN gis_layers l ON l.id = m.layer_id
                {$whereSQL}
                ORDER BY m.id";
        
        $markers = $db->query($sql)->fetchAll();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="markers_export_' . date('Y-m-d_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        fputcsv($output, ['ID', 'Title', 'Description', 'Latitude', 'Longitude', 'Status', 'Layer', 'Properties', 'Created', 'Updated']);
        
        foreach ($markers as $marker) {
            fputcsv($output, [
                $marker['id'],
                $marker['title'],
                $marker['description'],
                $marker['latitude'],
                $marker['longitude'],
                $marker['status'],
                $marker['layer_name'],
                $marker['properties'],
                $marker['created_at'],
                $marker['updated_at']
            ]);
        }
        
        fclose($output);
        exit;
    }

    // Export Markers to JSON
    if ($action === 'export_markers_json') {
        $layerId = isset($_POST['layer_id']) ? (int)$_POST['layer_id'] : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        $format = isset($_POST['format']) ? $_POST['format'] : 'standard';
        
        $where = [];
        if ($layerId > 0) $where[] = "m.layer_id = {$layerId}";
        if ($status) $where[] = "m.status = '{$status}'";
        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT m.*, l.layer_name, l.marker_color, l.icon_class
                FROM gis_markers m
                JOIN gis_layers l ON l.id = m.layer_id
                {$whereSQL}
                ORDER BY m.id";
        
        $markers = $db->query($sql)->fetchAll();
        
        // Decode properties
        foreach ($markers as &$marker) {
            $marker['properties'] = json_decode($marker['properties'], true) ?? [];
        }
        
        if ($format === 'geojson') {
            // GeoJSON format
            $features = [];
            foreach ($markers as $marker) {
                $features[] = [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [(float)$marker['longitude'], (float)$marker['latitude']]
                    ],
                    'properties' => [
                        'id' => $marker['id'],
                        'title' => $marker['title'],
                        'description' => $marker['description'],
                        'status' => $marker['status'],
                        'layer_name' => $marker['layer_name'],
                        'marker_color' => $marker['marker_color'],
                        'icon_class' => $marker['icon_class'],
                        'properties' => $marker['properties']
                    ]
                ];
            }
            
            $exportData = [
                'type' => 'FeatureCollection',
                'features' => $features
            ];
        } else {
            // Standard JSON format
            $exportData = [
                'exported_at' => date('Y-m-d H:i:s'),
                'total_markers' => count($markers),
                'filters' => [
                    'layer_id' => $layerId ?: 'all',
                    'status' => $status ?: 'all'
                ],
                'markers' => $markers
            ];
        }
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="markers_export_' . date('Y-m-d_His') . '.json"');
        
        echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Export Layers to CSV
    if ($action === 'export_layers_csv') {
        $sql = "SELECT l.*, c.name as category_name, 
                       (SELECT COUNT(*) FROM gis_markers WHERE layer_id = l.id) as marker_count
                FROM gis_layers l
                LEFT JOIN gis_categories c ON c.id = l.category_id
                ORDER BY l.sort_order";
        
        $layers = $db->query($sql)->fetchAll();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="layers_export_' . date('Y-m-d_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        fputcsv($output, ['ID', 'Layer Name', 'Slug', 'Category', 'Icon', 'Color', 'Shape', 'Visible', 'Sort Order', 'Marker Count']);
        
        foreach ($layers as $layer) {
            fputcsv($output, [
                $layer['id'],
                $layer['layer_name'],
                $layer['layer_slug'],
                $layer['category_name'],
                $layer['icon_class'],
                $layer['marker_color'],
                $layer['marker_shape'],
                $layer['is_visible'] ? 'Yes' : 'No',
                $layer['sort_order'],
                $layer['marker_count']
            ]);
        }
        
        fclose($output);
        exit;
    }

    // Export Layers to JSON
    if ($action === 'export_layers_json') {
        $sql = "SELECT l.*, c.name as category_name,
                       (SELECT COUNT(*) FROM gis_markers WHERE layer_id = l.id) as marker_count
                FROM gis_layers l
                LEFT JOIN gis_categories c ON c.id = l.category_id
                ORDER BY l.sort_order";
        
        $layers = $db->query($sql)->fetchAll();
        
        $exportData = [
            'exported_at' => date('Y-m-d H:i:s'),
            'total_layers' => count($layers),
            'layers' => $layers
        ];
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="layers_export_' . date('Y-m-d_His') . '.json"');
        
        echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Export Full Database Backup (JSON)
    if ($action === 'export_full_backup') {
        $categories = $db->query("SELECT * FROM gis_categories ORDER BY sort_order")->fetchAll();
        $layers = $db->query("SELECT * FROM gis_layers ORDER BY sort_order")->fetchAll();
        
        $markers = $db->query("SELECT * FROM gis_markers ORDER BY layer_id, id")->fetchAll();
        foreach ($markers as &$marker) {
            $marker['properties'] = json_decode($marker['properties'], true) ?? [];
        }
        
        $exportData = [
            'backup_info' => [
                'exported_at' => date('Y-m-d H:i:s'),
                'database_name' => DB_NAME,
                'version' => '1.0'
            ],
            'statistics' => [
                'total_categories' => count($categories),
                'total_layers' => count($layers),
                'total_markers' => count($markers)
            ],
            'data' => [
                'categories' => $categories,
                'layers' => $layers,
                'markers' => $markers
            ]
        ];
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="rangsit_cdp_backup_' . date('Y-m-d_His') . '.json"');
        
        echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Export to KML (Google Earth)
    if ($action === 'export_kml') {
        $layerId = isset($_POST['layer_id']) ? (int)$_POST['layer_id'] : 0;
        
        $where = $layerId > 0 ? "WHERE m.layer_id = {$layerId}" : '';
        
        $sql = "SELECT m.*, l.layer_name, l.marker_color
                FROM gis_markers m
                JOIN gis_layers l ON l.id = m.layer_id
                {$where}
                ORDER BY m.id";
        
        $markers = $db->query($sql)->fetchAll();
        
        header('Content-Type: application/vnd.google-earth.kml+xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="markers_export_' . date('Y-m-d_His') . '.kml"');
        
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<kml xmlns="http://www.opengis.net/kml/2.2">' . "\n";
        echo '<Document>' . "\n";
        echo '<name>Rangsit CDP GIS Export</name>' . "\n";
        echo '<description>Exported from Rangsit Community Data Platform</description>' . "\n";
        
        foreach ($markers as $marker) {
            echo '<Placemark>' . "\n";
            echo '<name>' . htmlspecialchars($marker['title']) . '</name>' . "\n";
            echo '<description>' . htmlspecialchars($marker['description']) . '</description>' . "\n";
            echo '<Point>' . "\n";
            echo '<coordinates>' . $marker['longitude'] . ',' . $marker['latitude'] . ',0</coordinates>' . "\n";
            echo '</Point>' . "\n";
            echo '</Placemark>' . "\n";
        }
        
        echo '</Document>' . "\n";
        echo '</kml>';
        exit;
    }
}

// Get statistics
$stats = [
    'markers' => $db->query("SELECT COUNT(*) as count FROM gis_markers")->fetch()['count'],
    'layers' => $db->query("SELECT COUNT(*) as count FROM gis_layers")->fetch()['count'],
    'categories' => $db->query("SELECT COUNT(*) as count FROM gis_categories")->fetch()['count'],
    'active_markers' => $db->query("SELECT COUNT(*) as count FROM gis_markers WHERE status='active'")->fetch()['count']
];

// Get layers for dropdown
$layers = $db->query("SELECT id, layer_name, marker_color FROM gis_layers ORDER BY sort_order")->fetchAll();
?>
<?php include __DIR__ . "/../template-layout/header.php"; ?>

<?php include __DIR__ . "/../template-layout/sidebar.php"; ?>

<div id="mainContent" class="main-expanded transition-all duration-300 min-h-screen flex flex-col">

    <?php include __DIR__ . "/../template-layout/navbar.php"; ?>

    <main class="flex-1 p-6 lg:p-8">

        <!-- Breadcrumb -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">ส่งออกข้อมูล GIS</h1>
                    <p class="text-sm text-gray-500 mt-1">ดาวน์โหลดข้อมูลในรูปแบบต่างๆ</p>
                </div>
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="<?= $B ?>/admin/index.php" class="hover:text-gray-700">หน้าหลัก</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900 font-medium">Export</span>
                </nav>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-map-marker-alt text-3xl opacity-80"></i>
                    <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded">Total</span>
                </div>
                <p class="text-3xl font-bold"><?= number_format($stats['markers']) ?></p>
                <p class="text-sm opacity-90">Markers</p>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-layer-group text-3xl opacity-80"></i>
                    <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded">Active</span>
                </div>
                <p class="text-3xl font-bold"><?= number_format($stats['layers']) ?></p>
                <p class="text-sm opacity-90">Layers</p>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-folder text-3xl opacity-80"></i>
                    <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded">Types</span>
                </div>
                <p class="text-3xl font-bold"><?= number_format($stats['categories']) ?></p>
                <p class="text-sm opacity-90">Categories</p>
            </div>

            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-check-circle text-3xl opacity-80"></i>
                    <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded">Live</span>
                </div>
                <p class="text-3xl font-bold"><?= number_format($stats['active_markers']) ?></p>
                <p class="text-sm opacity-90">Active</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Markers Export -->
            <div class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-map-marker-alt text-blue-600 mr-2"></i>
                    ส่งออกข้อมูล Markers
                </h2>

                <!-- CSV Export -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-file-csv text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">CSV Format</h3>
                            <p class="text-xs text-gray-500">Excel, Google Sheets</p>
                        </div>
                    </div>

                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="export_markers_csv">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Layer</label>
                            <select name="layer_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($layers as $layer): ?>
                                <option value="<?= $layer['id'] ?>"><?= htmlspecialchars($layer['layer_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="">ทั้งหมด</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>

                        <button type="submit" class="w-full px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors font-medium">
                            <i class="fas fa-download mr-2"></i>
                            ดาวน์โหลด CSV
                        </button>
                    </form>
                </div>

                <!-- JSON Export -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-orange-50 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-code text-orange-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">JSON Format</h3>
                            <p class="text-xs text-gray-500">API, Web Applications</p>
                        </div>
                    </div>

                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="export_markers_json">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Layer</label>
                            <select name="layer_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($layers as $layer): ?>
                                <option value="<?= $layer['id'] ?>"><?= htmlspecialchars($layer['layer_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="">ทั้งหมด</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Format</label>
                            <select name="format" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="standard">Standard JSON</option>
                                <option value="geojson">GeoJSON</option>
                            </select>
                        </div>

                        <button type="submit" class="w-full px-4 py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors font-medium">
                            <i class="fas fa-download mr-2"></i>
                            ดาวน์โหลด JSON
                        </button>
                    </form>
                </div>

                <!-- KML Export -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-red-50 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-globe text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">KML Format</h3>
                            <p class="text-xs text-gray-500">Google Earth, GPS Devices</p>
                        </div>
                    </div>

                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="export_kml">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Layer</label>
                            <select name="layer_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($layers as $layer): ?>
                                <option value="<?= $layer['id'] ?>"><?= htmlspecialchars($layer['layer_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="w-full px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors font-medium">
                            <i class="fas fa-download mr-2"></i>
                            ดาวน์โหลด KML
                        </button>
                    </form>
                </div>
            </div>

            <!-- Layers & System Export -->
            <div class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-database text-purple-600 mr-2"></i>
                    ส่งออกข้อมูลระบบ
                </h2>

                <!-- Layers CSV Export -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-layer-group text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Layers (CSV)</h3>
                            <p class="text-xs text-gray-500">ข้อมูล Layer ทั้งหมด</p>
                        </div>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="export_layers_csv">
                        <button type="submit" class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                            <i class="fas fa-download mr-2"></i>
                            ดาวน์โหลด Layers CSV
                        </button>
                    </form>
                </div>

                <!-- Layers JSON Export -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-indigo-50 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-layer-group text-indigo-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Layers (JSON)</h3>
                            <p class="text-xs text-gray-500">ข้อมูล Layer พร้อมสถิติ</p>
                        </div>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="export_layers_json">
                        <button type="submit" class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors font-medium">
                            <i class="fas fa-download mr-2"></i>
                            ดาวน์โหลด Layers JSON
                        </button>
                    </form>
                </div>

                <!-- Full Backup -->
                <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl p-6 text-white shadow-xl">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-database text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold">Full Database Backup</h3>
                            <p class="text-xs opacity-90">ข้อมูลทั้งหมดในระบบ</p>
                        </div>
                    </div>

                    <div class="bg-white bg-opacity-10 rounded-lg p-3 mb-4">
                        <p class="text-xs opacity-90 mb-2">รวมข้อมูล:</p>
                        <ul class="text-xs space-y-1 opacity-90">
                            <li><i class="fas fa-check mr-2"></i>Categories (<?= $stats['categories'] ?>)</li>
                            <li><i class="fas fa-check mr-2"></i>Layers (<?= $stats['layers'] ?>)</li>
                            <li><i class="fas fa-check mr-2"></i>Markers (<?= $stats['markers'] ?>)</li>
                        </ul>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="export_full_backup">
                        <button type="submit" class="w-full px-4 py-2.5 bg-white hover:bg-opacity-90 text-purple-700 rounded-lg transition-all font-semibold">
                            <i class="fas fa-cloud-download-alt mr-2"></i>
                            สำรองข้อมูลทั้งหมด
                        </button>
                    </form>
                </div>

                <!-- Quick Links -->
                <div class="bg-gray-50 rounded-xl border border-gray-200 p-6">
                    <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-link text-gray-600 mr-2"></i>
                        เครื่องมืออื่นๆ
                    </h3>
                    <div class="space-y-2">
                        <a href="<?= $B ?>/admin/import.php" class="block px-4 py-2 bg-white hover:bg-gray-50 rounded-lg text-sm transition-colors border border-gray-200">
                            <i class="fas fa-file-import mr-2 text-blue-600"></i>
                            นำเข้าข้อมูล
                        </a>
                        <a href="<?= $B ?>/admin/markers.php" class="block px-4 py-2 bg-white hover:bg-gray-50 rounded-lg text-sm transition-colors border border-gray-200">
                            <i class="fas fa-list mr-2 text-green-600"></i>
                            จัดการ Markers
                        </a>
                        <a href="<?= $B ?>/admin/layers.php" class="block px-4 py-2 bg-white hover:bg-gray-50 rounded-lg text-sm transition-colors border border-gray-200">
                            <i class="fas fa-layer-group mr-2 text-purple-600"></i>
                            จัดการ Layers
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <!-- Export Info -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
            <h3 class="font-semibold text-blue-900 mb-3 flex items-center">
                <i class="fas fa-info-circle mr-2"></i>
                คำแนะนำการใช้งาน
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-blue-800">
                <div>
                    <h4 class="font-semibold mb-1">CSV Format</h4>
                    <p class="text-xs">เหมาะสำหรับ Excel, Google Sheets หรือการวิเคราะห์ข้อมูล</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-1">JSON Format</h4>
                    <p class="text-xs">เหมาะสำหรับ API, Web Apps หรือการประมวลผลโปรแกรม</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-1">KML Format</h4>
                    <p class="text-xs">เหมาะสำหรับ Google Earth, Maps หรืออุปกรณ์ GPS</p>
                </div>
            </div>
        </div>

    </main>

    <?php include __DIR__ . "/../template-layout/footer.php"; ?>

</div>

<?php include __DIR__ . "/../template-layout/scripts.php"; ?>

</body>
</html>
