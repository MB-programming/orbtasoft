<?php
require __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/downloader.php';

function download_video_fail(string $errorKey): void
{
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo t($errorKey);
    exit;
}

$platform = (string) ($_GET['platform'] ?? '');
$rawUrl = (string) ($_GET['url'] ?? '');
$quality = (string) ($_GET['quality'] ?? '');

if (!array_key_exists($platform, downloader_platforms())) {
    download_video_fail('downloader_error_platform');
}

$validated = downloader_validate_url($rawUrl, $platform);
if (!$validated['ok']) {
    download_video_fail('downloader_error_' . $validated['error']);
}

if (!downloader_quality_allowed($quality)) {
    download_video_fail('downloader_error_quality');
}

set_time_limit(0);
ignore_user_abort(false);

$result = downloader_download($validated['url'], $quality);
if (!$result['ok']) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo t('downloader_error_' . $result['error']);
    exit;
}

$path = $result['path'];
$size = filesize($path);
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mimeMap = [
    'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mkv' => 'video/x-matroska',
    'mov' => 'video/quicktime', 'mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

$asciiName = preg_replace('~[^\x20-\x7E]~', '_', basename($path));
$asciiName = str_replace(['"', '\\'], '_', $asciiName);
$utf8Name = preg_replace('~[\r\n]~', '', basename($path));

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . $size);
header('Content-Disposition: attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($utf8Name));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

$fp = fopen($path, 'rb');
if ($fp) {
    while (!feof($fp)) {
        echo fread($fp, 1048576);
        flush();
    }
    fclose($fp);
}

downloader_rrmdir($result['dir']);
