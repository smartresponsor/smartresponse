<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$basePath = $root . '/template/interfacing/shell/base.html.twig';
$topPath = $root . '/template/interfacing/shell/partial/top_panel.html.twig';
$cssPath = $root . '/public/interfacing/design/provider-baseline.css';
$brandAssetPath = $root . '/public/interfacing/brand/smart-response-mark.svg';
$legacyMandalaAssetPath = $root . '/public/mandala.svg';

$failures = [];
foreach ([$basePath => 'shell base', $topPath => 'top panel partial', $cssPath => 'provider CSS', $brandAssetPath => 'brand SVG asset', $legacyMandalaAssetPath => 'legacy mandala SVG asset'] as $path => $label) {
    if (!is_file($path)) {
        $failures[] = sprintf('Missing %s: %s', $label, $path);
    }
}

$base = is_file($basePath) ? (string) file_get_contents($basePath) : '';
$top = is_file($topPath) ? (string) file_get_contents($topPath) : '';
$css = is_file($cssPath) ? (string) file_get_contents($cssPath) : '';

if (!str_contains($base, "include 'interfacing/shell/partial/top_panel.html.twig'")) {
    $failures[] = 'Shell base must include the top panel partial as the single top source.';
}

foreach (['interfacing-shell-top__inner', 'Smart Responsor', 'sr-', 'data-sr-'] as $forbidden) {
    if (str_contains($base, $forbidden)) {
        $failures[] = sprintf('Shell base contains forbidden legacy token: %s', $forbidden);
    }
}

foreach ([
    'interfacing-shell-grid',
    'interfacing-shell-panel interfacing-shell-panel--primary',
    'interfacing-shell-panel interfacing-shell-panel--secondary',
    'interfacing-shell-body interfacing-shell-body--top',
    'interfacing-shell-brand__logo',
    "asset('interfacing/brand/smart-response-mark.svg')",
    'Smart Response',
    'interfacing-shell-search__button-icon',
] as $required) {
    if (!str_contains($top, $required)) {
        $failures[] = sprintf('Top panel partial misses required shared-grid token: %s', $required);
    }
}

foreach (['style=', 'interfacing-shell-top__inner', 'Smart Responsor', 'sr-', 'data-sr-'] as $forbidden) {
    if (str_contains($top, $forbidden)) {
        $failures[] = sprintf('Top panel partial contains forbidden legacy token: %s', $forbidden);
    }
}

foreach (['.interfacing-shell-top__inner', 'grid-template-columns: minmax(220px, auto) minmax(320px, 1fr) auto'] as $forbiddenCss) {
    if (str_contains($css, $forbiddenCss)) {
        $failures[] = sprintf('Provider CSS contains old independent top-grid rule: %s', $forbiddenCss);
    }
}

if (!str_contains($css, '[data-interfacing-shell-mode="three-column"] .interfacing-shell-grid') || !str_contains($css, 'grid-template-columns: 260px 230px minmax(0, 1fr);')) {
    $failures[] = 'Provider CSS must define the shared three-column shell grid as 260px 230px minmax(0, 1fr).';
}


foreach ([$brandAssetPath => 'brand SVG asset', $legacyMandalaAssetPath => 'legacy mandala SVG asset'] as $svgPath => $label) {
    if (is_file($svgPath)) {
        $svg = (string) file_get_contents($svgPath);
        if (str_contains($svg, '<!DOCTYPE') || str_contains($svg, '&ns_')) {
            $failures[] = sprintf('%s must be browser-safe and must not contain Illustrator DOCTYPE/entities.', $label);
        }
        if (!str_contains($svg, '<svg') || !str_contains($svg, 'viewBox="0 0 96.63 96.63"')) {
            $failures[] = sprintf('%s must be a valid cleaned SVG mark with the expected viewBox.', $label);
        }
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, '[shared-shell-grid-class-guard] ' . $failure . PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, '[shared-shell-grid-class-guard] PASS' . PHP_EOL);
