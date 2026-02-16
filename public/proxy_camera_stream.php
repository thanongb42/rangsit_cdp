<?php
/**
 * Camera Stream Proxy - Rangsit CDP
 * Proxies MJPEG/snapshot streams from CCTV cameras via cURL.
 * Reads stream_url from gis_markers.properties JSON.
 *
 * Usage: proxy_camera_stream.php?id={marker_id}
 */

require_once __DIR__ . '/../config/database.php';

$markerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

function output_error_image($http_code, $message, $details = '') {
    http_response_code($http_code);
    header('Content-Type: image/jpeg');
    $im = imagecreatetruecolor(640, 480);
    $bg = imagecolorallocate($im, 20, 20, 20);
    $fg = imagecolorallocate($im, 255, 80, 80);
    $detail_fg = imagecolorallocate($im, 200, 200, 200);
    imagefill($im, 0, 0, $bg);
    imagestring($im, 5, 20, 220, $message, $fg);
    if ($details) {
        imagestring($im, 3, 20, 245, $details, $detail_fg);
    }
    imagejpeg($im);
    imagedestroy($im);
    exit;
}

if ($markerId <= 0) {
    output_error_image(400, 'Invalid Request', 'Marker ID is missing.');
}

try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT m.properties
        FROM gis_markers m
        WHERE m.id = :id AND m.status = 'active'
    ");
    $stmt->execute(['id' => $markerId]);
    $row = $stmt->fetch();
} catch (Exception $e) {
    output_error_image(500, 'Database Error', 'Could not query marker.');
}

if (!$row) {
    output_error_image(404, 'Camera Not Found', 'Marker ID: ' . $markerId . ' not found or inactive.');
}

$props = json_decode($row['properties'], true);
$streamUrl = $props['stream_url'] ?? '';

if (empty($streamUrl) || filter_var($streamUrl, FILTER_VALIDATE_URL) === false) {
    output_error_image(500, 'Invalid Stream URL', 'No valid stream_url in marker properties.');
}

// Parse URL for auth credentials
$url_parts = parse_url($streamUrl);
$clean_url = $streamUrl;
$user_pwd = null;

if (isset($url_parts['user']) && isset($url_parts['pass'])) {
    $user = $url_parts['user'];
    $pass = $url_parts['pass'];
    $clean_url = $url_parts['scheme'] . '://' . $url_parts['host'];
    if (isset($url_parts['port'])) $clean_url .= ':' . $url_parts['port'];
    if (isset($url_parts['path'])) $clean_url .= $url_parts['path'];
    if (isset($url_parts['query'])) $clean_url .= '?' . $url_parts['query'];
    $user_pwd = "$user:$pass";
}

// Prepare PHP for streaming
@set_time_limit(0);
@ini_set('max_execution_time', '0');
@ini_set('memory_limit', '128M');
while (ob_get_level()) {
    ob_end_clean();
}
@ini_set('output_buffering', 'off');
@ini_set('implicit_flush', '1');

// cURL stream proxy
$ch = curl_init();

curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header_line) {
    if (stripos($header_line, 'Transfer-Encoding') === 0 || stripos($header_line, 'Content-Length') === 0 || stripos($header_line, 'Connection') === 0) {
        return strlen($header_line);
    }
    if (trim($header_line) !== '') {
        header($header_line, false);
    }
    return strlen($header_line);
});

curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) {
    if (connection_aborted()) {
        return 0;
    }
    echo $data;
    flush();
    return strlen($data);
});

curl_setopt($ch, CURLOPT_URL, $clean_url);
if ($user_pwd) {
    curl_setopt($ch, CURLOPT_USERPWD, $user_pwd);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
}
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_FAILONERROR, false);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

curl_exec($ch);

$curl_errno = curl_errno($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curl_errno !== 0) {
    output_error_image($http_code ?: 502, "Connection Error: " . $curl_errno, curl_strerror($curl_errno));
}

exit;
