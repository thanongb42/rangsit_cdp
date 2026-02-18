<?php
/**
 * CCTV Stream Proxy
 * Safe proxy to stream camera feed
 * Prevents credential exposure in client-side code
 */

require_once __DIR__ . '/../config/database.php';
$db = getDB();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');

// Get camera ID from request
$cameraId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$cameraId) {
    http_response_code(400);
    exit('Invalid camera ID');
}

// Get camera from markers
$camera = $db->prepare("
    SELECT m.*, 
           (SELECT JSON_EXTRACT(m.properties, '$.stream_url')) as stream_url
    FROM gis_markers m
    WHERE m.id = ? AND m.status = 'active'
")->execute([$cameraId]);

$camera = $db->query("
    SELECT m.*, 
           JSON_EXTRACT(m.properties, '$.stream_url') as stream_url
    FROM gis_markers m
    WHERE m.id = {$cameraId} AND m.status = 'active'
")->fetch();

if (!$camera || empty($camera['stream_url'])) {
    http_response_code(404);
    exit('Camera not found or stream URL not available');
}

// Extract stream URL from JSON
$streamUrl = trim($camera['stream_url'], '"');

// If it's an HTTP stream, proxy it
if (strpos($streamUrl, 'http') === 0) {
    // Log access
    error_log("Camera stream access: Camera ID {$cameraId}");
    
    // Set appropriate headers based on stream type
    if (strpos($streamUrl, '.m3u8') !== false) {
        // HLS stream
        header('Content-Type: application/vnd.apple.mpegurl');
    } elseif (strpos($streamUrl, '.mpd') !== false) {
        // DASH stream
        header('Content-Type: application/dash+xml');
    } elseif (strpos($streamUrl, 'mjpeg') !== false || strpos($streamUrl, 'motion') !== false) {
        // Motion JPEG
        header('Content-Type: multipart/x-mixed-replace; boundary=frame');
    } else {
        // Default to stream
        header('Content-Type: application/octet-stream');
    }
    
    // Proxy the stream
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 30,
            'header' => "User-Agent: Rangsit-CDP-Stream-Proxy/1.0\r\n"
        ]
    ]);
    
    $stream = fopen($streamUrl, 'r', false, $ctx);
    
    if ($stream) {
        // Stream in chunks
        while (!feof($stream)) {
            $chunk = fread($stream, 8192);
            if ($chunk === false) break;
            echo $chunk;
            flush();
            
            // Check if client closed connection
            if (connection_aborted()) {
                break;
            }
        }
        fclose($stream);
    } else {
        http_response_code(503);
        exit('Unable to access camera stream');
    }
} else {
    // For non-HTTP streams, return error
    http_response_code(400);
    exit('Invalid stream URL protocol');
}
?>
