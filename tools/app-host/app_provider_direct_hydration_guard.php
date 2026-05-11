<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$runtime = $root . '/public/interfacing/admin-body/runtime.js';
$registry = $root . '/public/interfacing/admin-body/provider-registry.js';
$antd = $root . '/public/interfacing/admin-body/providers/antd-pro.js';
$processEnv = $root . '/public/interfacing/admin-body/process-env.js';
$canonical = $root . '/public/interfacing/admin-body/canonical-providers.js';

$checks = [
    [$runtime, 'ensurePrimaryProviderRegistration'],
    [$runtime, 'directRegisterExternalProvider'],
    [$runtime, 'provider-registry-ready'],
    [$registry, 'provider-registry-ready'],
    [$antd, 'provider-registry-ready'],
    [$processEnv, 'globalThis.process'],
    [$canonical, 'App host Wave 07 dashboard panels renderer'],
    [$canonical, 'sidePanels'],
    [$canonical, 'Dashboard sections'],
    [$canonical, 'Host panels'],
];

foreach ($checks as [$file, $needle]) {
    if (!is_file($file)) {
        fwrite(STDERR, "Missing required file: {$file}\n");
        exit(1);
    }

    $contents = (string) file_get_contents($file);
    if (!str_contains($contents, $needle)) {
        fwrite(STDERR, "Required marker {$needle} missing in {$file}\n");
        exit(1);
    }
}

echo "App host provider direct hydration guard passed.\n";
