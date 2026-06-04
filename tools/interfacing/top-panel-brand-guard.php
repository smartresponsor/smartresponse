<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$basePath = $root . DIRECTORY_SEPARATOR . 'templates/interfacing/shell/base.html.twig';
$partialPath = $root . DIRECTORY_SEPARATOR . 'templates/interfacing/shell/partial/top_panel.html.twig';
$cssPath = $root . DIRECTORY_SEPARATOR . 'public/interfacing/design/provider-baseline.css';

$failures = [];

foreach ([$basePath => 'shell base', $partialPath => 'top panel partial', $cssPath => 'provider CSS'] as $path => $label) {
    if (!is_file($path)) {
        $failures[] = sprintf('Missing %s: %s', $label, $path);
    }
}

$base = is_file($basePath) ? (string) file_get_contents($basePath) : '';
$partial = is_file($partialPath) ? (string) file_get_contents($partialPath) : '';
$css = is_file($cssPath) ? (string) file_get_contents($cssPath) : '';

if (!str_contains($base, "partial/top_panel.html.twig")) {
    $failures[] = 'Shell base must include top_panel partial as the single top-panel source.';
}

if (str_contains($base, '<header class="interface-shell-top"')) {
    $failures[] = 'Shell base must not own top-panel header markup.';
}

foreach (['Smart Responsor', '<div class="interface-shell-brand__title">'] as $forbidden) {
    if (str_contains($base, $forbidden)) {
        $failures[] = sprintf('Shell base still contains forbidden top-panel drift marker: %s', $forbidden);
    }
}

foreach ([
    'interface-shell-brand__logo',
    "asset('mandala.svg')",
    'Smart Response',
    'interface-shell-search__button-icon',
] as $required) {
    if (!str_contains($partial, $required)) {
        $failures[] = sprintf('Top panel partial missing required marker: %s', $required);
    }
}

foreach (['Smart Responsor', 'style='] as $forbidden) {
    if (str_contains($partial, $forbidden)) {
        $failures[] = sprintf('Top panel partial contains forbidden marker: %s', $forbidden);
    }
}

foreach (['.interface-shell-brand__logo', '.interface-shell-brand__text', '.interface-shell-search__button-icon'] as $selector) {
    if (!str_contains($css, $selector)) {
        $failures[] = sprintf('Provider CSS missing top panel selector: %s', $selector);
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, '[top-panel-brand-guard] ' . $failure . PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, '[top-panel-brand-guard] PASS' . PHP_EOL);

