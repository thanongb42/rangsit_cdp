<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
$db = getDB();
$B = BASE_URL;

// Handle actions
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Delete user
    if ($action === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        if ($id > 1) { // Protect superadmin
            $db->prepare("DELETE FROM gis_users WHERE id=?")->execute([$id]);
            $msg = 'ลบผู้ใช้สำเร็จ';
            $msgType = 'success';
        } else {
            $msg = 'ไม่สามารถลบ Super Admin ได้';
            $msgType = 'error';
        }
    }

    // Toggle active status
    if ($action === 'toggle_status' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $currentStatus = (int)$_POST['current_status'];
        $newStatus = $currentStatus ? 0 : 1;
        
        $db->prepare("UPDATE gis_users SET is_active=? WHERE id=?")->execute([$newStatus, $id]);
        $msg = $newStatus ? 'เปิดใช้งานผู้ใช้แล้ว' : 'ระงับผู้ใช้แล้ว';
        $msgType = 'success';
    }

    // Bulk delete
    if ($action === 'bulk_delete' && isset($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
        // Remove superadmin ID (1) from list
        $ids = array_filter($ids, fn($id) => $id > 1);
        
        if ($ids) {
            $in = implode(',', $ids);
            $db->exec("DELETE FROM gis_users WHERE id IN ({$in})");
            $msg = 'ลบ ' . count($ids) . ' ผู้ใช้สำเร็จ';
            $msgType = 'success';
        }
    }

    // Bulk toggle status
    if ($action === 'bulk_status' && isset($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
        $status = (int)$_POST['status'];
        
        if ($ids) {
            $in = implode(',', $ids);
            $db->exec("UPDATE gis_users SET is_active={$status} WHERE id IN ({$in})");
            $msg = 'เปลี่ยนสถานะ ' . count($ids) . ' ผู้ใช้สำเร็จ';
            $msgType = 'success';
        }
    }

    // Reset password
    if ($action === 'reset_password' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        // Default password: P@ssw0rd
        $defaultPassword = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        
        $db->prepare("UPDATE gis_users SET password_hash=?, password_changed_at=NULL WHERE id=?")
           ->execute([$defaultPassword, $id]);
        
        $msg = 'รีเซ็ตรหัสผ่านเป็น "P@ssw0rd" เรียบร้อย';
        $msgType = 'success';
    }
}

// Filters
$search = $_GET['q'] ?? '';
$filterDept = $_GET['dept'] ?? '';
$filterDeptId = ($filterDept !== '') ? (int)$filterDept : null;
$filterStatus = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($filterDeptId !== null) {
    $where[] = "u.department_id = ?";
    $params[] = $filterDeptId;
}

if ($filterStatus !== '') {
    $where[] = "u.is_active = ?";
    $params[] = (int)$filterStatus;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countStmt = $db->prepare("
    SELECT COUNT(DISTINCT u.id)
    FROM gis_users u
    LEFT JOIN departments d ON d.department_id = u.department_id
    {$whereSQL}
");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

// Fetch users
$stmt = $db->prepare("
    SELECT u.*,
           d.department_name AS department_display,
           (SELECT COUNT(*) FROM gis_user_roles WHERE user_id = u.id) as role_count
    FROM gis_users u
    LEFT JOIN departments d ON d.department_id = u.department_id
    {$whereSQL}
    ORDER BY u.id ASC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get departments for filter
$departments = $db->query("
    SELECT department_id, department_name
    FROM departments
    WHERE status = 'active'
    ORDER BY level ASC, department_name ASC
")->fetchAll();

// Stats
$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(is_active=1) as active,
        SUM(is_active=0) as inactive,
        SUM(last_login_at IS NOT NULL) as has_logged_in
    FROM gis_users
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
                    <h1 class="text-2xl font-semibold text-gray-900">จัดการผู้ใช้งาน</h1>
                    <p class="text-sm text-gray-500 mt-1">Users Management</p>
                </div>
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="<?= $B ?>/admin/index.php" class="hover:text-gray-700">หน้าหลัก</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900 font-medium">Users</span>
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
                <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-blue-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900"><?= number_format($stats['total']) ?></p>
                    <p class="text-xs text-gray-500">ทั้งหมด</p>
                </div>
            </div>
            <div class="bg-white rounded-xl p-4 border border-gray-200 flex items-center gap-3">
                <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-check text-green-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900"><?= number_format($stats['active']) ?></p>
                    <p class="text-xs text-gray-500">ใช้งาน</p>
                </div>
            </div>
            <div class="bg-white rounded-xl p-4 border border-gray-200 flex items-center gap-3">
                <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-slash text-red-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900"><?= number_format($stats['inactive']) ?></p>
                    <p class="text-xs text-gray-500">ระงับ</p>
                </div>
            </div>
            <div class="bg-white rounded-xl p-4 border border-gray-200 flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-sign-in-alt text-purple-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900"><?= number_format($stats['has_logged_in']) ?></p>
                    <p class="text-xs text-gray-500">เคยเข้าระบบ</p>
                </div>
            </div>
        </div>

        <!-- Filters & Actions -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
            <div class="flex flex-col lg:flex-row gap-3">
                <!-- Search & Filters -->
                <form method="GET" class="flex-1 flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="ค้นหา username, ชื่อ, email..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <select name="dept" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">ทุกหน่วยงาน</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?= (int)$dept['department_id'] ?>" <?= (string)$filterDept === (string)$dept['department_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['department_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">ทุกสถานะ</option>
                        <option value="1" <?= $filterStatus === '1' ? 'selected' : '' ?>>ใช้งาน</option>
                        <option value="0" <?= $filterStatus === '0' ? 'selected' : '' ?>>ระงับ</option>
                    </select>
                    
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-filter mr-1.5"></i> กรอง
                    </button>
                    
                    <?php if ($search || $filterDept || $filterStatus !== ''): ?>
                    <a href="users.php" class="px-4 py-2 bg-gray-100 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors text-center">
                        <i class="fas fa-times mr-1"></i> ล้าง
                    </a>
                    <?php endif; ?>
                </form>

                <!-- Action Buttons -->
                <div class="flex gap-2">
                    <a href="user-form.php" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors whitespace-nowrap">
                        <i class="fas fa-plus mr-1.5"></i> เพิ่มผู้ใช้
                    </a>
                </div>
            </div>
        </div>

        <!-- Bulk Actions Bar -->
        <div id="bulkActionsBar" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <div class="flex items-center justify-between">
                <span class="text-sm text-blue-700">
                    <i class="fas fa-check-square mr-2"></i>
                    เลือกแล้ว <strong id="selectedCount">0</strong> รายการ
                </span>
                <div class="flex gap-2">
                    <form method="POST" class="inline" onsubmit="return confirm('ยืนยันการเปิดใช้งานผู้ใช้ที่เลือก?')">
                        <input type="hidden" name="action" value="bulk_status">
                        <input type="hidden" name="status" value="1">
                        <input type="hidden" name="ids" id="bulkActiveIds">
                        <button type="submit" class="px-3 py-1.5 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700">
                            <i class="fas fa-check mr-1"></i> เปิดใช้งาน
                        </button>
                    </form>
                    
                    <form method="POST" class="inline" onsubmit="return confirm('ยืนยันการระงับผู้ใช้ที่เลือก?')">
                        <input type="hidden" name="action" value="bulk_status">
                        <input type="hidden" name="status" value="0">
                        <input type="hidden" name="ids" id="bulkInactiveIds">
                        <button type="submit" class="px-3 py-1.5 bg-orange-600 text-white text-xs rounded-lg hover:bg-orange-700">
                            <i class="fas fa-ban mr-1"></i> ระงับ
                        </button>
                    </form>
                    
                    <form method="POST" class="inline" onsubmit="return confirm('ยืนยันการลบผู้ใช้ที่เลือก? (ไม่สามารถกู้คืนได้)')">
                        <input type="hidden" name="action" value="bulk_delete">
                        <input type="hidden" name="ids" id="bulkDeleteIds">
                        <button type="submit" class="px-3 py-1.5 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700">
                            <i class="fas fa-trash mr-1"></i> ลบ
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ผู้ใช้งาน</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">หน่วยงาน/ตำแหน่ง</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Roles</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">เข้าระบบล่าสุด</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">สถานะ</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                                <p>ไม่พบข้อมูลผู้ใช้งาน</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <?php if ($user['id'] > 1): ?>
                                <input type="checkbox" class="userCheckbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" value="<?= $user['id'] ?>">
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600"><?= $user['id'] ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                                        <?= mb_strtoupper(mb_substr($user['full_name'], 0, 2, 'UTF-8'), 'UTF-8') ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?= htmlspecialchars($user['full_name']) ?></p>
                                        <p class="text-xs text-gray-500">
                                            <i class="fas fa-user text-xs mr-1"></i><?= htmlspecialchars($user['username']) ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <i class="fas fa-envelope text-xs mr-1"></i><?= htmlspecialchars($user['email']) ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php if ($user['department_display']): ?>
                                <p class="text-gray-900 font-medium"><?= htmlspecialchars($user['department_display']) ?></p>
                                <?php endif; ?>
                                <?php if ($user['position']): ?>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($user['position']) ?></p>
                                <?php endif; ?>
                                <?php if ($user['phone']): ?>
                                <p class="text-xs text-gray-500">
                                    <i class="fas fa-phone text-xs mr-1"></i><?= htmlspecialchars($user['phone']) ?>
                                </p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-700">
                                    <i class="fas fa-shield-alt mr-1"></i>
                                    <?= $user['role_count'] ?> role<?= $user['role_count'] > 1 ? 's' : '' ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <?php if ($user['last_login_at']): ?>
                                <p class="text-xs"><?= date('d/m/Y H:i', strtotime($user['last_login_at'])) ?></p>
                                <p class="text-xs text-gray-400">
                                    <i class="fas fa-sign-in-alt mr-1"></i><?= number_format($user['login_count']) ?> ครั้ง
                                </p>
                                <?php else: ?>
                                <span class="text-xs text-gray-400">ยังไม่เข้าระบบ</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="current_status" value="<?= $user['is_active'] ?>">
                                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $user['is_active'] ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' ?>">
                                        <i class="fas fa-circle text-[6px] mr-1.5"></i>
                                        <?= $user['is_active'] ? 'ใช้งาน' : 'ระงับ' ?>
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="user-form.php?id=<?= $user['id'] ?>" 
                                       class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors" 
                                       title="แก้ไข">
                                        <i class="fas fa-edit text-sm"></i>
                                    </a>
                                    
                                    <form method="POST" class="inline" onsubmit="return confirm('ยืนยันการรีเซ็ตรหัสผ่านเป็น P@ssw0rd?')">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <button type="submit" 
                                                class="p-1.5 text-orange-600 hover:bg-orange-50 rounded transition-colors" 
                                                title="รีเซ็ตรหัสผ่าน">
                                            <i class="fas fa-key text-sm"></i>
                                        </button>
                                    </form>
                                    
                                    <?php if ($user['id'] > 1): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('ยืนยันการลบผู้ใช้งาน?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <button type="submit" 
                                                class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors" 
                                                title="ลบ">
                                            <i class="fas fa-trash text-sm"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="p-1.5 text-gray-300" title="ไม่สามารถลบได้">
                                        <i class="fas fa-lock text-sm"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-between bg-gray-50">
                <div class="text-sm text-gray-600">
                    แสดง <?= number_format($offset + 1) ?>-<?= number_format(min($offset + $perPage, $totalRows)) ?> 
                    จากทั้งหมด <?= number_format($totalRows) ?> รายการ
                </div>
                <div class="flex gap-1">
                    <?php
                    $queryParams = $_GET;
                    for ($i = 1; $i <= $totalPages; $i++):
                        $queryParams['page'] = $i;
                        $url = 'users.php?' . http_build_query($queryParams);
                    ?>
                    <a href="<?= $url ?>" 
                       class="px-3 py-1 rounded <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?> text-sm">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </main>

    <?php include __DIR__ . "/../template-layout/footer.php"; ?>

</div>

<?php include __DIR__ . "/../template-layout/scripts.php"; ?>

<script>
// Bulk selection
const selectAll = document.getElementById('selectAll');
const userCheckboxes = document.querySelectorAll('.userCheckbox');
const bulkActionsBar = document.getElementById('bulkActionsBar');
const selectedCount = document.getElementById('selectedCount');

function updateBulkActions() {
    const checked = Array.from(userCheckboxes).filter(cb => cb.checked);
    const ids = checked.map(cb => cb.value);
    
    if (checked.length > 0) {
        bulkActionsBar.classList.remove('hidden');
        selectedCount.textContent = checked.length;
        document.getElementById('bulkActiveIds').value = ids.join(',');
        document.getElementById('bulkInactiveIds').value = ids.join(',');
        document.getElementById('bulkDeleteIds').value = ids.join(',');
    } else {
        bulkActionsBar.classList.add('hidden');
    }
}

selectAll?.addEventListener('change', function() {
    userCheckboxes.forEach(cb => cb.checked = this.checked);
    updateBulkActions();
});

userCheckboxes.forEach(cb => {
    cb.addEventListener('change', updateBulkActions);
});

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
