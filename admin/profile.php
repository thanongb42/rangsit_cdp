<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';
requireLogin();

$db = getDB();
$B = BASE_URL;
$userId = $_SESSION['user_id'];

// Load current user data from DB
$stmt = $db->prepare("
    SELECT u.*, d.department_name
    FROM gis_users u
    LEFT JOIN departments d ON d.department_id = u.department_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: {$B}/logout.php");
    exit;
}

// Load roles
$roles = $db->prepare("
    SELECT r.role_name, r.role_slug, r.priority
    FROM gis_user_roles ur
    JOIN gis_roles r ON r.id = ur.role_id
    WHERE ur.user_id = ?
    ORDER BY r.priority DESC
");
$roles->execute([$userId]);
$userRoles = $roles->fetchAll();

// Handle form submission
$msg = '';
$msgType = '';
$tab = $_GET['tab'] ?? 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        $errors = [];
        if ($fullName === '') $errors[] = 'กรุณาระบุชื่อ-นามสกุล';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'รูปแบบ Email ไม่ถูกต้อง';

        if (empty($errors)) {
            try {
                $stmt = $db->prepare("UPDATE gis_users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$fullName, $email, $phone ?: null, $userId]);

                // Update session
                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;

                $user['full_name'] = $fullName;
                $user['email'] = $email;
                $user['phone'] = $phone;

                auditLog($userId, 'edit', 'user', $userId, $fullName, ['field' => 'profile']);

                $msg = 'บันทึกข้อมูลโปรไฟล์เรียบร้อยแล้ว';
                $msgType = 'success';
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $msg = 'Email นี้มีผู้ใช้งานแล้ว';
                } else {
                    $msg = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                }
                $msgType = 'error';
            }
        } else {
            $msg = implode('<br>', $errors);
            $msgType = 'error';
        }
        $tab = 'info';

    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];
        if ($currentPassword === '') $errors[] = 'กรุณาระบุรหัสผ่านปัจจุบัน';
        if ($newPassword === '') $errors[] = 'กรุณาระบุรหัสผ่านใหม่';
        if (strlen($newPassword) < 8) $errors[] = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร';
        if ($newPassword !== $confirmPassword) $errors[] = 'รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน';

        if (empty($errors)) {
            if (!password_verify($currentPassword, $user['password_hash'])) {
                $msg = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
                $msgType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE gis_users SET password_hash = ?, password_changed_at = NOW() WHERE id = ?");
                $stmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $userId]);

                auditLog($userId, 'edit', 'user', $userId, $user['full_name'], ['field' => 'password']);

                $msg = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
                $msgType = 'success';
            }
        } else {
            $msg = implode('<br>', $errors);
            $msgType = 'error';
        }
        $tab = 'password';
    }
}

$defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&background=4f46e5&color=fff&size=128';
$avatarUrl = $user['avatar']
    ? $B . '/' . htmlspecialchars($user['avatar'])
    : $defaultAvatar;
$hasAvatar = !empty($user['avatar']);

