<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';
requireLogin();

$db = getDB();
$B = BASE_URL;
$isAjax = (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && stripos((string)$_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$msg = '';
$msgType = '';

$form = [
    'username' => '',
    'email' => '',
    'full_name' => '',
    'phone' => '',
    'department_id' => '',
    'position' => '',
    'is_active' => '1',
];

if ($isEdit) {
    $stmt = $db->prepare("
        SELECT id, username, email, full_name, phone, department_id, position, is_active
        FROM gis_users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ใช้'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        header("Location: {$B}/admin/users.php");
        exit;
    }

    $form = [
        'username' => (string)$user['username'],
        'email' => (string)$user['email'],
        'full_name' => (string)$user['full_name'],
        'phone' => (string)($user['phone'] ?? ''),
        'department_id' => $user['department_id'] === null ? '' : (string)$user['department_id'],
        'position' => (string)($user['position'] ?? ''),
        'is_active' => (string)$user['is_active'],
    ];
}

$departments = $db->query("
    SELECT department_id, department_name
    FROM departments
    WHERE status = 'active'
    ORDER BY level ASC, department_name ASC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['username'] = trim((string)($_POST['username'] ?? ''));
    $form['email'] = trim((string)($_POST['email'] ?? ''));
    $form['full_name'] = trim((string)($_POST['full_name'] ?? ''));
    $form['phone'] = trim((string)($_POST['phone'] ?? ''));
    $form['department_id'] = trim((string)($_POST['department_id'] ?? ''));
    $form['position'] = trim((string)($_POST['position'] ?? ''));
    $form['is_active'] = (string)((int)($_POST['is_active'] ?? 1));

    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

    $errors = [];
    if ($form['username'] === '') $errors[] = 'กรุณาระบุ Username';
    if ($form['full_name'] === '') $errors[] = 'กรุณาระบุชื่อ-นามสกุล';
    if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'รูปแบบ Email ไม่ถูกต้อง';
    if (!$isEdit && $password === '') $errors[] = 'กรุณาระบุรหัสผ่าน';
    if ($password !== '' && strlen($password) < 8) $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    if ($password !== '' && $password !== $passwordConfirm) $errors[] = 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน';

    $departmentId = null;
    if ($form['department_id'] !== '') {
        $departmentId = (int)$form['department_id'];
        if ($departmentId <= 0) {
            $errors[] = 'หน่วยงานไม่ถูกต้อง';
        }
    }

    if (empty($errors)) {
        try {
            if ($isEdit) {
                if ($password !== '') {
                    $sql = "
                        UPDATE gis_users
                        SET username = ?, email = ?, full_name = ?, phone = ?, department_id = ?, position = ?, is_active = ?,
                            password_hash = ?, password_changed_at = NOW()
                        WHERE id = ?
                    ";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $form['username'],
                        $form['email'],
                        $form['full_name'],
                        $form['phone'] !== '' ? $form['phone'] : null,
                        $departmentId,
                        $form['position'] !== '' ? $form['position'] : null,
                        (int)$form['is_active'],
                        password_hash($password, PASSWORD_BCRYPT),
                        $id
                    ]);
                } else {
                    $sql = "
                        UPDATE gis_users
                        SET username = ?, email = ?, full_name = ?, phone = ?, department_id = ?, position = ?, is_active = ?
                        WHERE id = ?
                    ";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $form['username'],
                        $form['email'],
                        $form['full_name'],
                        $form['phone'] !== '' ? $form['phone'] : null,
                        $departmentId,
                        $form['position'] !== '' ? $form['position'] : null,
                        (int)$form['is_active'],
                        $id
                    ]);
                }
                $msg = 'บันทึกข้อมูลผู้ใช้เรียบร้อยแล้ว';
            } else {
                $sql = "
                    INSERT INTO gis_users
                    (username, email, password_hash, full_name, phone, department_id, position, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $form['username'],
                    $form['email'],
                    password_hash($password, PASSWORD_BCRYPT),
                    $form['full_name'],
                    $form['phone'] !== '' ? $form['phone'] : null,
                    $departmentId,
                    $form['position'] !== '' ? $form['position'] : null,
                    (int)$form['is_active']
                ]);
                $id = (int)$db->lastInsertId();
                $isEdit = true;
                $msg = 'สร้างผู้ใช้เรียบร้อยแล้ว';
            }
            $msgType = 'success';

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => $msg,
                    'id' => (int)$id
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $msg = 'Username หรือ Email ซ้ำในระบบ';
            } else {
                $msg = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            }
            $msgType = 'error';

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    } else {
        $msg = implode('<br>', $errors);
        $msgType = 'error';

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => implode("\n", $errors)], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

