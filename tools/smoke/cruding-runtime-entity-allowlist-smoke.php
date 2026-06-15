<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$services = file_get_contents($root.'/config/services.yaml');
$webRoute = file_get_contents($root.'/config/routes/cruding_crud.yaml');
$apiRoute = file_get_contents($root.'/config/routes/cruding_api_crud.yaml');
$extension = file_get_contents($root.'/src/DependencyInjection/CrudingExtension.php');

$errors = [];

if (!str_contains($services, "cruding.runtime_entity_requirement: '(?!)'")) {
    $errors[] = 'Runtime entity requirement must default to fail-closed (?!).';
}

if (str_contains($services, "cruding.resource_requirement: '[a-z][a-z0-9_-]*'")) {
    $errors[] = 'Broad resource fallback is still present.';
}

foreach (['web' => $webRoute, 'api' => $apiRoute] as $name => $route) {
    if (!str_contains($route, "%cruding.runtime_entity_requirement%(?:/.*)?")) {
        $errors[] = sprintf('%s route does not use the exported runtime entity requirement.', $name);
    }
}

foreach ([
    "setParameter('cruding.runtime_entity_allow_tokens', ",
    "setParameter('cruding.runtime_entity_requirement', ",
    'assertValidRuntimeEntityAllowlist',
    "'/^[a-z][a-z0-9_-]*$/'",
] as $needle) {
    if (!str_contains($extension, $needle)) {
        $errors[] = sprintf('CrudingExtension is missing required allowlist contract fragment: %s', $needle);
    }
}

if ([] !== $errors) {
    fwrite(STDERR, implode(PHP_EOL, $errors).PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Cruding runtime entity allowlist smoke: PASS\n");