$roleColors = [
    'super_admin'   => 'bg-red-100 text-red-700 border-red-200',
    'admin'         => 'bg-orange-100 text-orange-700 border-orange-200',
    'layer_manager' => 'bg-blue-100 text-blue-700 border-blue-200',
    'editor'        => 'bg-green-100 text-green-700 border-green-200',
    'viewer'        => 'bg-gray-100 text-gray-700 border-gray-200',
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
                    <h1 class="text-2xl font-semibold text-gray-900">โปรไฟล์ของฉัน</h1>
                    <p class="text-sm text-gray-500 mt-1">จัดการข้อมูลส่วนตัวและรหัสผ่าน</p>
                </div>
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="<?= $B ?>/admin/index.php" class="hover:text-gray-700">หน้าหลัก</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900 font-medium">โปรไฟล์ของฉัน</span>
                </nav>
            </div>
        </div>

        <?php if ($msg): ?>
        <div class="mb-6 p-4 rounded-lg <?= $msgType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
            <div class="flex items-center gap-2">
                <i class="fas <?= $msgType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <span><?= $msg ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Profile Card (Left) -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <!-- Cover -->
                    <div class="h-24 bg-gradient-to-r from-indigo-500 to-purple-600"></div>

                    <!-- Avatar & Info -->
                    <div class="px-6 pb-6">
                        <div class="-mt-12 mb-4 relative inline-block group">
                            <img id="profileAvatar" src="<?= $avatarUrl ?>"
                                 alt="Avatar"
                                 class="w-24 h-24 rounded-full border-4 border-white shadow-md object-cover">
                            <!-- Upload overlay -->
                            <label for="avatarInput"
                                   class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-full opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer border-4 border-transparent">
                                <i class="fas fa-camera text-white text-lg"></i>
                            </label>
                            <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                        </div>
                        <div class="flex items-center gap-2 mb-3">
                            <button type="button" onclick="document.getElementById('avatarInput').click()"
                                    class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">
                                <i class="fas fa-upload mr-1"></i> อัปโหลดรูป
                            </button>
                            <?php if ($hasAvatar): ?>
                            <button type="button" id="removeAvatarBtn" onclick="removeAvatar()"
                                    class="text-xs text-red-500 hover:text-red-600 font-medium">
                                <i class="fas fa-trash-alt mr-1"></i> ลบรูป
                            </button>
                            <?php endif; ?>
                        </div>

                        <h2 class="text-xl font-semibold text-gray-900"><?= htmlspecialchars($user['full_name']) ?></h2>
                        <p class="text-sm text-gray-500 mt-0.5">@<?= htmlspecialchars($user['username']) ?></p>

                        <!-- Roles -->
                        <div class="flex flex-wrap gap-2 mt-3">
                            <?php foreach ($userRoles as $role): ?>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border <?= $roleColors[$role['role_slug']] ?? 'bg-gray-100 text-gray-700 border-gray-200' ?>">
                                <i class="fas fa-shield-alt mr-1 text-[10px]"></i>
                                <?= htmlspecialchars($role['role_name']) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>

                        <!-- Info List -->
                        <div class="mt-6 space-y-3">
                            <div class="flex items-center gap-3 text-sm">
                                <i class="fas fa-envelope text-gray-400 w-4"></i>
                                <span class="text-gray-600"><?= htmlspecialchars($user['email']) ?></span>
                            </div>
                            <?php if ($user['phone']): ?>
                            <div class="flex items-center gap-3 text-sm">
                                <i class="fas fa-phone text-gray-400 w-4"></i>
                                <span class="text-gray-600"><?= htmlspecialchars($user['phone']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($user['department_name'] ?? null): ?>
                            <div class="flex items-center gap-3 text-sm">
                                <i class="fas fa-building text-gray-400 w-4"></i>
                                <span class="text-gray-600"><?= htmlspecialchars($user['department_name']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($user['position']): ?>
                            <div class="flex items-center gap-3 text-sm">
                                <i class="fas fa-briefcase text-gray-400 w-4"></i>
                                <span class="text-gray-600"><?= htmlspecialchars($user['position']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Stats -->
                        <div class="mt-6 pt-4 border-t border-gray-100 grid grid-cols-2 gap-4">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900"><?= number_format($user['login_count'] ?? 0) ?></p>
                                <p class="text-xs text-gray-500 mt-0.5">เข้าสู่ระบบ</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-medium text-gray-900">
                                    <?= $user['last_login_at'] ? date('d/m/Y', strtotime($user['last_login_at'])) : '-' ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">เข้าล่าสุด</p>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center gap-2 text-xs text-gray-400">
                                <i class="fas fa-calendar-alt"></i>
                                <span>สมาชิกตั้งแต่ <?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Forms (Right) -->
            <div class="lg:col-span-2">
                <!-- Tabs -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button onclick="switchTab('info')" id="tab-info"
                                    class="tab-btn px-6 py-3.5 text-sm font-medium border-b-2 transition-colors <?= $tab === 'info' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                                <i class="fas fa-user mr-2"></i> ข้อมูลส่วนตัว
                            </button>
                            <button onclick="switchTab('password')" id="tab-password"
                                    class="tab-btn px-6 py-3.5 text-sm font-medium border-b-2 transition-colors <?= $tab === 'password' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                                <i class="fas fa-lock mr-2"></i> เปลี่ยนรหัสผ่าน
                            </button>
                        </nav>
                    </div>

                    <!-- Tab: Personal Info -->
                    <div id="panel-info" class="p-6 lg:p-8 <?= $tab !== 'info' ? 'hidden' : '' ?>">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                                    <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled
                                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed">
                                    <p class="text-xs text-gray-400 mt-1">Username ไม่สามารถเปลี่ยนได้</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                                    <input type="text" name="full_name" required
                                           value="<?= htmlspecialchars($user['full_name']) ?>"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                                    <input type="email" name="email" required
                                           value="<?= htmlspecialchars($user['email']) ?>"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">เบอร์โทร</label>
                                    <input type="text" name="phone"
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                           placeholder="0xx-xxx-xxxx"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">หน่วยงาน</label>
                                        <input type="text" value="<?= htmlspecialchars($user['department_name'] ?? '-') ?>" disabled
                                               class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed">
                                        <p class="text-xs text-gray-400 mt-1">ติดต่อ Admin เพื่อเปลี่ยนหน่วยงาน</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">ตำแหน่ง</label>
                                        <input type="text" value="<?= htmlspecialchars($user['position'] ?? '-') ?>" disabled
                                               class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed">
                                        <p class="text-xs text-gray-400 mt-1">ติดต่อ Admin เพื่อเปลี่ยนตำแหน่ง</p>
                                    </div>
                                </div>

                                <div class="pt-2">
                                    <button type="submit"
                                            class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium text-sm">
                                        <i class="fas fa-save mr-2"></i> บันทึกข้อมูล
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Tab: Change Password -->
                    <div id="panel-password" class="p-6 lg:p-8 <?= $tab !== 'password' ? 'hidden' : '' ?>">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">รหัสผ่านปัจจุบัน <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="password" name="current_password" required id="currentPwd"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 pr-10">
                                        <button type="button" onclick="togglePwd('currentPwd')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-eye text-sm"></i>
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">รหัสผ่านใหม่ <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="password" name="new_password" required id="newPwd" minlength="8"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 pr-10">
                                        <button type="button" onclick="togglePwd('newPwd')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-eye text-sm"></i>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">ต้องมีอย่างน้อย 8 ตัวอักษร</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">ยืนยันรหัสผ่านใหม่ <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="password" name="confirm_password" required id="confirmPwd" minlength="8"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 pr-10">
                                        <button type="button" onclick="togglePwd('confirmPwd')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-eye text-sm"></i>
                                        </button>
                                    </div>
                                </div>

                                <?php if ($user['password_changed_at']): ?>
                                <div class="flex items-center gap-2 text-xs text-gray-400">
                                    <i class="fas fa-info-circle"></i>
                                    <span>เปลี่ยนรหัสผ่านล่าสุด: <?= date('d/m/Y H:i', strtotime($user['password_changed_at'])) ?></span>
                                </div>
                                <?php endif; ?>

                                <div class="pt-2">
                                    <button type="submit"
                                            class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium text-sm">
                                        <i class="fas fa-key mr-2"></i> เปลี่ยนรหัสผ่าน
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . "/../template-layout/footer.php"; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php include __DIR__ . "/../template-layout/scripts.php"; ?>

<script>
const BASE = '<?= $B ?>';
const defaultAvatarUrl = '<?= $defaultAvatar ?>';

// Avatar upload
document.getElementById('avatarInput').addEventListener('change', async function() {
    const file = this.files[0];
    if (!file) return;

    // Client-side validation
    const maxSize = 2 * 1024 * 1024;
    if (file.size > maxSize) {
        Swal.fire({ icon: 'error', title: 'ไฟล์ใหญ่เกินไป', text: 'ขนาดไฟล์สูงสุด 2MB', confirmButtonColor: '#4f46e5' });
        this.value = '';
        return;
    }
    const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowed.includes(file.type)) {
        Swal.fire({ icon: 'error', title: 'ไฟล์ไม่รองรับ', text: 'รองรับเฉพาะ JPG, PNG, GIF, WEBP', confirmButtonColor: '#4f46e5' });
        this.value = '';
        return;
    }

    // Preview immediately
    const reader = new FileReader();
    reader.onload = (e) => {
        document.getElementById('profileAvatar').src = e.target.result;
    };
    reader.readAsDataURL(file);

    // Upload
    const formData = new FormData();
    formData.append('avatar', file);
    formData.append('action', 'upload');

    Swal.fire({ title: 'กำลังอัปโหลด...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(BASE + '/admin/upload-avatar.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            const url = data.avatar_url + '?t=' + Date.now();
            document.getElementById('profileAvatar').src = url;
            const navAvatar = document.getElementById('navbarAvatar');
            if (navAvatar) navAvatar.src = url;

            // Show/ensure remove button exists
            let removeBtn = document.getElementById('removeAvatarBtn');
            if (!removeBtn) {
                const container = document.getElementById('profileAvatar').closest('.px-6').querySelector('.flex.items-center.gap-2');
                if (container) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.id = 'removeAvatarBtn';
                    btn.onclick = removeAvatar;
                    btn.className = 'text-xs text-red-500 hover:text-red-600 font-medium';
                    btn.innerHTML = '<i class="fas fa-trash-alt mr-1"></i> ลบรูป';
                    container.appendChild(btn);
                }
            }

            Swal.fire({ icon: 'success', title: 'สำเร็จ', text: data.message, timer: 1500, showConfirmButton: false });
        } else {
            document.getElementById('profileAvatar').src = '<?= $avatarUrl ?>';
            Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: data.message, confirmButtonColor: '#4f46e5' });
        }
    } catch (err) {
        document.getElementById('profileAvatar').src = '<?= $avatarUrl ?>';
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', confirmButtonColor: '#4f46e5' });
    }

    this.value = '';
});

