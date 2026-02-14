<?php
/**
 * Authentication & Session Management - Rangsit CDP
 */

require_once __DIR__ . '/database.php';

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Attempt login with username/email and password
 *
 * @return array ['success' => bool, 'message' => string, 'user' => array|null]
 */
function attemptLogin(string $identity, string $password): array
{
    $db = getDB();

    $stmt = $db->prepare("
        SELECT u.*,
               GROUP_CONCAT(r.role_slug ORDER BY r.priority DESC) AS role_slugs,
               MAX(r.priority) AS max_priority,
               (SELECT r2.role_name FROM gis_user_roles ur2
                JOIN gis_roles r2 ON r2.id = ur2.role_id
                WHERE ur2.user_id = u.id
                ORDER BY r2.priority DESC LIMIT 1) AS primary_role_name
        FROM gis_users u
        LEFT JOIN gis_user_roles ur ON ur.user_id = u.id
        LEFT JOIN gis_roles r ON r.id = ur.role_id
        WHERE (u.username = :identity OR u.email = :identity2)
        GROUP BY u.id
    ");
    $stmt->execute(['identity' => $identity, 'identity2' => $identity]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'ไม่พบผู้ใช้งานนี้ในระบบ'];
    }

    if (!$user['is_active']) {
        return ['success' => false, 'message' => 'บัญชีนี้ถูกระงับการใช้งาน'];
    }

    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'รหัสผ่านไม่ถูกต้อง'];
    }

    // Update login info
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $db->prepare("
        UPDATE gis_users
        SET last_login_at = NOW(),
            last_login_ip = :ip,
            login_count = login_count + 1
        WHERE id = :id
    ");
    $stmt->execute(['ip' => $ip, 'id' => $user['id']]);

    // Set session
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['full_name']  = $user['full_name'];
    $_SESSION['email']      = $user['email'];
    $_SESSION['avatar']     = $user['avatar'];
    $_SESSION['department'] = $user['department'];
    $_SESSION['position']   = $user['position'];
    $_SESSION['role_slugs'] = $user['role_slugs'] ? explode(',', $user['role_slugs']) : [];
    $_SESSION['role_name']  = $user['primary_role_name'] ?? 'User';
    $_SESSION['logged_in']  = true;
    $_SESSION['login_time'] = time();

    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);

    // Audit log
    auditLog($user['id'], 'login', 'user', $user['id'], $user['full_name']);

    return ['success' => true, 'message' => 'เข้าสู่ระบบสำเร็จ', 'user' => $user];
}

/**
 * Logout current user
 */
function logout(): void
{
    if (isLoggedIn()) {
        auditLog($_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id'], $_SESSION['full_name']);
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
}

/**
 * Require login - redirect to login page if not authenticated
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        $base = defined('BASE_URL') ? BASE_URL : '/cdp';
        header('Location: ' . $base . '/login.php');
        exit;
    }
}

/**
 * Check if current user has a specific role
 */
function hasRole(string $roleSlug): bool
{
    if (!isLoggedIn()) return false;
    return in_array($roleSlug, $_SESSION['role_slugs'] ?? [], true);
}

/**
 * Check if current user is admin or higher
 */
function isAdmin(): bool
{
    return hasRole('super_admin') || hasRole('admin');
}

/**
 * Get current user info from session
 */
function currentUser(): ?array
{
    if (!isLoggedIn()) return null;
    return [
        'id'         => $_SESSION['user_id'],
        'username'   => $_SESSION['username'],
        'full_name'  => $_SESSION['full_name'],
        'email'      => $_SESSION['email'],
        'avatar'     => $_SESSION['avatar'] ?? null,
        'department' => $_SESSION['department'] ?? null,
        'position'   => $_SESSION['position'] ?? null,
        'role_slugs' => $_SESSION['role_slugs'] ?? [],
        'role_name'  => $_SESSION['role_name'] ?? 'User',
    ];
}

/**
 * Write to audit log
 */
function auditLog(
    ?int $userId,
    string $action,
    ?string $resourceType = null,
    ?int $resourceId = null,
    ?string $resourceTitle = null,
    ?array $details = null
): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO gis_audit_log
                (user_id, action, resource_type, resource_id, resource_title, details, ip_address, user_agent)
            VALUES
                (:uid, :action, :rtype, :rid, :rtitle, :details, :ip, :ua)
        ");
        $stmt->execute([
            'uid'     => $userId,
            'action'  => $action,
            'rtype'   => $resourceType,
            'rid'     => $resourceId,
            'rtitle'  => $resourceTitle,
            'details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            'ip'      => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'ua'      => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    } catch (\Exception $e) {
        // Silently fail - audit should not break the app
    }
}