$pageTitle = $isEdit ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้';
?>
<?php include __DIR__ . "/../template-layout/header.php"; ?>
<?php include __DIR__ . "/../template-layout/sidebar.php"; ?>

<div id="mainContent" class="main-expanded transition-all duration-300 min-h-screen flex flex-col">
    <?php include __DIR__ . "/../template-layout/navbar.php"; ?>

    <main class="flex-1 p-6 lg:p-8">
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <h1 class="text-2xl font-semibold text-gray-900"><?= htmlspecialchars($pageTitle) ?></h1>
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="<?= $B ?>/admin/index.php" class="hover:text-gray-700">หน้าหลัก</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <a href="<?= $B ?>/admin/users.php" class="hover:text-gray-700">Users</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900 font-medium"><?= htmlspecialchars($pageTitle) ?></span>
                </nav>
            </div>
        </div>

        <?php if ($msg): ?>
        <div class="mb-6 p-4 rounded-lg <?= $msgType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
            <?= $msg ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <form id="userForm" method="POST" class="p-6 lg:p-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username <span class="text-red-500">*</span></label>
                        <input type="text" name="username" required value="<?= htmlspecialchars($form['username']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($form['email']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                        <input type="text" name="full_name" required value="<?= htmlspecialchars($form['full_name']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">เบอร์โทร</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($form['phone']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">หน่วยงาน</label>
                        <select name="department_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- ไม่ระบุ --</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?= (int)$dept['department_id'] ?>" <?= $form['department_id'] !== '' && (int)$form['department_id'] === (int)$dept['department_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['department_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ตำแหน่ง</label>
                        <input type="text" name="position" value="<?= htmlspecialchars($form['position']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">รหัสผ่าน <?= $isEdit ? '(เว้นว่างถ้าไม่เปลี่ยน)' : '<span class="text-red-500">*</span>' ?></label>
                        <input type="password" name="password" <?= $isEdit ? '' : 'required' ?> class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ยืนยันรหัสผ่าน</label>
                        <input type="password" name="password_confirm" <?= $isEdit ? '' : 'required' ?> class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">สถานะ</label>
                        <select name="is_active" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="1" <?= $form['is_active'] === '1' ? 'selected' : '' ?>>ใช้งาน</option>
                            <option value="0" <?= $form['is_active'] === '0' ? 'selected' : '' ?>>ระงับ</option>
                        </select>
                    </div>
                </div>

                <div class="pt-2 flex items-center gap-3">
                    <button id="saveBtn" type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-1.5"></i> บันทึก
                    </button>
                    <a href="<?= $B ?>/admin/users.php" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                        ย้อนกลับ
                    </a>
                </div>
            </form>
        </div>
    </main>

    <?php include __DIR__ . "/../template-layout/footer.php"; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(() => {
    const form = document.getElementById('userForm');
    const saveBtn = document.getElementById('saveBtn');
    if (!form || !saveBtn) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const oldHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> กำลังบันทึก...';

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: data.message || 'บันทึกข้อมูลเรียบร้อยแล้ว',
                    confirmButtonText: 'ตกลง'
                });

                if (data.id) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('id', String(data.id));
                    window.history.replaceState({}, '', url.toString());
                }
            } else {
                await Swal.fire({
                    icon: 'error',
                    title: 'ไม่สำเร็จ',
                    text: data.message || 'ไม่สามารถบันทึกข้อมูลได้',
                    confirmButtonText: 'ตกลง'
                });
            }
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้',
                confirmButtonText: 'ตกลง'
            });
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = oldHtml;
        }
    });
})();
</script>
<?php include __DIR__ . "/../template-layout/scripts.php"; ?>
</body>
</html>
