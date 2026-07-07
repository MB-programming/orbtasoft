<?php
/**
 * Minimal image upload handler — no Composer dependency, validates by
 * actual image content (getimagesize), not just the file extension.
 */

function handle_image_upload(string $fieldName, string $fallback = ''): string
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $fallback;
    }

    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] <= 0 || $file['size'] > 5 * 1024 * 1024) {
        return $fallback;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    // SVGs aren't real "images" to getimagesize(); trust finfo's mime sniff for them.
    if ($mime !== 'image/svg+xml' && @getimagesize($file['tmp_name']) === false) {
        return $fallback;
    }

    if (!isset($allowed[$mime])) {
        return $fallback;
    }

    $uploadDir = __DIR__ . '/../assets/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(10)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
        return $fallback;
    }

    return '/assets/uploads/' . $filename;
}
