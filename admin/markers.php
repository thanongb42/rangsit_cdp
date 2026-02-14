<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$db = getDB();
$B = BASE_URL;

// Handle actions
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $db->prepare("DELETE FROM gis_markers WHERE id=?")->execute([(int)$_POST['id']]);
        $msg = 'ลบหมุดสำเร็จ';
        $msgType = 'success';
    }

    if ($action === 'status') {
        $db->prepare("UPDATE gis_markers SET status=? WHERE id=?")->execute([$_POST['status'], (int)$_POST['id']]);
        $msg = 'เปลี่ยนสถานะสำเร็จ';
        $msgType = 'success';
    }

    if ($action === 'bulk_delete') {
        $ids = array_map('intval', $_POST['ids'] ?? []);
        if ($ids) {
            $in = implode(',', $ids);
            $db->exec("DELETE FROM gis_markers WHERE id IN ({$in})");
            $msg = 'ลบ ' . count($ids) . ' หมุดสำเร็จ';
            $msgType = 'success';
        }
    }

    if ($action === 'bulk_status') {
        $ids = array_map('intval', $_POST['ids'] ?? []);
        $status = $_POST['status'];
        if ($ids && in_array($status, ['active', 'inactive', 'maintenance'])) {
            $in = implode(',', $ids);
            $db->exec("UPDATE gis_markers SET status='{$status}' WHERE id IN ({$in})");
            $msg = 'เปลี่ยนสถานะ ' . count($ids) . ' หมุดสำเร็จ';
            $msgType = 'success';
        }
    }
}

