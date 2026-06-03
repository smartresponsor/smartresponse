<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$class = 'BridgeProviderSurfaceController';
$pattern = '/(?:final\s+readonly\s+class|final\s+class|class)\s+'.preg_quote($class, '/').'\b/';
$allowed = str_replace('\\', '/', $root.'/src/Presentation/Controller/Interfacing/BridgeProviderSurfaceController.php');
$matches = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile()) {
        continue;
    }
    $path = str_replace('\\', '/', $file->getPathname());
    if (str_contains($path, '/vendor/') || str_contains($path, '/node_modules/') || str_contains($path, '/var/cache/')) {
        continue;
    }
    if ('php' !== strtolower($file->getExtension())) {
        continue;
    }
    $content = file_get_contents($path);
    if (false === $content) {
        continue;
    }
    if (preg_match_all($pattern, $content, $found, PREG_OFFSET_CAPTURE)) {
        foreach ($found[0] as $hit) {
            $line = substr_count(substr($content, 0, $hit[1]), "\n") + 1;
            $matches[] = [$path, $line];
        }
    }
}

if (1 !== count($matches) || $matches[0][0] !== $allowed) {
    fwrite(STDERR, "[no-duplicate-controller-class-guard] FAIL\n");
    foreach ($matches as [$path, $line]) {
        fwrite(STDERR, sprintf(" - %s:%d\n", $path, $line));
    }
    exit(1);
}

fwrite(STDOUT, "[no-duplicate-controller-class-guard] PASS\n");
