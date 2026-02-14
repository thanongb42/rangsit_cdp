<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$db = getDB();
$B = BASE_URL;

// Filters
$filterAction = $_GET['action'] ?? '';
$filterResource = $_GET['resource'] ?? '';
$filterUser = $_GET['user'] ?? '';
$search = $_GET['q'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($filterAction) {
    $where[] = "a.action = ?";
    $params[] = $filterAction;
}

if ($filterResource) {
    $where[] = "a.resource_type = ?";
    $params[] = $filterResource;
}

if ($filterUser) {
    $where[] = "a.user_id = ?";
    $params[] = (int)$filterUser;
}

if ($search) {
    $where[] = "(a.resource_title LIKE ? OR a.details LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($dateFrom) {
    $where[] = "DATE(a.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = "DATE(a.created_at) <= ?";
    $params[] = $dateTo;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM gis_audit_log a {$whereSQL}");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

// Fetch logs
$stmt = $db->prepare("
    SELECT a.*, u.username, u.full_name
    FROM gis_audit_log a
    LEFT JOIN gis_users u ON u.id = a.user_id
    {$whereSQL}
    ORDER BY a.id DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get filter options
$actions = $db->query("SELECT DISTINCT action FROM gis_audit_log ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
$resources = $db->query("SELECT DISTINCT resource_type FROM gis_audit_log WHERE resource_type IS NOT NULL ORDER BY resource_type")->fetchAll(PDO::FETCH_COLUMN);
$users = $db->query("SELECT id, username, full_name FROM gis_users ORDER BY username")->fetchAll();

// Stats
$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        COUNT(DISTINCT user_id) as unique_users,
        COUNT(DISTINCT DATE(created_at)) as active_days,
        SUM(action='login') as logins,
        SUM(action='create') as creates,
        SUM(action='edit') as edits,
        SUM(action='delete') as deletes
    FROM gis_audit_log
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetch();

// Action colors
function getActionColor($action) {
    $colors = [
        'login' => 'blue',
        'logout' => 'gray',
        'create' => 'green',
        'edit' => 'yellow',
        'delete' => 'red',
        'import' => 'purple',
        'export' => 'indigo',
        'assign_role' => 'pink',
        'system_init' => 'cyan'
    ];
    return $colors[$action] ?? 'gray';
}

// Action icons
function getActionIcon($action) {
    $icons = [
        'login' => 'fa-sign-in-alt',
        'logout' => 'fa-sign-out-alt',
        'create' => 'fa-plus-circle',
        'edit' => 'fa-edit',
        'delete' => 'fa-trash',
        'import' => 'fa-file-import',
        'export' => 'fa-file-export',
        'assign_role' => 'fa-user-shield',
        'system_init' => 'fa-cog'
    ];
    return $icons[$action] ?? 'fa-circle';
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
                    <h1 class="text-2xl font-semibold text-gray-900">Audit Log</h1>
                    <p class="text-sm text-gray-500 mt-1">บันทึกการใช้งานระบบทั้งหมด</p>
                </div>
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="<?= $B ?>/admin/index.php" class="hover:text-gray-700">หน้าหลัก</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900 font-medium">Audit Log</span>
                </nav>
            </div>
        </div>

        <!-- Stats Cards (Last 30 days) -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-list text-gray-600 text-sm"></i>
                    <p class="text-xs text-gray-500">Total</p>
                </div>
                <p class="text-xl font-bold text-gray-900"><?= number_format($stats['total']) ?></p>
            </div>
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-users text-blue-600 text-sm"></i>
                    <p class="text-xs text-gray-500">Users</p>
                </div>
                <p class="text-xl font-bold text-gray-900"><?= number_format($stats['unique_users']) ?></p>
            </div>
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-sign-in-alt text-green-600 text-sm"></i>
                    <p class="text-xs text-gray-500">Logins</p>
                </div>
                <p class="text-xl font-bold text-gray-900"><?= number_format($stats['logins']) ?></p>
            </div>
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-plus-circle text-green-600 text-sm"></i>
                    <p class="text-xs text-gray-500">Creates</p>
                </div>
                <p class="text-xl font-bold text-gray-900"><?= number_format($stats['creates']) ?></p>
            </div>
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-edit text-yellow-600 text-sm"></i>
                    <p class="text-xs text-gray-500">Edits</p>
                </div>
                <p class="text-xl font-bold text-gray-900"><?= number_format($stats['edits']) ?></p>
            </div>
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-trash text-red-600 text-sm"></i>
                    <p class="text-xs text-gray-500">Deletes</p>
                </div>
                <p class="text-xl font-bold text-gray-900"><?= number_format($stats['deletes']) ?></p>
            </div>
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-calendar text-purple-600 text-sm"></i>
                    <p class="text-xs text-gray-500">Days</p>
                </div>
                <p class="text-xl font-bold text-gray-900"><?= number_format($stats['active_days']) ?></p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <!-- Search -->
                    <div class="lg:col-span-3">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="text" 
                                   name="q" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="ค้นหาใน resource title หรือ details..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- Action Filter -->
                    <div>
                        <select name="action" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">ทุก Action</option>
                            <?php foreach ($actions as $act): ?>
                            <option value="<?= htmlspecialchars($act) ?>" <?= $filterAction === $act ? 'selected' : '' ?>>
                                <?= htmlspecialchars($act) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Resource Filter -->
                    <div>
                        <select name="resource" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">ทุก Resource</option>
                            <?php foreach ($resources as $res): ?>
                            <option value="<?= htmlspecialchars($res) ?>" <?= $filterResource === $res ? 'selected' : '' ?>>
                                <?= htmlspecialchars($res) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- User Filter -->
                    <div>
                        <select name="user" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">ทุกผู้ใช้</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= $filterUser == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($user['username']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <input type="date" 
                               name="date_from" 
                               value="<?= htmlspecialchars($dateFrom) ?>"
                               placeholder="จากวันที่"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Date To -->
                    <div>
                        <input type="date" 
                               name="date_to" 
                               value="<?= htmlspecialchars($dateTo) ?>"
                               placeholder="ถึงวันที่"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-filter mr-1.5"></i> กรอง
                        </button>
                        <?php if ($search || $filterAction || $filterResource || $filterUser || $dateFrom || $dateTo): ?>
                        <a href="audit-log.php" class="px-4 py-2 bg-gray-100 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="fas fa-times"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Audit Log Table -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-16">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-32">เวลา</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-24">Action</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-32">ผู้ใช้</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-28">Resource</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">รายละเอียด</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-32">IP / Agent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                                <p>ไม่พบบันทึก</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <?php 
                        $color = getActionColor($log['action']); 
                        $icon = getActionIcon($log['action']);
                        ?>
                        <tr class="hover:bg-gray-50 text-sm">
                            <td class="px-4 py-3 text-gray-600">#<?= $log['id'] ?></td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                <?= date('d/m/Y', strtotime($log['created_at'])) ?><br>
                                <span class="text-gray-400"><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-<?= $color ?>-100 text-<?= $color ?>-700">
                                    <i class="fas <?= $icon ?> text-[10px] mr-1"></i>
                                    <?= htmlspecialchars($log['action']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($log['user_id']): ?>
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white text-xs font-semibold">
                                        <?= mb_strtoupper(mb_substr($log['full_name'] ?? 'U', 0, 1, 'UTF-8'), 'UTF-8') ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 text-xs"><?= htmlspecialchars($log['full_name'] ?? 'Unknown') ?></p>
                                        <p class="text-[10px] text-gray-500"><?= htmlspecialchars($log['username'] ?? '') ?></p>
                                    </div>
                                </div>
                                <?php else: ?>
                                <span class="text-xs text-gray-400 italic">System</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($log['resource_type']): ?>
                                <p class="font-medium text-gray-900 text-xs"><?= htmlspecialchars($log['resource_type']) ?></p>
                                <?php if ($log['resource_id']): ?>
                                <p class="text-[10px] text-gray-500">ID: <?= $log['resource_id'] ?></p>
                                <?php endif; ?>
                                <?php else: ?>
                                <span class="text-xs text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($log['resource_title']): ?>
                                <p class="text-gray-900 font-medium mb-1"><?= htmlspecialchars($log['resource_title']) ?></p>
                                <?php endif; ?>
                                <?php if ($log['details']): ?>
                                <details class="text-xs text-gray-600">
                                    <summary class="cursor-pointer hover:text-blue-600">
                                        <i class="fas fa-code text-[10px] mr-1"></i>
                                        View JSON
                                    </summary>
                                    <pre class="mt-2 p-2 bg-gray-50 rounded text-[10px] overflow-x-auto"><?= htmlspecialchars(json_encode(json_decode($log['details']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                </details>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <?php if ($log['ip_address']): ?>
                                <p class="text-gray-900 font-mono"><?= htmlspecialchars($log['ip_address']) ?></p>
                                <?php endif; ?>
                                <?php if ($log['user_agent']): ?>
                                <p class="text-gray-500 text-[10px] truncate max-w-[120px]" title="<?= htmlspecialchars($log['user_agent']) ?>">
                                    <?= htmlspecialchars(substr($log['user_agent'], 0, 30)) ?>...
                                </p>
                                <?php endif; ?>
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
                    $maxPages = 10;
                    $startPage = max(1, $page - floor($maxPages / 2));
                    $endPage = min($totalPages, $startPage + $maxPages - 1);
                    
                    if ($page > 1):
                        $queryParams['page'] = $page - 1;
                    ?>
                    <a href="?<?= http_build_query($queryParams) ?>" 
                       class="px-3 py-1 rounded bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++):
                        $queryParams['page'] = $i;
                    ?>
                    <a href="?<?= http_build_query($queryParams) ?>" 
                       class="px-3 py-1 rounded <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?> text-sm">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages):
                        $queryParams['page'] = $page + 1;
                    ?>
                    <a href="?<?= http_build_query($queryParams) ?>" 
                       class="px-3 py-1 rounded bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Info Box -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
            <h3 class="font-semibold text-blue-900 mb-2 flex items-center">
                <i class="fas fa-info-circle mr-2"></i>
                เกี่ยวกับ Audit Log
            </h3>
            <div class="text-sm text-blue-800 space-y-1">
                <p><strong>Audit Log</strong> บันทึกทุกการกระทำในระบบ เพื่อตรวจสอบและติดตามได้</p>
                <p><strong>Actions:</strong> login, logout, create, edit, delete, import, export, assign_role</p>
                <p><strong>Resources:</strong> marker, layer, category, user, role, system</p>
                <p><strong>Details:</strong> เก็บข้อมูล JSON ของการเปลี่ยนแปลง (old → new values)</p>
            </div>
        </div>

    </main>

    <?php include __DIR__ . "/../template-layout/footer.php"; ?>

</div>

<?php include __DIR__ . "/../template-layout/scripts.php"; ?>

</body>
</html>
