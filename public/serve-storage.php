<?php

/**
 * Serve public-disk uploads without booting Laravel (Hostinger / shared hosting).
 * Avoids session middleware 500 errors when public/storage symlink is missing.
 */

declare(strict_types=1);

$relative = $_GET['path'] ?? '';
if ($relative === '' && isset($_SERVER['PATH_INFO'])) {
    $relative = ltrim((string) $_SERVER['PATH_INFO'], '/');
}

$relative = ltrim(str_replace('\\', '/', urldecode($relative)), '/');

if ($relative === '' || str_contains($relative, '..')) {
    http_response_code(404);
    exit;
}

$base = realpath(__DIR__ . '/../storage/app/public');
if ($base === false) {
    http_response_code(500);
    exit;
}

$file = realpath($base . '/' . $relative);
$basePrefix = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

if ($file === false || !str_starts_with($file, $basePrefix) || !is_file($file)) {
    http_response_code(404);
    exit;
}

$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mimeTypes = [
    'css' => 'text/css',
    'js' => 'application/javascript',
    'json' => 'application/json',
    'xml' => 'application/xml',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'svg' => 'image/svg+xml',
    'webp' => 'image/webp',
    'ico' => 'image/x-icon',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
    'eot' => 'application/vnd.ms-fontobject',
    'pdf' => 'application/pdf',
];

$mime = $mimeTypes[$extension] ?? (function_exists('mime_content_type') ? mime_content_type($file) : null);
header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
header('Content-Length: ' . (string) filesize($file));
header('Cache-Control: public, max-age=31536000');
readfile($file);
