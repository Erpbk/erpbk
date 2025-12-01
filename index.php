<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * This file redirects all requests to the public folder
 * Only use this if your hosting doesn't allow setting document root to /public
 */

// Get the URI path
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Remove leading slash
$uri = ltrim($uri, '/');

// Check if the requested file exists in public directory
$publicPath = __DIR__ . '/public/' . $uri;

// If it's a file in public directory (CSS, JS, images, etc.), serve it directly
if ($uri !== '' && file_exists($publicPath) && !is_dir($publicPath)) {
    // Get the file extension
    $extension = pathinfo($publicPath, PATHINFO_EXTENSION);

    // Set appropriate content type
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
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
        'pdf' => 'application/pdf',
    ];

    if (isset($mimeTypes[$extension])) {
        header('Content-Type: ' . $mimeTypes[$extension]);
    }

    readfile($publicPath);
    exit;
}

// Otherwise, load Laravel's public/index.php
require_once __DIR__ . '/public/index.php';
