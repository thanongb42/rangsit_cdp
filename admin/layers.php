<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$db = getDB();
$B = BASE_URL;

// Handle form actions
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $stmt = $db->prepare("INSERT INTO gis_layers (category_id, layer_name, layer_slug, description, icon_class, marker_color, marker_shape, is_visible, sort_order)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['category_id'] ?: null,
            $_POST['layer_name'],
            $_POST['layer_slug'],
            $_POST['description'] ?: null,
            $_POST['icon_class'] ?: 'fa-map-marker-alt',
            $_POST['marker_color'] ?: '#3b82f6',
            $_POST['marker_shape'] ?: 'circle',
            isset($_POST['is_visible']) ? 1 : 0,
            (int)($_POST['sort_order'] ?? 0),
        ]);
        $msg = 'เพิ่ม Layer สำเร็จ';
        $msgType = 'success';
    }

    if ($action === 'update') {
        $stmt = $db->prepare("UPDATE gis_layers SET category_id=?, layer_name=?, layer_slug=?, description=?, icon_class=?, marker_color=?, marker_shape=?, is_visible=?, sort_order=? WHERE id=?");
        $stmt->execute([
            $_POST['category_id'] ?: null,
            $_POST['layer_name'],
            $_POST['layer_slug'],
            $_POST['description'] ?: null,
            $_POST['icon_class'] ?: 'fa-map-marker-alt',
            $_POST['marker_color'] ?: '#3b82f6',
            $_POST['marker_shape'] ?: 'circle',
            isset($_POST['is_visible']) ? 1 : 0,
            (int)($_POST['sort_order'] ?? 0),
            (int)$_POST['id'],
        ]);
        $msg = 'แก้ไข Layer สำเร็จ';
        $msgType = 'success';
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $markerCount = $db->query("SELECT COUNT(*) FROM gis_markers WHERE layer_id={$id}")->fetchColumn();
        if ($markerCount > 0) {
            $msg = "ไม่สามารถลบได้ — Layer นี้มี {$markerCount} หมุดอยู่";
            $msgType = 'error';
        } else {
            $db->prepare("DELETE FROM gis_layers WHERE id=?")->execute([$id]);
            $msg = 'ลบ Layer สำเร็จ';
            $msgType = 'success';
        }
    }

    if ($action === 'toggle') {
        $db->prepare("UPDATE gis_layers SET is_visible = NOT is_visible WHERE id=?")->execute([(int)$_POST['id']]);
        $msg = 'เปลี่ยนสถานะแสดง/ซ่อนสำเร็จ';
        $msgType = 'success';
    }
}

