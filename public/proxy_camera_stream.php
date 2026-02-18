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

function output_error_image($message, $details = '') {
    // Always return HTTP 200 so <img> tag displays the error image instead of triggering onerror
    http_response_code(200);
    header('Content-Type: image/jpeg');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $w = 640;
    $h = 480;
    $im = imagecreatetruecolor($w, $h);

    // Dark background
    $bg = imagecolorallocate($im, 30, 30, 40);
    imagefill($im, 0, 0, $bg);

    // Colors
    $red    = imagecolorallocate($im, 239, 68, 68);
    $white  = imagecolorallocate($im, 220, 220, 230);
    $gray   = imagecolorallocate($im, 120, 120, 140);
    $yellow = imagecolorallocate($im, 250, 204, 21);

    // Camera icon area (simple rectangle)
    $iconColor = imagecolorallocate($im, 50, 50, 65);
    imagefilledrectangle($im, 270, 140, 370, 200, $iconColor);
    imagefilledrectangle($im, 370, 155, 395, 185, $iconColor);

    // Error message
    imagestring($im, 5, ($w - strlen($message) * 9) / 2, 220, $message, $red);

    // Details
    if ($details) {
        imagestring($im, 3, ($w - strlen($details) * 7) / 2, 248, $details, $gray);
    }

    // Suggestion line
    $hint = 'Server cannot reach camera. Check firewall/network.';
    imagestring($im, 3, ($w - strlen($hint) * 7) / 2, 280, $hint, $yellow);

    // Timestamp
    $ts = date('Y-m-d H:i:s');
    imagestring($im, 2, ($w - strlen($ts) * 6) / 2, 310, $ts, $gray);

    imagejpeg($im, null, 85);
    imagedestroy($im);
    exit;
}

if ($markerId <= 0) {
    output_error_image('Invalid Request', 'Marker ID is missing.');
}

try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT m.properties, m.title
        FROM gis_markers m
        WHERE m.id = :id AND m.status = 'active'
    ");
    $stmt->execute(['id' => $markerId]);
    $row = $stmt->fetch();
} catch (Exception $e) {
    output_error_image('Database Error', 'Could not query marker.');
}

if (!$row) {
    output_error_image('Camera Not Found', 'Marker ID: ' . $markerId . ' not found or inactive.');
}

$props = json_decode($row['properties'], true);
$streamUrl = $props['stream_url'] ?? '';

if (empty($streamUrl) || filter_var($streamUrl, FILTER_VALIDATE_URL) === false) {
    output_error_image('No Stream URL', 'Camera has no valid stream_url configured.');
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

// First, do a quick connectivity check (snapshot mode) with short timeout
$test_ch = curl_init();
curl_setopt($test_ch, CURLOPT_URL, $clean_url);
if ($user_pwd) {
    curl_setopt($test_ch, CURLOPT_USERPWD, $user_pwd);
    curl_setopt($test_ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
}
curl_setopt($test_ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($test_ch, CURLOPT_NOBODY, true); // HEAD request only
curl_setopt($test_ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($test_ch, CURLOPT_TIMEOUT, 8);
curl_setopt($test_ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($test_ch);
$test_errno = curl_errno($test_ch);
$test_http = curl_getinfo($test_ch, CURLINFO_HTTP_CODE);
curl_close($test_ch);

if ($test_errno === 7 || $test_errno === 28) {
    // Connection refused or timeout — camera unreachable from this server
    $host = $url_parts['host'] ?? 'unknown';
    $port = $url_parts['port'] ?? '80';
    output_error_image(
        'Cannot Connect to Camera',
        "Host $host:$port unreachable (err:$test_errno)"
    );
}

if ($test_http === 401) {
    output_error_image('Authentication Failed', 'Camera rejected credentials (HTTP 401).');
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
    output_error_image("Connection Error: " . $curl_errno, curl_strerror($curl_errno));
}

exit;
