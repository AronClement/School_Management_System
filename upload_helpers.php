<?php
function ensure_upload_directory(): void
{
    $path = __DIR__ . '/uploads';
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

function sanitize_filename(string $filename): string
{
    $filename = preg_replace('/[^\w\-. ]+/', '', $filename);
    $filename = trim($filename);
    return $filename !== '' ? $filename : 'upload';
}

function save_upload(array $file, string $prefix): ?array
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    ensure_upload_directory();
    $originalName = sanitize_filename(basename($file['name']));
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $safeName = pathinfo($originalName, PATHINFO_FILENAME);
    $timestamp = date('YmdHis');
    $savedName = sprintf('%s_%s_%s.%s', $prefix, $timestamp, bin2hex(random_bytes(4)), $extension);
    $destination = __DIR__ . '/uploads/' . $savedName;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return null;
    }
    return [
        'original_name' => $originalName,
        'saved_name' => $savedName,
        'mime_type' => $file['type'] ?? 'application/octet-stream',
        'size' => $file['size'] ?? 0,
        'uploaded_at' => date('Y-m-d H:i:s'),
    ];
}

function attachment_url(string $savedName): string
{
    return 'uploads/' . rawurlencode($savedName);
}
