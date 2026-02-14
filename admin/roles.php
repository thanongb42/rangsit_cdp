<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$db = getDB();
$B = BASE_URL;

// Handle actions
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Delete role
    if ($action === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $role = $db->prepare("SELECT is_system FROM gis_roles WHERE id=?")->execute([$id]);
        $role = $db->query("SELECT is_system FROM gis_roles WHERE id={$id}")->fetch();
        
        if (!$role || $role['is_system']) {
            $msg = 'ไม่สามารถลบ System Role ได้';
            $msgType = 'error';
        } else {
            $db->prepare("DELETE FROM gis_roles WHERE id=?")->execute([$id]);
            $msg = 'ลบ Role สำเร็จ';
            $msgType = 'success';
        }
    }

    // Add/Edit role
    if ($action === 'save' && isset($_POST['role_name'])) {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $roleName = trim($_POST['role_name']);
        $roleSlug = trim($_POST['role_slug']);
        $description = trim($_POST['description']);
        $priority = (int)$_POST['priority'];
        
        if (empty($roleName) || empty($roleSlug)) {
            $msg = 'กรุณากรอกข้อมูลให้ครบถ้วน';
            $msgType = 'error';
        } else {
            try {
                if ($id > 0) {
                    // Update
                    $stmt = $db->prepare("UPDATE gis_roles SET role_name=?, role_slug=?, description=?, priority=? WHERE id=? AND is_system=0");
                    $stmt->execute([$roleName, $roleSlug, $description, $priority, $id]);
                    $msg = 'แก้ไข Role สำเร็จ';
                } else {
                    // Insert
                    $stmt = $db->prepare("INSERT INTO gis_roles (role_name, role_slug, description, priority, is_system) VALUES (?, ?, ?, ?, 0)");
                    $stmt->execute([$roleName, $roleSlug, $description, $priority]);
                    $msg = 'เพิ่ม Role สำเร็จ';
                }
                $msgType = 'success';
            } catch (PDOException $e) {
                $msg = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                $msgType = 'error';
            }
        }
    }
}

