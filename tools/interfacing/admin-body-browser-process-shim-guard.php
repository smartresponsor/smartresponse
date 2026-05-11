<?php
// Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$processShim = $root . '/public/interfacing/admin-body/process-env.js';
$mount = $root . '/template/interfacing/admin/body/mount.html.twig';
$document = $root . '/template/interfacing/admin/body/provider_document.html.twig';

foreach ([$processShim, $mount, $document] as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, sprintf("Missing required admin-body browser process shim file: %s\n", $path));
        exit(1);
    }
}

$shim = file_get_contents($processShim) ?: '';
foreach (['globalThis', 'global.process', 'NODE_ENV', 'production'] as $required) {
    if (!str_contains($shim, $required)) {
        fwrite(STDERR, sprintf("process-env.js is missing required marker: %s\n", $required));
        exit(1);
    }
}

$mountBody = file_get_contents($mount) ?: '';
$scriptSrcMatches = [];
preg_match_all('/<script\b[^>]*\bsrc="([^"]+)"[^>]*>/i', $mountBody, $scriptSrcMatches, PREG_SET_ORDER);
$scriptSources = array_map(static fn (array $match): string => $match[1], $scriptSrcMatches);
$processIndex = null;
$canonicalIndex = null;
foreach ($scriptSources as $index => $source) {
    if (str_contains($source, 'process-env.js')) {
        $processIndex = $index;
    }
    if (str_contains($source, 'canonical-providers.js')) {
        $canonicalIndex = $index;
        break;
    }
}
if ($processIndex === null) {
    fwrite(STDERR, "admin body mount must load process-env.js as an executable script.\n");
    exit(1);
}
if ($canonicalIndex === null) {
    fwrite(STDERR, "admin body mount must load canonical-providers.js as an executable script.\n");
    exit(1);
}
if ($processIndex > $canonicalIndex) {
    fwrite(STDERR, "process-env.js must be loaded before canonical-providers.js in admin body mount.\n");
    exit(1);
}
if (!str_contains($mountBody, 'w47-browser-process-shim')) {
    fwrite(STDERR, "admin body mount must use the w47 browser process shim cache-buster.\n");
    exit(1);
}
if (str_contains($mountBody, 'w45-provider-browser-mount')) {
    fwrite(STDERR, "admin body mount must not reference stale w45 provider browser mount assets.\n");
    exit(1);
}

$documentBody = file_get_contents($document) ?: '';
if (!str_contains($documentBody, 'w47-browser-process-shim')) {
    fwrite(STDERR, "provider document stylesheet must use the w47 browser process shim cache-buster.\n");
    exit(1);
}
if (str_contains($documentBody, 'w45-provider-browser-mount')) {
    fwrite(STDERR, "provider document must not reference stale w45 provider browser mount assets.\n");
    exit(1);
}

echo "App host Interfacing admin body browser process shim guard passed.\n";
