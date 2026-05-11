<?php

/*
 * Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
 */

$root = dirname(__DIR__, 2);

$files = [
    'config/routes/app_host_dashboard.yaml',
    'src/Controller/AppDashboardController.php',
    'src/Service/Dashboard/AppDashboardSurfaceBuilder.php',
    'public/interfacing/admin-body/process-env.js',
    'public/interfacing/admin-body/provider-registry.js',
    'public/interfacing/admin-body/canonical-providers.js',
    'public/interfacing/admin-body/providers/antd-pro.js',
    'public/interfacing/admin-body/providers/primereact.js',
    'public/interfacing/admin-body/runtime.js',
];

foreach ($files as $file) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
    if (!is_file($path)) {
        fwrite(STDERR, "Missing provider rendering set file: {$file}\n");
        exit(1);
    }
}

$route = file_get_contents($root . '/config/routes/app_host_dashboard.yaml');
if (!str_contains($route, 'path: /') || !str_contains($route, 'App\\Controller\\AppDashboardController::__invoke')) {
    fwrite(STDERR, "App host dashboard route must own / and point at AppDashboardController.\n");
    exit(1);
}

$controller = file_get_contents($root . '/src/Controller/AppDashboardController.php');
foreach ([
    'AppDashboardSurfaceResponderInterface',
    'respond(',
] as $needle) {
    if (!str_contains($controller, $needle)) {
        fwrite(STDERR, "App dashboard controller is missing bridge/provider marker: {$needle}\n");
        exit(1);
    }
}

$builder = file_get_contents($root . '/src/Service/Dashboard/AppDashboardSurfaceBuilder.php');
foreach ([
    "'surface' => 'dashboard'",
    "'operation' => 'overview'",
    "'layout' => 'provider-dashboard'",
    "'shellMode' => 'provider-page'",
    "'key' => 'provider-hardening'",
    "'key' => 'route-set'",
    "'key' => 'asset-publication'",
    "'key' => 'legacy-shell-block'",
    "'sidePanels' => [",
] as $needle) {
    if (!str_contains($builder, $needle)) {
        fwrite(STDERR, "App dashboard contract is missing provider hardening marker: {$needle}\n");
        exit(1);
    }
}

foreach (['crud-app-shell', 'data-cruding-shell-contract', '@Cruding/crud/index.html.twig', '<style>'] as $forbidden) {
    if (str_contains($builder, $forbidden) || str_contains($controller, $forbidden)) {
        fwrite(STDERR, "App dashboard primary path contains forbidden legacy UI marker: {$forbidden}\n");
        exit(1);
    }
}

$shim = file_get_contents($root . '/public/interfacing/admin-body/process-env.js');
foreach (['globalScope.process', 'globalThis.process', 'NODE_ENV'] as $needle) {
    if (!str_contains($shim, $needle)) {
        fwrite(STDERR, "Browser process shim is missing marker: {$needle}\n");
        exit(1);
    }
}

$bundle = file_get_contents($root . '/public/interfacing/admin-body/canonical-providers.js');
foreach (['Dashboard sections', 'Host panels', 'e.surface === "dashboard" || t === "overview"'] as $needle) {
    if (!str_contains($bundle, $needle)) {
        fwrite(STDERR, "Canonical provider bundle is missing dashboard renderer marker: {$needle}\n");
        exit(1);
    }
}

fwrite(STDOUT, "App host provider rendering set guard passed.\n");