// Get all roles with user count
$roles = $db->query("
    SELECT r.*,
           (SELECT COUNT(*) FROM gis_user_roles WHERE role_id = r.id) as user_count,
           (SELECT COUNT(*) FROM gis_role_permissions WHERE role_id = r.id) as permission_count
    FROM gis_roles r
    ORDER BY r.priority DESC, r.id ASC
")->fetchAll();

// Stats
$stats = [
    'total_roles' => count($roles),
    'system_roles' => count(array_filter($roles, fn($r) => $r['is_system'])),
    'custom_roles' => count(array_filter($roles, fn($r) => !$r['is_system'])),
    'total_users' => $db->query("SELECT COUNT(DISTINCT user_id) FROM gis_user_roles")->fetchColumn()
];

// Get role for editing if ID provided
$editRole = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editRole = $db->query("SELECT * FROM gis_roles WHERE id={$editId}")->fetch();
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
                    <h1 class="text-2xl font-semibold text-gray-900">จัดการบทบาท (Roles)</h1>
                    <p class="text-sm text-gray-500 mt-1">Role & Permission Management</p>
                </div>
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="<?= $B ?>/admin/index.php" class="hover:text-gray-700">หน้าหลัก</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900 font-medium">Roles</span>
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
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-5 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-shield-alt text-3xl opacity-80"></i>
                    <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded">Total</span>
                </div>
                <p class="text-3xl font-bold"><?= number_format($stats['total_roles']) ?></p>
                <p class="text-sm opacity-90">Roles ทั้งหมด</p>
            </div>

            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-5 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-lock text-3xl opacity-80"></i>
                    <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded">System</span>
                </div>
                <p class="text-3xl font-bold"><?= number_format($stats['system_roles']) ?></p>
                <p class="text-sm opacity-90">System Roles</p>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-5 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-cog text-3xl opacity-80"></i>
                    <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded">Custom</span>
                </div>
                <p class="text-3xl font-bold"><?= number_format($stats['custom_roles']) ?></p>
                <p class="text-sm opacity-90">Custom Roles</p>
            </div>

            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-5 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-users text-3xl opacity-80"></i>
                    <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded">Active</span>
                </div>
                <p class="text-3xl font-bold"><?= number_format($stats['total_users']) ?></p>
                <p class="text-sm opacity-90">Users ที่มี Role</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Role Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl border border-gray-200 p-6 sticky top-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-plus-circle text-blue-600 mr-2"></i>
                        <?= $editRole ? 'แก้ไข Role' : 'เพิ่ม Role ใหม่' ?>
                    </h2>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="save">
                        <?php if ($editRole): ?>
                        <input type="hidden" name="id" value="<?= $editRole['id'] ?>">
                        <?php endif; ?>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ชื่อ Role <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="role_name" 
                                   required 
                                   value="<?= $editRole ? htmlspecialchars($editRole['role_name']) : '' ?>"
                                   placeholder="เช่น Content Manager"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Slug <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="role_slug" 
                                   required 
                                   value="<?= $editRole ? htmlspecialchars($editRole['role_slug']) : '' ?>"
                                   placeholder="เช่น content_manager"
                                   pattern="[a-z0-9_]+"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">ใช้ตัวพิมพ์เล็ก, ตัวเลข, _ เท่านั้น</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                คำอธิบาย
                            </label>
                            <textarea name="description" 
                                      rows="3" 
                                      placeholder="อธิบายหน้าที่และความรับผิดชอบ..."
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= $editRole ? htmlspecialchars($editRole['description']) : '' ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Priority (ระดับสิทธิ์)
                            </label>
                            <input type="number" 
                                   name="priority" 
                                   min="0" 
                                   max="100" 
                                   value="<?= $editRole ? $editRole['priority'] : 50 ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">ยิ่งสูง = สิทธิ์เยอะ (0-100)</p>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                                <i class="fas fa-save mr-2"></i>
                                <?= $editRole ? 'บันทึกการแก้ไข' : 'เพิ่ม Role' ?>
                            </button>
                            <?php if ($editRole): ?>
                            <a href="roles.php" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                                <i class="fas fa-times"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if ($editRole && !$editRole['is_system']): ?>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <form method="POST" onsubmit="return confirm('ยืนยันการลบ Role นี้?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $editRole['id'] ?>">
                            <button type="submit" class="w-full px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-colors font-medium">
                                <i class="fas fa-trash mr-2"></i>
                                ลบ Role นี้
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Roles List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-list text-purple-600 mr-2"></i>
                            รายการ Roles
                        </h2>
                    </div>

                    <div class="divide-y divide-gray-200">
                        <?php if (empty($roles)): ?>
                        <div class="p-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                            <p>ไม่มี Role ในระบบ</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($roles as $role): ?>
                        <div class="p-5 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg flex items-center justify-center text-white">
                                            <i class="fas fa-shield-alt"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                                                <?= htmlspecialchars($role['role_name']) ?>
                                                <?php if ($role['is_system']): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700">
                                                    <i class="fas fa-lock text-[10px] mr-1"></i>
                                                    System
                                                </span>
                                                <?php endif; ?>
                                            </h3>
                                            <p class="text-xs text-gray-500">
                                                <i class="fas fa-tag mr-1"></i><?= htmlspecialchars($role['role_slug']) ?>
                                            </p>
                                        </div>
                                    </div>

                                    <?php if ($role['description']): ?>
                                    <p class="text-sm text-gray-600 mb-3 ml-13">
                                        <?= htmlspecialchars($role['description']) ?>
                                    </p>
                                    <?php endif; ?>

                                    <div class="flex items-center gap-4 ml-13">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-700">
                                                <i class="fas fa-users text-[10px] mr-1"></i>
                                                <?= number_format($role['user_count']) ?> users
                                            </span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">
                                                <i class="fas fa-key text-[10px] mr-1"></i>
                                                <?= number_format($role['permission_count']) ?> permissions
                                            </span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-orange-100 text-orange-700">
                                                <i class="fas fa-signal text-[10px] mr-1"></i>
                                                Priority: <?= $role['priority'] ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 ml-4">
                                    <a href="role-permissions.php?id=<?= $role['id'] ?>" 
                                       class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" 
                                       title="จัดการสิทธิ์">
                                        <i class="fas fa-key"></i>
                                    </a>
                                    
                                    <?php if (!$role['is_system']): ?>
                                    <a href="roles.php?edit=<?= $role['id'] ?>" 
                                       class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors" 
                                       title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <form method="POST" class="inline" onsubmit="return confirm('ยืนยันการลบ Role นี้?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $role['id'] ?>">
                                        <button type="submit" 
                                                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" 
                                                title="ลบ">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="p-2 text-gray-300" title="ไม่สามารถแก้ไข System Role">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <h3 class="font-semibold text-blue-900 mb-2 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        เกี่ยวกับ Roles & Permissions
                    </h3>
                    <div class="text-sm text-blue-800 space-y-1">
                        <p><strong>System Roles:</strong> เป็น Role พื้นฐานที่สร้างโดยระบบ ไม่สามารถแก้ไขหรือลบได้</p>
                        <p><strong>Custom Roles:</strong> สามารถสร้างและปรับแต่งได้ตามความต้องการ</p>
                        <p><strong>Priority:</strong> ยิ่งค่าสูง = สิทธิ์เยอะ ใช้เมื่อผู้ใช้มีหลาย Role</p>
                        <p><strong>Permissions:</strong> กำหนดสิทธิ์ละเอียดได้ในหน้า Role Permissions</p>
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

// Auto-generate slug from role name
const roleNameInput = document.querySelector('input[name="role_name"]');
const roleSlugInput = document.querySelector('input[name="role_slug"]');

if (roleNameInput && roleSlugInput && !roleSlugInput.value) {
    roleNameInput.addEventListener('input', function() {
        const slug = this.value
            .toLowerCase()
            .replace(/[^a-z0-9\s]/g, '')
            .replace(/\s+/g, '_');
        roleSlugInput.value = slug;
    });
}
</script>

</body>
</html>
