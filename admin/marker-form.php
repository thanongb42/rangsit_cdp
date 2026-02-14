<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$db = getDB();
$B = BASE_URL;

// Get marker ID from URL (for edit mode)
$markerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $markerId > 0;
$marker = null;

// Fetch marker data if editing
if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM gis_markers WHERE id = ?");
    $stmt->execute([$markerId]);
    $marker = $stmt->fetch();
    
    if (!$marker) {
        header("Location: {$B}/admin/markers.php?error=not_found");
        exit;
    }
    
    // Decode properties JSON
    $marker['properties'] = $marker['properties'] ? json_decode($marker['properties'], true) : [];
}

// Handle form submission
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $layerId = (int)$_POST['layer_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $latitude = (float)$_POST['latitude'];
    $longitude = (float)$_POST['longitude'];
    $status = $_POST['status'];
    
    // Properties JSON
    $properties = [];
    if (!empty($_POST['zone_group'])) {
        $properties['zone_group'] = $_POST['zone_group'];
    }
    if (!empty($_POST['device_count'])) {
        $properties['device_count'] = (int)$_POST['device_count'];
    }
    if (!empty($_POST['point_number'])) {
        $properties['point_number'] = (int)$_POST['point_number'];
    }
    // Add custom properties
    if (!empty($_POST['custom_properties'])) {
        $customProps = json_decode($_POST['custom_properties'], true);
        if (is_array($customProps)) {
            $properties = array_merge($properties, $customProps);
        }
    }
    $propertiesJson = json_encode($properties, JSON_UNESCAPED_UNICODE);
    
    // Validation
    $errors = [];
    if (empty($title)) $errors[] = 'กรุณาระบุชื่อจุด';
    if (!$layerId) $errors[] = 'กรุณาเลือก Layer';
    if (!$latitude || !$longitude) $errors[] = 'กรุณาระบุพิกัด';
    if ($latitude < -90 || $latitude > 90) $errors[] = 'ค่า Latitude ไม่ถูกต้อง (-90 ถึง 90)';
    if ($longitude < -180 || $longitude > 180) $errors[] = 'ค่า Longitude ไม่ถูกต้อง (-180 ถึง 180)';
    
    if (empty($errors)) {
        try {
            if ($isEdit) {
                // Update existing marker
                $sql = "UPDATE gis_markers SET 
                        layer_id = ?,
                        title = ?,
                        description = ?,
                        latitude = ?,
                        longitude = ?,
                        coordinates = ST_GeomFromText(?, 4326),
                        properties = ?,
                        status = ?
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $layerId,
                    $title,
                    $description,
                    $latitude,
                    $longitude,
                    "POINT({$longitude} {$latitude})",
                    $propertiesJson,
                    $status,
                    $markerId
                ]);
                $msg = 'แก้ไขหมุดสำเร็จ';
            } else {
                // Insert new marker
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
                    $propertiesJson,
                    $status
                ]);
                $msg = 'เพิ่มหมุดสำเร็จ';
            }
            $msgType = 'success';
            
            // Redirect after success
            header("Location: {$B}/admin/markers.php?msg=" . urlencode($msg) . "&type={$msgType}");
            exit;
            
        } catch (Exception $e) {
            $msg = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            $msgType = 'error';
        }
    } else {
        $msg = implode('<br>', $errors);
        $msgType = 'error';
    }
}

// Get all layers for dropdown
$layers = $db->query("SELECT id, layer_name, marker_color FROM gis_layers ORDER BY sort_order")->fetchAll();

// Page title
$pageTitle = $isEdit ? 'แก้ไขหมุด' : 'เพิ่มหมุดใหม่';
?>
<?php include __DIR__ . "/../template-layout/header.php"; ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<?php include __DIR__ . "/../template-layout/sidebar.php"; ?>

