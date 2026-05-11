<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$public = $root . '/public/interfacing/admin-body';

$required = [
    'process-env.js',
    'provider-registry.js',
    'canonical-providers.js',
    'providers/antd-pro.js',
    'runtime.js',
];

foreach ($required as $file) {
    $path = $public . '/' . $file;
    if (!is_file($path)) {
        fwrite(STDERR, "Missing App host Interfacing provider asset: {$file}\n");
        exit(1);
    }
}

$processEnv = file_get_contents($public . '/process-env.js') ?: '';
foreach (['globalThis', 'global.process', 'NODE_ENV'] as $marker) {
    if (!str_contains($processEnv, $marker)) {
        fwrite(STDERR, "App host process-env.js is missing marker: {$marker}\n");
        exit(1);
    }
}

$registry = file_get_contents($public . '/provider-registry.js') ?: '';
foreach (['InterfacingAdminBodyProviderRegistry', 'register', 'get'] as $marker) {
    if (!str_contains($registry, $marker)) {
        fwrite(STDERR, "App host provider-registry.js is missing marker: {$marker}\n");
        exit(1);
    }
}

$antd = file_get_contents($public . '/providers/antd-pro.js') ?: '';
foreach (['InterfacingAntDesignProAdminBodyProvider', 'canonical-providers-ready', 'registerAntDesignProvider'] as $marker) {
    if (!str_contains($antd, $marker)) {
        fwrite(STDERR, "App host antd-pro.js is missing provider registration marker: {$marker}\n");
        exit(1);
    }
}

$runtime = file_get_contents($public . '/runtime.js') ?: '';
foreach (['InterfacingAntDesignProAdminBodyProvider', 'interfacing:admin-body:canonical-providers-ready', 'hydrateMount'] as $marker) {
    if (!str_contains($runtime, $marker)) {
        fwrite(STDERR, "App host runtime.js is missing hydration sync marker: {$marker}\n");
        exit(1);
    }
}

$canonical = file_get_contents($public . '/canonical-providers.js') ?: '';
foreach (['globalThis.process', 'InterfacingAntDesignProAdminBodyProvider', 'dashboard', 'sidePanels'] as $marker) {
    if (!str_contains($canonical, $marker)) {
        fwrite(STDERR, "App host canonical-providers.js is missing dashboard renderer marker: {$marker}\n");
        exit(1);
    }
}

echo "App host provider registration sync guard passed.\n";
