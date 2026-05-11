<?php

/*
 * Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
 */

$root = dirname(__DIR__, 2);

$requiredFiles = [
    'src/Service/Dashboard/AppDashboardSurfaceBuilder.php',
    'public/interfacing/admin-body/canonical-providers.js',
    'config/packages/twig.yaml',
];

foreach ($requiredFiles as $file) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
    if (!is_file($path)) {
        fwrite(STDERR, "Missing required App dashboard panels file: {$file}
");
        exit(1);
    }
}

$twig = file_get_contents($root . '/config/packages/twig.yaml');
if (str_contains($twig, '%kernel.project_dir%/../Interfacing/template')) {
    fwrite(STDERR, "App host must not directly consume Interfacing template paths.
");
    exit(1);
}

$builder = file_get_contents($root . '/src/Service/Dashboard/AppDashboardSurfaceBuilder.php');
foreach ([
    "'layout' => 'provider-dashboard'",
    "'shellMode' => 'provider-page'",
    "'metrics' => [",
    "'widgets' => [",
    "'sidePanels' => [",
    "'dashboard-panels'",
    "'customer-shortcuts'",
    'Bridge handoff active',
    'Provider assets',
    "'key' => 'provider-hardening'",
    "'key' => 'route-set'",
    "'key' => 'asset-publication'",
    "'key' => 'legacy-shell-block'",
] as $needle) {
    if (!str_contains($builder, $needle)) {
        fwrite(STDERR, "App dashboard builder is missing provider panel marker: {$needle}
");
        exit(1);
    }
}
foreach (['crud-app-shell', '@Cruding/crud', '<style'] as $forbidden) {
    if (str_contains($builder, $forbidden)) {
        fwrite(STDERR, "App dashboard builder contains forbidden legacy UI marker: {$forbidden}
");
        exit(1);
    }
}

$bundle = file_get_contents($root . '/public/interfacing/admin-body/canonical-providers.js');
foreach ([
    'Dashboard sections',
    'Host panels',
    'e.surface === "dashboard" || t === "overview"',
] as $needle) {
    if (!str_contains($bundle, $needle)) {
        fwrite(STDERR, "Canonical provider bundle is missing dashboard panel renderer marker: {$needle}
");
        exit(1);
    }
}

fwrite(STDOUT, "App host dashboard provider panels guard passed.
");