<div id="mainContent" class="main-expanded transition-all duration-300 min-h-screen flex flex-col">

    <?php include __DIR__ . "/../template-layout/navbar.php"; ?>

    <main class="flex-1 p-6 lg:p-8">

        <!-- Breadcrumb -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <h1 class="text-2xl font-semibold text-gray-900"><?= htmlspecialchars($pageTitle) ?></h1>
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="<?= $B ?>/admin/index.php" class="hover:text-gray-700">หน้าหลัก</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <a href="<?= $B ?>/admin/markers.php" class="hover:text-gray-700">จัดการหมุด</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900 font-medium"><?= htmlspecialchars($pageTitle) ?></span>
                </nav>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($msg): ?>
        <div class="mb-6 p-4 rounded-lg <?= $msgType === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
            <div class="flex items-center">
                <i class="fas <?= $msgType === 'success' ? 'fa-check-circle text-green-600' : 'fa-exclamation-circle text-red-600' ?> mr-2"></i>
                <span class="<?= $msgType === 'success' ? 'text-green-800' : 'text-red-800' ?>"><?= $msg ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <form method="POST" action="" class="p-6 lg:p-8">
                
                <!-- Basic Information -->
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        ข้อมูลพื้นฐาน
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Layer Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Layer <span class="text-red-500">*</span>
                            </label>
                            <select name="layer_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- เลือก Layer --</option>
                                <?php foreach ($layers as $layer): ?>
                                <option value="<?= $layer['id'] ?>" 
                                        <?= ($isEdit && $marker['layer_id'] == $layer['id']) ? 'selected' : '' ?>
                                        data-color="<?= htmlspecialchars($layer['marker_color']) ?>">
                                    <?= htmlspecialchars($layer['layer_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                สถานะ <span class="text-red-500">*</span>
                            </label>
                            <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="active" <?= ($isEdit && $marker['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($isEdit && $marker['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                <option value="maintenance" <?= ($isEdit && $marker['status'] === 'maintenance') ? 'selected' : '' ?>>Maintenance</option>
                            </select>
                        </div>

                        <!-- Title -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ชื่อจุด <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="title" 
                                   required 
                                   value="<?= $isEdit ? htmlspecialchars($marker['title']) : '' ?>"
                                   placeholder="เช่น จุดที่ 1"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                รายละเอียด
                            </label>
                            <textarea name="description" 
                                      rows="3" 
                                      placeholder="รายละเอียดเพิ่มเติม..."
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= $isEdit ? htmlspecialchars($marker['description']) : '' ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Location Information -->
                <div class="mb-8 pb-8 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                        ตำแหน่งพิกัด
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Latitude -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Latitude (ละติจูด) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   name="latitude" 
                                   step="0.000001" 
                                   required 
                                   value="<?= $isEdit ? $marker['latitude'] : '' ?>"
                                   placeholder="13.986146"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">ค่าระหว่าง -90 ถึง 90</p>
                        </div>

                        <!-- Longitude -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Longitude (ลองจิจูด) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   name="longitude" 
                                   step="0.000001" 
                                   required 
                                   value="<?= $isEdit ? $marker['longitude'] : '' ?>"
                                   placeholder="100.608983"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">ค่าระหว่าง -180 ถึง 180</p>
                        </div>
                    </div>

                    <!-- Map Preview -->
                    <div class="mt-6">
                        <p class="text-sm text-gray-600 mb-2">
                            <i class="fas fa-mouse-pointer mr-1"></i>
                            คลิกบนแผนที่เพื่อเลือกตำแหน่งพิกัด
                        </p>
                        <div id="mapPreview" class="w-full h-96 rounded-lg border border-gray-300 z-0"></div>
                        <button type="button"
                                id="getCurrentLocation"
                                class="mt-3 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                            <i class="fas fa-crosshairs mr-2"></i>
                            ใช้ตำแหน่งปัจจุบัน
                        </button>
                    </div>
                </div>

                <!-- Additional Properties -->
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-cog text-gray-500 mr-2"></i>
                        ข้อมูลเพิ่มเติม
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Zone Group -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                กลุ่มพื้นที่
                            </label>
                            <input type="text" 
                                   name="zone_group" 
                                   value="<?= $isEdit && isset($marker['properties']['zone_group']) ? htmlspecialchars($marker['properties']['zone_group']) : '' ?>"
                                   placeholder="เช่น ซอยรังสิต-ปทุมธานี17"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Device Count -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                จำนวนอุปกรณ์
                            </label>
                            <input type="number" 
                                   name="device_count" 
                                   min="0"
                                   value="<?= $isEdit && isset($marker['properties']['device_count']) ? $marker['properties']['device_count'] : '' ?>"
                                   placeholder="0"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Point Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                หมายเลขจุด
                            </label>
                            <input type="number" 
                                   name="point_number" 
                                   min="0"
                                   value="<?= $isEdit && isset($marker['properties']['point_number']) ? $marker['properties']['point_number'] : '' ?>"
                                   placeholder="0"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- Custom Properties JSON -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Properties เพิ่มเติม (JSON)
                        </label>
                        <textarea name="custom_properties" 
                                  rows="4" 
                                  placeholder='{"key": "value"}'
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"><?= $isEdit && !empty($marker['properties']) ? htmlspecialchars(json_encode($marker['properties'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) : '' ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">รูปแบบ JSON ถูกต้อง (ถ้ามี)</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="<?= $B ?>/admin/markers.php" 
                       class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                        <i class="fas fa-times mr-2"></i>
                        ยกเลิก
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        <?= $isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มหมุดใหม่' ?>
                    </button>
                </div>

            </form>
        </div>

    </main>

    <?php include __DIR__ . "/../template-layout/footer.php"; ?>

</div>

<?php include __DIR__ . "/../template-layout/scripts.php"; ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function() {
    const latInput = document.querySelector('input[name="latitude"]');
    const longInput = document.querySelector('input[name="longitude"]');

    // Default center: Rangsit area
    const defaultLat = <?= $isEdit ? $marker['latitude'] : '13.986146' ?>;
    const defaultLng = <?= $isEdit ? $marker['longitude'] : '100.608983' ?>;
    const defaultZoom = <?= $isEdit ? 16 : 13 ?>;

    // Initialize map
    const map = L.map('mapPreview').setView([defaultLat, defaultLng], defaultZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    // Marker on map
    let marker = null;

    function setMarker(lat, lng) {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);

            // Drag marker to update coordinates
            marker.on('dragend', function() {
                const pos = marker.getLatLng();
                latInput.value = pos.lat.toFixed(6);
                longInput.value = pos.lng.toFixed(6);
            });
        }
        marker.bindPopup('Lat: ' + lat.toFixed(6) + '<br>Lng: ' + lng.toFixed(6)).openPopup();
    }

    // If editing, show existing marker
    <?php if ($isEdit): ?>
    setMarker(defaultLat, defaultLng);
    <?php endif; ?>

    // Click on map to set coordinates
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        latInput.value = lat.toFixed(6);
        longInput.value = lng.toFixed(6);
        setMarker(lat, lng);
    });

    // Sync: when user types lat/lng manually, update map
    function syncMapFromInputs() {
        const lat = parseFloat(latInput.value);
        const lng = parseFloat(longInput.value);
        if (!isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
            map.setView([lat, lng], map.getZoom() < 14 ? 15 : map.getZoom());
            setMarker(lat, lng);
        }
    }
    latInput.addEventListener('change', syncMapFromInputs);
    longInput.addEventListener('change', syncMapFromInputs);

    // Get current location button
    document.getElementById('getCurrentLocation')?.addEventListener('click', function() {
        if (!navigator.geolocation) {
            alert('เบราว์เซอร์ของคุณไม่รองรับ Geolocation');
            return;
        }
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                latInput.value = lat.toFixed(6);
                longInput.value = lng.toFixed(6);
                map.setView([lat, lng], 16);
                setMarker(lat, lng);
            },
            function(error) {
                alert('ไม่สามารถรับตำแหน่งปัจจุบันได้: ' + error.message);
            }
        );
    });
})();
</script>

</body>
</html>
