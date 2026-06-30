<?php

declare(strict_types=1);

/**
 * Serves files from storage/app/public when php artisan storage:link is unavailable.
 * Used on InfinityFree and similar hosts that disable symlinks and external rewrites.
 */

$path = $_GET['path'] ?? '';
$path = str_replace(['\\', "\0"], '/', (string) $path);
$path = ltrim($path, '/');

if ($path === '' || str_contains($path, '..')) {
    http_response_code(404);
    exit;
}

$base = realpath(dirname(__DIR__, 2).'/storage/app/public');
if ($base === false) {
    http_response_code(404);
    exit;
}

$candidate = $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
$file = realpath($candidate);

if ($file === false || ! is_file($file)) {
    http_response_code(404);
    exit;
}

$basePrefix = $base.DIRECTORY_SEPARATOR;
if ($file !== $base && ! str_starts_with($file, $basePrefix)) {
    http_response_code(404);
    exit;
}

$mime = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
    'jpg', 'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    default => mime_content_type($file) ?: 'application/octet-stream',
};

header('Content-Type: '.$mime);
header('Content-Length: '.(string) filesize($file));
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');

readfile($file);