// Remove avatar
async function removeAvatar() {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'ลบรูปโปรไฟล์?',
        text: 'ต้องการลบรูปโปรไฟล์หรือไม่',
        showCancelButton: true,
        confirmButtonText: 'ลบรูป',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
    });

    if (!result.isConfirmed) return;

    Swal.fire({ title: 'กำลังลบ...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const formData = new FormData();
        formData.append('action', 'remove');

        const res = await fetch(BASE + '/admin/upload-avatar.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            document.getElementById('profileAvatar').src = defaultAvatarUrl;
            const navAvatar = document.getElementById('navbarAvatar');
            if (navAvatar) navAvatar.src = defaultAvatarUrl;

            const removeBtn = document.getElementById('removeAvatarBtn');
            if (removeBtn) removeBtn.remove();

            Swal.fire({ icon: 'success', title: 'สำเร็จ', text: data.message, timer: 1500, showConfirmButton: false });
        } else {
            Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: data.message, confirmButtonColor: '#4f46e5' });
        }
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', confirmButtonColor: '#4f46e5' });
    }
}

function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-indigo-500', 'text-indigo-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    document.getElementById('tab-' + tab).classList.remove('border-transparent', 'text-gray-500');
    document.getElementById('tab-' + tab).classList.add('border-indigo-500', 'text-indigo-600');

    document.getElementById('panel-info').classList.toggle('hidden', tab !== 'info');
    document.getElementById('panel-password').classList.toggle('hidden', tab !== 'password');
}

function togglePwd(id) {
    const input = document.getElementById(id);
    const icon = input.nextElementSibling.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
