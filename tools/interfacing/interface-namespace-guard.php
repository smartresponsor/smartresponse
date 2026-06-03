<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$paths = [
    'template',
    'templates',
    'public/interfacing/design',
    'tools/interfacing',
];

$failures = [];
foreach ($paths as $relativeRoot) {
    $dir = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeRoot);
    if (!is_dir($dir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }
        $extension = strtolower($file->getExtension());
        if (!in_array($extension, ['twig', 'css', 'js', 'php'], true)) {
            continue;
        }

        $content = (string) file_get_contents($file->getPathname());
        if (str_contains($file->getFilename(), 'interface-namespace-guard.php')) {
            continue;
        }

        if ($file->getFilename() === 'provider-baseline.css' && str_contains($content, 'LEGACY SR RUNTIME COMPATIBILITY')) {
            continue;
        }
        $legacyClassMarker = 'class="' . 'sr-';
        $legacyDataMarker = 'data-' . 'sr-';
        $legacyVariableMarker = '--' . 'sr-provider-';
        if (str_contains($content, $legacyClassMarker) || str_contains($content, $legacyDataMarker) || str_contains($content, $legacyVariableMarker)) {
            $failures[] = str_replace($root . DIRECTORY_SEPARATOR, '', $file->getPathname());
        }
    }
}

$css = (string) file_get_contents($root . '/public/interfacing/design/provider-baseline.css');
foreach (['.interface-shell', '.interface-shell-top__inner', '.interface-top-cell--primary', '.interface-top-cell--secondary', '.interface-top-cell--main', '.interface-top-cell--right', 'LEGACY SR RUNTIME COMPATIBILITY', '.sr-shell', '.sr-shell-top__inner'] as $required) {
    if (!str_contains($css, $required)) {
        $failures[] = 'Missing interface namespace/top-grid/compatibility marker: ' . $required;
    }
}

if ($failures !== []) {
    fwrite(STDERR, "[interface-namespace-guard] FAIL\n" . implode("\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "[interface-namespace-guard] PASS\n");
