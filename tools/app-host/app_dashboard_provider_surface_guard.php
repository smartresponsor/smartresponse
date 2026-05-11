<?php

/*
 * Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
 */

$root = dirname(__DIR__, 2);
$requiredFiles = [
    'src/Controller/AppDashboardController.php',
    'src/Contract/Ui/AppDashboardSurfaceContract.php',
    'src/Dto/Dashboard/AppDashboardSurfacePayload.php',
    'src/Service/Dashboard/AppDashboardSurfaceBuilder.php',
    'config/routes/app_host_dashboard.yaml',
    'config/packages/twig.yaml',
    'public/interfacing/admin-body/process-env.js',
    'public/interfacing/admin-body/canonical-providers.js',
];

foreach ($requiredFiles as $file) {
    if (!is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file))) {
        fwrite(STDERR, "Missing required App dashboard provider file: {$file}
");
        exit(1);
    }
}

$routes = file_get_contents($root . '/config/routes.yaml');
$appDashboardPosition = strpos($routes, 'resource: routes/app_host_dashboard.yaml');
$crudingPosition = strpos($routes, "resource: '@CrudingBundle/config/routes.yaml'");
if ($appDashboardPosition === false) {
    fwrite(STDERR, "App dashboard route import is missing from config/routes.yaml.
");
    exit(1);
}
if ($crudingPosition !== false && $appDashboardPosition > $crudingPosition) {
    fwrite(STDERR, "App dashboard route must be imported before Cruding routes.
");
    exit(1);
}

$twig = file_get_contents($root . '/config/packages/twig.yaml');
if (str_contains($twig, '%kernel.project_dir%/../Interfacing/template')) {
    fwrite(STDERR, "App host must not directly import Interfacing renderer templates.
");
    exit(1);
}

$controller = file_get_contents($root . '/src/Controller/AppDashboardController.php');
foreach ([
    'AppDashboardSurfaceContract',
    'AppDashboardSurfaceResponderInterface',
    'respond(',
] as $needle) {
    if (!str_contains($controller, $needle)) {
        fwrite(STDERR, "AppDashboardController is missing required marker: {$needle}
");
        exit(1);
    }
}
foreach (['crud-app-shell', '@Cruding/crud', 'InterfacingRendererInterface'] as $forbidden) {
    if (str_contains($controller, $forbidden)) {
        fwrite(STDERR, "AppDashboardController contains forbidden marker: {$forbidden}
");
        exit(1);
    }
}

$services = file_get_contents($root . '/config/services.yaml');
if (!str_contains($services, "App\Contract\Ui\AppDashboardSurfaceContract: '@App\Service\Dashboard\AppDashboardSurfaceBuilder'")) {
    fwrite(STDERR, "App dashboard contract service alias is missing.
");
    exit(1);
}

$builder = file_get_contents($root . '/src/Service/Dashboard/AppDashboardSurfaceBuilder.php');
foreach (['Commerce Control Center', 'app-dashboard-surface', 'ant-design-procomponents', 'primereact', 'AppDashboardSurfacePayload'] as $needle) {
    if (!str_contains($builder, $needle)) {
        fwrite(STDERR, "AppDashboardSurfaceBuilder is missing required marker: {$needle}
");
        exit(1);
    }
}
foreach (['crud-app-shell', '@Cruding/crud', '<style'] as $forbidden) {
    if (str_contains($builder, $forbidden)) {
        fwrite(STDERR, "AppDashboardSurfaceBuilder contains forbidden UI marker: {$forbidden}
");
        exit(1);
    }
}

fwrite(STDOUT, "App host dashboard provider surface guard passed.
");
