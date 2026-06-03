<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$staleHeaderNeedles = [
    '<div class="interface-shell-brand__title">Smart Responsor</div>',
    '<button class="interface-shell-search__button" type="submit" aria-label="Search" title="Search">Search</button>',
];
$dirs = ['template', 'templates', 'src', 'config', 'public', 'var/cache'];
$found = [];
foreach ($dirs as $dir) {
    $path = $root . DIRECTORY_SEPARATOR . $dir;
    if (!is_dir($path)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (!$file->isFile() || $file->getSize() > 2_000_000) {
            continue;
        }
        $name = $file->getPathname();
        $text = @file_get_contents($name);
        if (!is_string($text)) {
            continue;
        }
        foreach ($staleHeaderNeedles as $needle) {
            if (str_contains($text, $needle)) {
                $found[] = [$dir, str_replace($root . DIRECTORY_SEPARATOR, '', $name), $needle];
            }
        }
    }
}

foreach ($found as [$dir, $file, $needle]) {
    echo sprintf("[top-panel-runtime-diagnostic] STALE HEADER MARKER in %s: %s :: %s\n", $dir, $file, $needle);
}

$css = $root . '/public/interfacing/design/provider-baseline.css';
$logo = $root . '/public/mandala.svg';
if (!is_file($css)) {
    echo "[top-panel-runtime-diagnostic] MISSING CSS: public/interfacing/design/provider-baseline.css\n";
    exit(1);
}
if (!is_file($logo)) {
    echo "[top-panel-runtime-diagnostic] MISSING LOGO: public/mandala.svg\n";
    exit(1);
}
$cssText = file_get_contents($css) ?: '';
foreach (["url('/mandala.svg')", 'text-indent: -9999px', 'provider-baseline-20260527h'] as $cssNeedle) {
    if ($cssNeedle !== 'provider-baseline-20260527h' && !str_contains($cssText, $cssNeedle)) {
        echo "[top-panel-runtime-diagnostic] MISSING CSS RULE: {$cssNeedle}\n";
        exit(1);
    }
}

if ($found === []) {
    echo "[top-panel-runtime-diagnostic] PASS: no stale top-panel markers found; CSS and logo assets exist.\n";
    exit(0);
}

echo "[top-panel-runtime-diagnostic] WARN: stale header markers found above. If only var/cache contains them, clear Symfony cache and OPcache/browser cache.\n";
exit(2);
