<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$publicRoot = $root . '/public/interfacing/admin-body';
$files = [
    $publicRoot . '/canonical-providers.js',
    $publicRoot . '/runtime.js',
    $publicRoot . '/provider-registry.js',
    $publicRoot . '/process-env.js',
    $publicRoot . '/providers/antd-pro.js',
    $publicRoot . '/providers/primereact.js',
    $publicRoot . '/canonical-providers.interfacing-interface-ui.css',
];
foreach ($files as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "App host is missing published Interfacing provider asset: {$file}\n");
        exit(1);
    }
}

$bundleContent = file_get_contents($publicRoot . '/canonical-providers.js') ?: '';
$processEnvContent = file_get_contents($publicRoot . '/process-env.js') ?: '';

foreach (['App host Wave 07 dashboard panels renderer.', 'e.surface === "dashboard" || t === "overview"', 'sidePanels', 'dashboard', 'metrics', 'widgets', 'InterfacingAntDesignProAdminBodyProvider'] as $marker) {
    if (!str_contains($bundleContent, $marker)) {
        fwrite(STDERR, "App host canonical-providers.js is missing dashboard renderer marker: {$marker}\n");
        exit(1);
    }
}

foreach (['globalThis', 'root.process', 'process.env', 'NODE_ENV', '__INTERFACING_PROCESS_ENV_READY__'] as $marker) {
    if (!str_contains($processEnvContent, $marker)) {
        fwrite(STDERR, "App host process-env.js is missing marker: {$marker}\n");
        exit(1);
    }
}

fwrite(STDOUT, "App host Interfacing dashboard provider assets guard passed.\n");
