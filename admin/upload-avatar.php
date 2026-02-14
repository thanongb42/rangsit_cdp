<?php
/**
 * Avatar Upload API (AJAX) - Rangsit CDP
 * POST multipart/form-data with field "avatar"
 * Returns JSON: { success, message, avatar_url }
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? 'upload';

// Handle remove avatar
if ($action === 'remove') {
    $db = getDB();
    $stmt = $db->prepare("SELECT avatar FROM gis_users WHERE id = ?");
    $stmt->execute([$userId]);
    $oldAvatar = $stmt->fetchColumn();

    // Delete old file
    if ($oldAvatar) {
        $oldFile = __DIR__ . '/../' . ltrim($oldAvatar, '/');
        if (file_exists($oldFile)) {
            unlink($oldFile);
        }
    }

    $stmt = $db->prepare("UPDATE gis_users SET avatar = NULL WHERE id = ?");
    $stmt->execute([$userId]);
    $_SESSION['avatar'] = null;

    auditLog($userId, 'edit', 'user', $userId, $_SESSION['full_name'], ['field' => 'avatar', 'action' => 'remove']);

    echo json_encode(['success' => true, 'message' => 'ลบรูปโปรไฟล์เรียบร้อย', 'avatar_url' => ''], JSON_UNESCAPED_UNICODE);
    exit;
}

// Upload avatar
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'ไฟล์มีขนาดใหญ่เกินไป',
        UPLOAD_ERR_FORM_SIZE  => 'ไฟล์มีขนาดใหญ่เกินไป',
        UPLOAD_ERR_PARTIAL    => 'อัปโหลดไฟล์ไม่สมบูรณ์',
        UPLOAD_ERR_NO_FILE    => 'ไม่พบไฟล์ที่อัปโหลด',
    ];
    $errCode = $_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE;
    echo json_encode(['success' => false, 'message' => $errorMessages[$errCode] ?? 'เกิดข้อผิดพลาดในการอัปโหลด'], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['avatar'];
$maxSize = 2 * 1024 * 1024; // 2MB
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// Validate file size
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 2MB)'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
if (!in_array($mimeType, $allowedTypes, true)) {
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะไฟล์ JPG, PNG, GIF, WEBP'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Generate unique filename
$ext = match ($mimeType) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
    default      => 'jpg',
};
$filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
$uploadDir = __DIR__ . '/../public/uploads/avatars/';
$uploadPath = $uploadDir . $filename;
$dbPath = 'public/uploads/avatars/' . $filename;

// Ensure directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Delete old avatar file
$db = getDB();
$stmt = $db->prepare("SELECT avatar FROM gis_users WHERE id = ?");
$stmt->execute([$userId]);
$oldAvatar = $stmt->fetchColumn();

if ($oldAvatar) {
    $oldFile = __DIR__ . '/../' . ltrim($oldAvatar, '/');
    if (file_exists($oldFile)) {
        unlink($oldFile);
    }
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกไฟล์ได้'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Update database
$stmt = $db->prepare("UPDATE gis_users SET avatar = ? WHERE id = ?");
$stmt->execute([$dbPath, $userId]);

// Update session
$_SESSION['avatar'] = $dbPath;

auditLog($userId, 'edit', 'user', $userId, $_SESSION['full_name'], ['field' => 'avatar', 'file' => $filename]);

$avatarUrl = BASE_URL . '/' . $dbPath;
echo json_encode([
    'success'    => true,
    'message'    => 'อัปโหลดรูปโปรไฟล์เรียบร้อย',
    'avatar_url' => $avatarUrl,
], JSON_UNESCAPED_UNICODE);
