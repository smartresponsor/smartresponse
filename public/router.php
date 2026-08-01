<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__.$path;

$interfacingPrefix = '/bundles/interfacing/';
if (str_starts_with($path, $interfacingPrefix)) {
    $relativePath = substr($path, strlen($interfacingPrefix));
    $interfacingPublic = dirname(__DIR__, 2).'/Interfacing/public/';
    $sourceFile = $interfacingPublic.'bundles/interfacing/'.$relativePath;

    if (!is_file($sourceFile)) {
        $sourceFile = $interfacingPublic.$relativePath;
    }

    if (is_file($sourceFile)) {
        $extension = strtolower(pathinfo($sourceFile, PATHINFO_EXTENSION));
        $contentTypes = [
            'css' => 'text/css; charset=UTF-8',
            'js' => 'text/javascript; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
        ];

        header('Content-Type: '.($contentTypes[$extension] ?? 'application/octet-stream'));
        header('Content-Length: '.(string) filesize($sourceFile));

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
            readfile($sourceFile);
        }

        return true;
    }
}

if (is_file($file)) {
    return false;
}

require __DIR__.'/index.php';
return true;
