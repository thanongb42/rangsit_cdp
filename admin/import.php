<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$db = getDB();
$B = BASE_URL;

// Handle file upload and import
$msg = '';
$msgType = '';
$importResults = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Import CSV
    if ($action === 'import_csv' && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file'];
        $layerId = (int)$_POST['layer_id'];
        
        if ($file['error'] === UPLOAD_ERR_OK && $layerId > 0) {
            $tmpName = $file['tmp_name'];
            $handle = fopen($tmpName, 'r');
            
            if ($handle !== false) {
                $header = fgetcsv($handle); // Skip header row
                $imported = 0;
                $skipped = 0;
                $errors = [];
                
                $db->beginTransaction();
                try {
                    while (($row = fgetcsv($handle)) !== false) {
                        // Expected format: title, description, latitude, longitude, status, properties_json
                        if (count($row) < 4) {
                            $skipped++;
                            continue;
                        }
                        
                        $title = trim($row[0] ?? '');
                        $description = trim($row[1] ?? '');
                        $latitude = (float)($row[2] ?? 0);
                        $longitude = (float)($row[3] ?? 0);
                        $status = trim($row[4] ?? 'active');
                        $properties = trim($row[5] ?? '{}');
                        
                        if (empty($title) || !$latitude || !$longitude) {
                            $skipped++;
                            continue;
                        }
                        
                        // Validate status
                        if (!in_array($status, ['active', 'inactive', 'maintenance'])) {
                            $status = 'active';
                        }
                        
                        // Insert marker
                        $sql = "INSERT INTO gis_markers 
                                (layer_id, title, description, latitude, longitude, coordinates, properties, status) 
                                VALUES (?, ?, ?, ?, ?, ST_GeomFromText(?, 4326), ?, ?)";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            $layerId,
                            $title,
                            $description,
                            $latitude,
                            $longitude,
                            "POINT({$longitude} {$latitude})",
                            $properties,
                            $status
                        ]);
                        $imported++;
                    }
                    
                    $db->commit();
                    $msg = "นำเข้าสำเร็จ {$imported} รายการ" . ($skipped > 0 ? ", ข้าม {$skipped} รายการ" : '');
                    $msgType = 'success';
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    $msg = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                    $msgType = 'error';
                }
                
                fclose($handle);
            }
        } else {
            $msg = 'กรุณาเลือกไฟล์ CSV และ Layer';
            $msgType = 'error';
        }
    }

    // Import JSON
    if ($action === 'import_json' && isset($_FILES['json_file'])) {
        $file = $_FILES['json_file'];
        $layerId = (int)$_POST['layer_id'];
        
        if ($file['error'] === UPLOAD_ERR_OK && $layerId > 0) {
            $jsonContent = file_get_contents($file['tmp_name']);
            $data = json_decode($jsonContent, true);
            
            if ($data && isset($data['markers']) && is_array($data['markers'])) {
                $imported = 0;
                $skipped = 0;
                
                $db->beginTransaction();
                try {
                    foreach ($data['markers'] as $marker) {
                        $title = $marker['title'] ?? '';
                        $description = $marker['description'] ?? '';
                        $latitude = (float)($marker['latitude'] ?? 0);
                        $longitude = (float)($marker['longitude'] ?? 0);
                        $status = $marker['status'] ?? 'active';
                        $properties = json_encode($marker['properties'] ?? []);
                        
                        if (empty($title) || !$latitude || !$longitude) {
                            $skipped++;
                            continue;
                        }
                        
                        if (!in_array($status, ['active', 'inactive', 'maintenance'])) {
                            $status = 'active';
                        }
                        
                        $sql = "INSERT INTO gis_markers 
                                (layer_id, title, description, latitude, longitude, coordinates, properties, status) 
                                VALUES (?, ?, ?, ?, ?, ST_GeomFromText(?, 4326), ?, ?)";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            $layerId,
                            $title,
                            $description,
                            $latitude,
                            $longitude,
                            "POINT({$longitude} {$latitude})",
                            $properties,
                            $status
                        ]);
                        $imported++;
                    }
                    
                    $db->commit();
                    $msg = "นำเข้าสำเร็จ {$imported} รายการ" . ($skipped > 0 ? ", ข้าม {$skipped} รายการ" : '');
                    $msgType = 'success';
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    $msg = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                    $msgType = 'error';
                }
            } else {
                $msg = 'รูปแบบ JSON ไม่ถูกต้อง';
                $msgType = 'error';
            }
        } else {
            $msg = 'กรุณาเลือกไฟล์ JSON และ Layer';
            $msgType = 'error';
        }
    }

    // Export to CSV
    if ($action === 'export_csv') {
        $layerId = isset($_POST['export_layer_id']) ? (int)$_POST['export_layer_id'] : 0;
        
        $sql = "SELECT title, description, latitude, longitude, status, properties FROM gis_markers";
        if ($layerId > 0) {
            $sql .= " WHERE layer_id = {$layerId}";
        }
        $sql .= " ORDER BY id";
        
        $markers = $db->query($sql)->fetchAll();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="markers_export_' . date('Y-m-d_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['title', 'description', 'latitude', 'longitude', 'status', 'properties']);
        
        foreach ($markers as $marker) {
            fputcsv($output, [
                $marker['title'],
                $marker['description'],
                $marker['latitude'],
                $marker['longitude'],
                $marker['status'],
                $marker['properties']
            ]);
        }
        
        fclose($output);
        exit;
    }

    // Export to JSON
    if ($action === 'export_json') {
        $layerId = isset($_POST['export_layer_id']) ? (int)$_POST['export_layer_id'] : 0;
        
        $sql = "SELECT id, layer_id, title, description, latitude, longitude, status, properties FROM gis_markers";
        if ($layerId > 0) {
            $sql .= " WHERE layer_id = {$layerId}";
        }
        $sql .= " ORDER BY id";
        
        $markers = $db->query($sql)->fetchAll();
        
        // Decode properties
        foreach ($markers as &$marker) {
            $marker['properties'] = json_decode($marker['properties'], true) ?? [];
        }
        
        $exportData = [
            'exported_at' => date('Y-m-d H:i:s'),
            'total_markers' => count($markers),
            'markers' => $markers
        ];
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="markers_export_' . date('Y-m-d_His') . '.json"');
        
        echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Get all layers for dropdown
$layers = $db->query("SELECT id, layer_name, marker_color FROM gis_layers ORDER BY sort_order")->fetchAll();

// Get stats
$stats = $db->query("SELECT COUNT(*) as total FROM gis_markers")->fetch();
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
                    <h1 class="text-2xl font-semibold text-gray-900">นำเข้า/ส่งออกข้อมูล</h1>
                    <p class="text-sm text-gray-500 mt-1">นำเข้าข้อมูล GIS จาก CSV/JSON หรือส่งออกข้อมูล</p>
                </div>
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="<?= $B ?>/admin/index.php" class="hover:text-gray-700">หน้าหลัก</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900 font-medium">Import/Export</span>
                </nav>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($msg): ?>
        <div class="mb-6 p-4 rounded-lg <?= $msgType === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
            <div class="flex items-center">
                <i class="fas <?= $msgType === 'success' ? 'fa-check-circle text-green-600' : 'fa-exclamation-circle text-red-600' ?> mr-2"></i>
                <span class="<?= $msgType === 'success' ? 'text-green-800' : 'text-red-800' ?>"><?= htmlspecialchars($msg) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-map-marker-alt text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['total']) ?></p>
                        <p class="text-sm text-gray-500">หมุดทั้งหมด</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-layer-group text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900"><?= count($layers) ?></p>
                        <p class="text-sm text-gray-500">Layers</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-database text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900">CSV/JSON</p>
                        <p class="text-sm text-gray-500">รองรับ</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Import Section -->
            <div>
                <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-file-import text-blue-600 mr-2"></i>
                    นำเข้าข้อมูล
                </h2>

                <!-- Import CSV -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-file-csv text-green-600 mr-2"></i>
                        นำเข้าจาก CSV
                    </h3>
                    
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="import_csv">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                เลือก Layer <span class="text-red-500">*</span>
                            </label>
                            <select name="layer_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">-- เลือก Layer --</option>
                                <?php foreach ($layers as $layer): ?>
                                <option value="<?= $layer['id'] ?>"><?= htmlspecialchars($layer['layer_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ไฟล์ CSV <span class="text-red-500">*</span>
                            </label>
                            <input type="file" 
                                   name="csv_file" 
                                   accept=".csv" 
                                   required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">
                                รูปแบบ: title, description, latitude, longitude, status, properties
                            </p>
                        </div>

                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-upload mr-2"></i>
                            นำเข้า CSV
                        </button>
                    </form>

                    <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-600 font-semibold mb-2">ตัวอย่างรูปแบบ CSV:</p>
                        <pre class="text-xs text-gray-700 overflow-x-auto">title,description,latitude,longitude,status,properties
จุดที่ 1,คำอธิบาย,13.986146,100.608983,active,"{}"</pre>
                    </div>
                </div>

                <!-- Import JSON -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-code text-orange-600 mr-2"></i>
                        นำเข้าจาก JSON
                    </h3>
                    
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="import_json">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                เลือก Layer <span class="text-red-500">*</span>
                            </label>
                            <select name="layer_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">-- เลือก Layer --</option>
                                <?php foreach ($layers as $layer): ?>
                                <option value="<?= $layer['id'] ?>"><?= htmlspecialchars($layer['layer_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ไฟล์ JSON <span class="text-red-500">*</span>
                            </label>
                            <input type="file" 
                                   name="json_file" 
                                   accept=".json" 
                                   required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>

                        <button type="submit" class="w-full px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-upload mr-2"></i>
                            นำเข้า JSON
                        </button>
                    </form>

                    <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-600 font-semibold mb-2">ตัวอย่างรูปแบบ JSON:</p>
                        <pre class="text-xs text-gray-700 overflow-x-auto">{
  "markers": [
    {
      "title": "จุดที่ 1",
      "description": "คำอธิบาย",
      "latitude": 13.986146,
      "longitude": 100.608983,
      "status": "active",
      "properties": {}
    }
  ]
}</pre>
                    </div>
                </div>
            </div>

            <!-- Export Section -->
            <div>
                <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-file-export text-purple-600 mr-2"></i>
                    ส่งออกข้อมูล
                </h2>

                <!-- Export CSV -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-file-csv text-green-600 mr-2"></i>
                        ส่งออกเป็น CSV
                    </h3>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="export_csv">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                เลือก Layer (ไม่เลือก = ทั้งหมด)
                            </label>
                            <select name="export_layer_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($layers as $layer): ?>
                                <option value="<?= $layer['id'] ?>"><?= htmlspecialchars($layer['layer_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-download mr-2"></i>
                            ดาวน์โหลด CSV
                        </button>
                    </form>

                    <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            ไฟล์ CSV เปิดได้ด้วย Excel, Google Sheets
                        </p>
                    </div>
                </div>

                <!-- Export JSON -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-code text-orange-600 mr-2"></i>
                        ส่งออกเป็น JSON
                    </h3>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="export_json">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                เลือก Layer (ไม่เลือก = ทั้งหมด)
                            </label>
                            <select name="export_layer_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($layers as $layer): ?>
                                <option value="<?= $layer['id'] ?>"><?= htmlspecialchars($layer['layer_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="w-full px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-download mr-2"></i>
                            ดาวน์โหลด JSON
                        </button>
                    </form>

                    <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            ไฟล์ JSON สำหรับ API หรือระบบอื่น
                        </p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl border border-blue-200 p-6 mt-6">
                    <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-bolt text-yellow-600 mr-2"></i>
                        เครื่องมือเพิ่มเติม
                    </h3>
                    <div class="space-y-2">
                        <a href="<?= $B ?>/admin/markers.php" class="block px-4 py-2 bg-white hover:bg-gray-50 rounded-lg text-sm transition-colors border border-gray-200">
                            <i class="fas fa-list mr-2 text-gray-600"></i>
                            จัดการหมุดทั้งหมด
                        </a>
                        <a href="<?= $B ?>/admin/marker-form.php" class="block px-4 py-2 bg-white hover:bg-gray-50 rounded-lg text-sm transition-colors border border-gray-200">
                            <i class="fas fa-plus-circle mr-2 text-gray-600"></i>
                            เพิ่มหมุดใหม่
                        </a>
                        <a href="<?= $B ?>/admin/layers.php" class="block px-4 py-2 bg-white hover:bg-gray-50 rounded-lg text-sm transition-colors border border-gray-200">
                            <i class="fas fa-layer-group mr-2 text-gray-600"></i>
                            จัดการ Layers
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <?php include __DIR__ . "/../template-layout/footer.php"; ?>

</div>

<?php include __DIR__ . "/../template-layout/scripts.php"; ?>

</body>
</html>