// Fetch layers
$layers = $db->query("
    SELECT l.*, c.name as category_name, c.color as category_color,
           COUNT(m.id) as marker_count,
           SUM(CASE WHEN m.status='active' THEN 1 ELSE 0 END) as active_count
    FROM gis_layers l
    LEFT JOIN gis_categories c ON c.id = l.category_id
    LEFT JOIN gis_markers m ON m.layer_id = l.id
    GROUP BY l.id
    ORDER BY l.sort_order, l.id
")->fetchAll();

// Fetch categories for dropdown
$categories = $db->query("SELECT id, name FROM gis_categories ORDER BY sort_order")->fetchAll();
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
                    <h1 class="text-2xl font-semibold text-gray-900">Layers</h1>
                    <p class="text-sm text-gray-500 mt-1">จัดการชั้นข้อมูลแผนที่ GIS</p>
                </div>
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="index.php" class="hover:text-gray-700">Home</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900 font-medium">Layers</span>
                </nav>
            </div>
        </div>

        <!-- Alert Message -->
        <?php if ($msg): ?>
        <div class="mb-6 px-4 py-3 rounded-lg border <?= $msgType === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' ?> flex items-center gap-2" id="alertMsg">
            <i class="fas <?= $msgType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <span class="text-sm font-medium"><?= htmlspecialchars($msg) ?></span>
            <button onclick="document.getElementById('alertMsg').remove()" class="ml-auto text-lg leading-none">&times;</button>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl p-5 border border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-layer-group text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900"><?= count($layers) ?></p>
                        <p class="text-xs text-gray-500">ชั้นข้อมูลทั้งหมด</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-5 border border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-eye text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900"><?= count(array_filter($layers, fn($l) => $l['is_visible'])) ?></p>
                        <p class="text-xs text-gray-500">แสดงบนแผนที่</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-5 border border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-map-pin text-indigo-600"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900"><?= array_sum(array_column($layers, 'marker_count')) ?></p>
                        <p class="text-xs text-gray-500">หมุดทั้งหมด</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" id="searchInput" placeholder="ค้นหา Layer..."
                       class="pl-10 pr-4 py-2 w-72 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
            </div>
            <button onclick="openModal()" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                <i class="fas fa-plus mr-2"></i> เพิ่ม Layer ใหม่
            </button>
        </div>

        <!-- Layers Table -->
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full" id="layersTable">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Layer</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">หมวดหมู่</th>
                            <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">หมุด</th>
                            <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">รูปแบบ</th>
                            <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">แสดงผล</th>
                            <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">ลำดับ</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($layers)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <i class="fas fa-layer-group text-4xl mb-3"></i>
                                <p class="text-sm">ยังไม่มีชั้นข้อมูล</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($layers as $layer): ?>
                        <tr class="hover:bg-gray-50 transition-colors layer-row">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                         style="background:<?= htmlspecialchars($layer['marker_color']) ?>20;">
                                        <i class="fas <?= htmlspecialchars($layer['icon_class']) ?>"
                                           style="color:<?= htmlspecialchars($layer['marker_color']) ?>;"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 layer-name"><?= htmlspecialchars($layer['layer_name']) ?></p>
                                        <p class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($layer['layer_slug']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($layer['category_name']): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border"
                                      style="color:<?= htmlspecialchars($layer['category_color']) ?>;
                                             border-color:<?= htmlspecialchars($layer['category_color']) ?>40;
                                             background:<?= htmlspecialchars($layer['category_color']) ?>10;">
                                    <?= htmlspecialchars($layer['category_name']) ?>
                                </span>
                                <?php else: ?>
                                <span class="text-xs text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm font-bold text-gray-900"><?= number_format($layer['marker_count']) ?></span>
                                <?php if ($layer['active_count'] > 0): ?>
                                <span class="block text-[10px] text-green-600"><?= $layer['active_count'] ?> active</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <span class="w-5 h-5 rounded-full border-2 border-white shadow-sm" style="background:<?= htmlspecialchars($layer['marker_color']) ?>;"></span>
                                    <span class="text-xs text-gray-500"><?= htmlspecialchars($layer['marker_shape']) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $layer['id'] ?>">
                                    <button type="submit" class="<?= $layer['is_visible'] ? 'text-green-600' : 'text-gray-300' ?> hover:opacity-70 transition-opacity" title="<?= $layer['is_visible'] ? 'แสดงอยู่' : 'ซ่อนอยู่' ?>">
                                        <i class="fas <?= $layer['is_visible'] ? 'fa-eye' : 'fa-eye-slash' ?> text-lg"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm text-gray-500"><?= $layer['sort_order'] ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <button onclick='editLayer(<?= json_encode($layer, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'
                                            class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="แก้ไข">
                                        <i class="fas fa-pen text-sm"></i>
                                    </button>
                                    <button onclick="confirmDelete(<?= $layer['id'] ?>, '<?= htmlspecialchars(addslashes($layer['layer_name'])) ?>', <?= $layer['marker_count'] ?>)"
                                            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="ลบ">
                                        <i class="fas fa-trash-alt text-sm"></i>
                                    </button>
                                </div>
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

<!-- Modal: Create / Edit Layer -->
<div id="layerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
        <form method="POST" id="layerForm">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="formId">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">เพิ่ม Layer ใหม่</h3>
                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>

            <div class="p-6 space-y-4">
                <!-- Layer Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ Layer <span class="text-red-500">*</span></label>
                    <input type="text" name="layer_name" id="fLayerName" required
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="เช่น จุดติดตั้งกล้อง CCTV">
                </div>

                <!-- Slug -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slug <span class="text-red-500">*</span></label>
                    <input type="text" name="layer_slug" id="fLayerSlug" required
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono"
                           placeholder="เช่น cctv-cameras">
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">หมวดหมู่</label>
                    <select name="category_id" id="fCategory"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                        <option value="">— ไม่ระบุ —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด</label>
                    <textarea name="description" id="fDescription" rows="2"
                              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                              placeholder="คำอธิบาย Layer"></textarea>
                </div>

                <!-- Color & Icon Row -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">สีหมุด</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="marker_color" id="fColor" value="#3b82f6"
                                   class="w-10 h-10 border border-gray-200 rounded-lg cursor-pointer p-0.5">
                            <input type="text" id="fColorText" value="#3b82f6"
                                   class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   maxlength="7">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Icon</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i id="iconPreview" class="fas fa-map-marker-alt"></i></span>
                            <input type="text" name="icon_class" id="fIcon" value="fa-map-marker-alt" readonly
                                   class="w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer bg-white"
                                   onclick="document.getElementById('iconPicker').classList.toggle('hidden')">
                        </div>
                    </div>
                </div>

                <!-- Icon Picker -->
                <div id="iconPicker" class="hidden border border-gray-200 rounded-lg p-3 bg-white">
                    <input type="text" id="iconSearch" placeholder="ค้นหา icon..."
                           class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-xs mb-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <div id="iconGrid" class="grid grid-cols-8 gap-1 max-h-40 overflow-y-auto"></div>
                </div>

                <!-- Shape & Sort Row -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">รูปร่างหมุด</label>
                        <select name="marker_shape" id="fShape"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                            <option value="circle">Circle</option>
                            <option value="square">Square</option>
                            <option value="diamond">Diamond</option>
                            <option value="star">Star</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ลำดับ</label>
                        <input type="number" name="sort_order" id="fSort" value="0" min="0"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- Visible -->
                <div class="flex items-center gap-3 pt-1">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_visible" id="fVisible" checked class="sr-only peer">
                        <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                    <span class="text-sm text-gray-700">แสดงบนแผนที่</span>
                </div>

                <!-- Preview -->
                <div class="bg-gray-50 rounded-lg p-4 flex items-center gap-4 border border-gray-100">
                    <div id="previewIcon" class="w-12 h-12 rounded-lg flex items-center justify-center" style="background:#3b82f620;">
                        <i class="fas fa-map-marker-alt" style="color:#3b82f6;"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-700" id="previewName">ตัวอย่าง Layer</p>
                        <p class="text-xs text-gray-400">Preview</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    ยกเลิก
                </button>
                <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                    <i class="fas fa-save mr-1.5"></i> <span id="submitText">บันทึก</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4">
        <div class="p-6 text-center">
            <div class="w-14 h-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-trash-alt text-red-500 text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">ยืนยันการลบ</h3>
            <p class="text-sm text-gray-500" id="deleteMsg">คุณต้องการลบ Layer นี้หรือไม่?</p>
        </div>
        <div class="flex border-t border-gray-200">
            <button onclick="closeDeleteModal()" class="flex-1 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors rounded-bl-2xl">ยกเลิก</button>
            <form method="POST" id="deleteForm" class="flex-1">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId">
                <button type="submit" class="w-full py-3 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors rounded-br-2xl border-l border-gray-200">ลบ</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . "/../template-layout/scripts.php"; ?>

<script>
// Modal controls
function openModal() {
    document.getElementById('layerForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('formId').value = '';
    document.getElementById('modalTitle').textContent = 'เพิ่ม Layer ใหม่';
    document.getElementById('submitText').textContent = 'บันทึก';
    document.getElementById('fColor').value = '#3b82f6';
    document.getElementById('fColorText').value = '#3b82f6';
    document.getElementById('fVisible').checked = true;
    updatePreview();
    document.getElementById('layerModal').classList.remove('hidden');
}

function editLayer(data) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = data.id;
    document.getElementById('modalTitle').textContent = 'แก้ไข Layer';
    document.getElementById('submitText').textContent = 'อัปเดต';
    document.getElementById('fLayerName').value = data.layer_name;
    document.getElementById('fLayerSlug').value = data.layer_slug;
    document.getElementById('fCategory').value = data.category_id || '';
    document.getElementById('fDescription').value = data.description || '';
    document.getElementById('fColor').value = data.marker_color;
    document.getElementById('fColorText').value = data.marker_color;
    document.getElementById('fIcon').value = data.icon_class;
    document.getElementById('fShape').value = data.marker_shape;
    document.getElementById('fSort').value = data.sort_order;
    document.getElementById('fVisible').checked = !!data.is_visible;
    updatePreview();
    document.getElementById('layerModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('layerModal').classList.add('hidden');
}

function confirmDelete(id, name, count) {
    document.getElementById('deleteId').value = id;
    if (count > 0) {
        document.getElementById('deleteMsg').innerHTML = `Layer "<b>${name}</b>" มี <b>${count}</b> หมุด — ไม่สามารถลบได้จนกว่าจะย้ายหรือลบหมุดทั้งหมดก่อน`;
    } else {
        document.getElementById('deleteMsg').innerHTML = `คุณต้องการลบ Layer "<b>${name}</b>" หรือไม่?`;
    }
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Live preview
function updatePreview() {
    const color = document.getElementById('fColor').value;
    const icon = document.getElementById('fIcon').value || 'fa-map-marker-alt';
    const name = document.getElementById('fLayerName').value || 'ตัวอย่าง Layer';
    document.getElementById('previewIcon').style.background = color + '20';
    document.getElementById('previewIcon').innerHTML = `<i class="fas ${icon}" style="color:${color};"></i>`;
    document.getElementById('previewName').textContent = name;
    document.getElementById('iconPreview').className = 'fas ' + icon;
}

document.getElementById('fColor').addEventListener('input', function() {
    document.getElementById('fColorText').value = this.value;
    updatePreview();
});
document.getElementById('fColorText').addEventListener('input', function() {
    if (/^#[0-9a-fA-F]{6}$/.test(this.value)) {
        document.getElementById('fColor').value = this.value;
    }
    updatePreview();
});
document.getElementById('fLayerName').addEventListener('input', updatePreview);

// Icon Picker
const mapIcons = [
    // Map & Location
    'fa-map-marker-alt','fa-map-pin','fa-location-dot','fa-location-crosshairs','fa-map','fa-map-location-dot','fa-compass','fa-earth-asia',
    // Infrastructure
    'fa-tower-broadcast','fa-satellite-dish','fa-wifi','fa-bolt','fa-plug','fa-lightbulb','fa-solar-panel','fa-charging-station',
    // Water & Environment
    'fa-droplet','fa-faucet-drip','fa-water','fa-leaf','fa-tree','fa-seedling','fa-recycle','fa-trash-can',
    // Buildings
    'fa-building','fa-house','fa-school','fa-hospital','fa-hotel','fa-church','fa-mosque','fa-store',
    // Public Services
    'fa-hand-holding-heart','fa-people-roof','fa-person-shelter','fa-kit-medical','fa-suitcase-medical','fa-stethoscope','fa-wheelchair','fa-baby',
    // Transport
    'fa-road','fa-bridge','fa-car','fa-bus','fa-train','fa-bicycle','fa-motorcycle','fa-gas-pump',
    // Safety & Security
    'fa-shield-halved','fa-camera','fa-video','fa-fire-extinguisher','fa-triangle-exclamation','fa-bell','fa-siren','fa-phone',
    // Government & Admin
    'fa-landmark','fa-building-columns','fa-flag','fa-gavel','fa-scale-balanced','fa-id-card','fa-user-tie','fa-users',
    // Tools & Work
    'fa-screwdriver-wrench','fa-wrench','fa-hammer','fa-gear','fa-toolbox','fa-hard-hat','fa-industry','fa-warehouse',
    // Sports & Recreation
    'fa-futbol','fa-basketball','fa-dumbbell','fa-swimming-pool','fa-umbrella-beach','fa-campground','fa-mountain','fa-person-running',
    // Education & Culture
    'fa-graduation-cap','fa-book','fa-chalkboard-teacher','fa-microscope','fa-palette','fa-music','fa-monument','fa-chess-rook',
    // Food & Commerce
    'fa-utensils','fa-mug-hot','fa-cart-shopping','fa-basket-shopping','fa-shop','fa-cash-register','fa-coins','fa-money-bill',
    // Misc
    'fa-circle-info','fa-star','fa-heart','fa-clock','fa-calendar','fa-layer-group','fa-folder','fa-tags',
];

const iconGrid = document.getElementById('iconGrid');
const iconSearch = document.getElementById('iconSearch');

function renderIcons(filter = '') {
    iconGrid.innerHTML = '';
    const filtered = filter ? mapIcons.filter(i => i.includes(filter)) : mapIcons;
    filtered.forEach(icon => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'w-9 h-9 flex items-center justify-center rounded-lg hover:bg-indigo-50 hover:text-indigo-600 text-gray-500 transition-colors';
        btn.innerHTML = `<i class="fas ${icon}"></i>`;
        btn.title = icon;
        btn.addEventListener('click', () => {
            document.getElementById('fIcon').value = icon;
            document.getElementById('iconPicker').classList.add('hidden');
            updatePreview();
        });
        iconGrid.appendChild(btn);
    });
}
renderIcons();
iconSearch.addEventListener('input', function() { renderIcons(this.value.toLowerCase()); });

// Auto-generate slug
document.getElementById('fLayerName').addEventListener('input', function() {
    if (document.getElementById('formAction').value === 'create') {
        document.getElementById('fLayerSlug').value = this.value
            .toLowerCase().trim()
            .replace(/[^\u0E00-\u0E7Fa-z0-9\s-]/g, '')
            .replace(/[\s]+/g, '-')
            .replace(/-+/g, '-');
    }
});

// Search filter
document.getElementById('searchInput').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.layer-row').forEach(row => {
        const name = row.querySelector('.layer-name').textContent.toLowerCase();
        row.style.display = name.includes(q) ? '' : 'none';
    });
});

// Close modals on backdrop click
document.getElementById('layerModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>