// Filters
$filterLayer  = $_GET['layer'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$search       = $_GET['q'] ?? '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];
if ($filterLayer) { $where[] = "m.layer_id = ?"; $params[] = (int)$filterLayer; }
if ($filterStatus) { $where[] = "m.status = ?"; $params[] = $filterStatus; }
if ($search) { $where[] = "(m.title LIKE ? OR m.description LIKE ?)"; $params[] = "%{$search}%"; $params[] = "%{$search}%"; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM gis_markers m {$whereSQL}");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

// Fetch markers
$stmt = $db->prepare("
    SELECT m.*, l.layer_name, l.marker_color, l.icon_class
    FROM gis_markers m
    JOIN gis_layers l ON l.id = m.layer_id
    {$whereSQL}
    ORDER BY m.id DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$markers = $stmt->fetchAll();

// Layers for filter dropdown
$layers = $db->query("SELECT id, layer_name, marker_color FROM gis_layers ORDER BY sort_order")->fetchAll();

// Stats
$stats = $db->query("
    SELECT
        COUNT(*) as total,
        SUM(status='active') as active,
        SUM(status='inactive') as inactive,
        SUM(status='maintenance') as maintenance
    FROM gis_markers
")->fetch();
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
                    <h1 class="text-2xl font-semibold text-gray-900">Markers</h1>
                    <p class="text-sm text-gray-500 mt-1">จัดการหมุดพิกัด GIS ทั้งหมด</p>
                </div>
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="index.php" class="hover:text-gray-700">Home</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900 font-medium">Markers</span>
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

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl p-4 border border-gray-200 flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-map-pin text-indigo-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900"><?= number_format($stats['total']) ?></p>
                    <p class="text-xs text-gray-500">ทั้งหมด</p>
                </div>
            </div>
            <div class="bg-white rounded-xl p-4 border border-gray-200 flex items-center gap-3">
                <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900"><?= number_format($stats['active']) ?></p>
                    <p class="text-xs text-gray-500">Active</p>
                </div>
            </div>
            <div class="bg-white rounded-xl p-4 border border-gray-200 flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-wrench text-yellow-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900"><?= number_format($stats['maintenance']) ?></p>
                    <p class="text-xs text-gray-500">Maintenance</p>
                </div>
            </div>
            <div class="bg-white rounded-xl p-4 border border-gray-200 flex items-center gap-3">
                <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-times-circle text-red-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900"><?= number_format($stats['inactive']) ?></p>
                    <p class="text-xs text-gray-500">Inactive</p>
                </div>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="ค้นหาชื่อหมุด..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                </div>
                <select name="layer" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                    <option value="">ทุก Layer</option>
                    <?php foreach ($layers as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= $filterLayer == $l['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($l['layer_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                    <option value="">ทุกสถานะ</option>
                    <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="maintenance" <?= $filterStatus === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                    <i class="fas fa-filter mr-1.5"></i> กรอง
                </button>
                <?php if ($search || $filterLayer || $filterStatus): ?>
                <a href="markers.php" class="px-4 py-2 bg-gray-100 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors text-center">
                    <i class="fas fa-times mr-1"></i> ล้าง
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Bulk Actions & Add -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2" id="bulkActions" style="display:none;">
                <span class="text-sm text-gray-500">เลือก <b id="selectedCount">0</b> รายการ</span>
                <button type="button" onclick="bulkStatus('active')" class="px-3 py-1.5 text-xs font-medium bg-green-50 text-green-700 rounded-lg hover:bg-green-100">Active</button>
                <button type="button" onclick="bulkStatus('inactive')" class="px-3 py-1.5 text-xs font-medium bg-red-50 text-red-700 rounded-lg hover:bg-red-100">Inactive</button>
                <button type="button" onclick="bulkStatus('maintenance')" class="px-3 py-1.5 text-xs font-medium bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100">Maintenance</button>
                <button type="button" onclick="bulkDelete()" class="px-3 py-1.5 text-xs font-medium bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-trash-alt mr-1"></i> ลบ
                </button>
            </div>
            <div id="resultInfo" class="text-sm text-gray-500">
                แสดง <?= number_format(count($markers)) ?> จาก <?= number_format($totalRows) ?> รายการ
            </div>
            <a href="marker-form.php" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                <i class="fas fa-plus mr-2"></i> เพิ่มหมุดใหม่
            </a>
        </div>

        <!-- Markers Table -->
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3.5 text-left">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ชื่อหมุด</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Layer</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">พิกัด</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">สถานะ</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">สร้างเมื่อ</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($markers)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center text-gray-400">
                                <i class="fas fa-map-pin text-4xl mb-3"></i>
                                <p class="text-sm">ไม่พบข้อมูลหมุด</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($markers as $m): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <input type="checkbox" class="row-cb rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" value="<?= $m['id'] ?>">
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900">#<?= $m['id'] ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:<?= htmlspecialchars($m['marker_color']) ?>;"></span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate max-w-[240px]"><?= htmlspecialchars($m['title']) ?></p>
                                        <?php if ($m['description']): ?>
                                        <p class="text-xs text-gray-400 truncate max-w-[240px]"><?= htmlspecialchars($m['description']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border"
                                      style="color:<?= htmlspecialchars($m['marker_color']) ?>;
                                             border-color:<?= htmlspecialchars($m['marker_color']) ?>40;
                                             background:<?= htmlspecialchars($m['marker_color']) ?>10;">
                                    <i class="fas <?= htmlspecialchars($m['icon_class']) ?> text-[10px]"></i>
                                    <?= htmlspecialchars($m['layer_name']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="https://www.google.com/maps?q=<?= $m['latitude'] ?>,<?= $m['longitude'] ?>" target="_blank"
                                   class="text-xs text-blue-600 hover:text-blue-800 font-mono hover:underline">
                                    <?= number_format($m['latitude'], 6) ?>, <?= number_format($m['longitude'], 6) ?>
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php
                                $sCls = match($m['status']) {
                                    'active'      => 'bg-green-50 text-green-700 border-green-200',
                                    'inactive'    => 'bg-red-50 text-red-700 border-red-200',
                                    'maintenance' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                };
                                ?>
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium border <?= $sCls ?>">
                                    <?= ucfirst($m['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                <?= date('d M Y', strtotime($m['created_at'])) ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- Status Dropdown -->
                                    <div class="relative" x-data="{open:false}">
                                        <button onclick="this.nextElementSibling.classList.toggle('hidden')"
                                                class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="เปลี่ยนสถานะ">
                                            <i class="fas fa-exchange-alt text-sm"></i>
                                        </button>
                                        <div class="hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                                            <?php foreach (['active' => 'Active', 'inactive' => 'Inactive', 'maintenance' => 'Maintenance'] as $sv => $sl): ?>
                                            <?php if ($sv !== $m['status']): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="status">
                                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $sv ?>">
                                                <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 first:rounded-t-lg last:rounded-b-lg"><?= $sl ?></button>
                                            </form>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <a href="marker-form.php?id=<?= $m['id'] ?>"
                                       class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="แก้ไข">
                                        <i class="fas fa-pen text-sm"></i>
                                    </a>
                                    <button onclick="confirmDelete(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['title'])) ?>')"
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

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200">
                <p class="text-sm text-gray-500">
                    หน้า <?= $page ?> จาก <?= $totalPages ?> (<?= number_format($totalRows) ?> รายการ)
                </p>
                <div class="flex items-center gap-1">
                    <?php
                    $qs = http_build_query(array_filter(['q' => $search, 'layer' => $filterLayer, 'status' => $filterStatus]));
                    $qs = $qs ? "&{$qs}" : '';
                    ?>
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= $qs ?>" class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                    <a href="?page=<?= $i ?><?= $qs ?>"
                       class="px-3 py-1.5 text-sm rounded-lg transition-colors <?= $i === $page ? 'bg-indigo-600 text-white' : 'border border-gray-200 hover:bg-gray-50' ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $qs ?>" class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </main>

    <?php include __DIR__ . "/../template-layout/footer.php"; ?>

</div>

<!-- Delete Confirm Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4">
        <div class="p-6 text-center">
            <div class="w-14 h-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-trash-alt text-red-500 text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">ยืนยันการลบ</h3>
            <p class="text-sm text-gray-500" id="deleteMsg"></p>
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

<!-- Bulk Forms (hidden) -->
<form method="POST" id="bulkDeleteForm">
    <input type="hidden" name="action" value="bulk_delete">
    <div id="bulkDeleteIds"></div>
</form>
<form method="POST" id="bulkStatusForm">
    <input type="hidden" name="action" value="bulk_status">
    <input type="hidden" name="status" id="bulkStatusVal">
    <div id="bulkStatusIds"></div>
</form>

<?php include __DIR__ . "/../template-layout/scripts.php"; ?>

<script>
// Delete modal
function confirmDelete(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteMsg').innerHTML = `คุณต้องการลบหมุด "<b>${name}</b>" หรือไม่?`;
    document.getElementById('deleteModal').classList.remove('hidden');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

// Select all / row checkboxes
const selectAll = document.getElementById('selectAll');
const rowCbs = document.querySelectorAll('.row-cb');
const bulkActions = document.getElementById('bulkActions');
const resultInfo = document.getElementById('resultInfo');
const selectedCount = document.getElementById('selectedCount');

function updateBulk() {
    const checked = document.querySelectorAll('.row-cb:checked');
    const n = checked.length;
    if (n > 0) {
        bulkActions.style.display = 'flex';
        resultInfo.style.display = 'none';
        selectedCount.textContent = n;
    } else {
        bulkActions.style.display = 'none';
        resultInfo.style.display = '';
    }
}

selectAll.addEventListener('change', function() {
    rowCbs.forEach(cb => cb.checked = this.checked);
    updateBulk();
});
rowCbs.forEach(cb => cb.addEventListener('change', updateBulk));

function getSelectedIds() {
    return [...document.querySelectorAll('.row-cb:checked')].map(cb => cb.value);
}

function bulkDelete() {
    const ids = getSelectedIds();
    if (!ids.length) return;
    if (!confirm(`ต้องการลบ ${ids.length} หมุดหรือไม่?`)) return;
    const container = document.getElementById('bulkDeleteIds');
    container.innerHTML = '';
    ids.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
        container.appendChild(inp);
    });
    document.getElementById('bulkDeleteForm').submit();
}

function bulkStatus(status) {
    const ids = getSelectedIds();
    if (!ids.length) return;
    document.getElementById('bulkStatusVal').value = status;
    const container = document.getElementById('bulkStatusIds');
    container.innerHTML = '';
    ids.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
        container.appendChild(inp);
    });
    document.getElementById('bulkStatusForm').submit();
}

// Close status dropdowns on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.relative')) {
        document.querySelectorAll('.relative > div.absolute').forEach(d => d.classList.add('hidden'));
    }
});
</script>
