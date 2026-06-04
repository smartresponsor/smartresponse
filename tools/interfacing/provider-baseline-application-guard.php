<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$errors = [];

$requiredFiles = [
    'public/interfacing/design/provider-baseline.css',
    'public/interfacing/design/provider-baseline-tokens.js',
    'templates/app-host/dashboard.html.twig',
    'templates/base.html.twig',
];

foreach ($requiredFiles as $file) {
    if (!is_file($root . '/' . $file)) {
        $errors[] = 'Missing required provider baseline file: ' . $file;
    }
}

foreach ([
    'templates/interfacing/shell/base.html.twig',
    'templates/interfacing/access/base.html.twig',
    'templates/app-host/dashboard.html.twig',
    'templates/base.html.twig',
] as $template) {
    $content = is_file($root . '/' . $template) ? file_get_contents($root . '/' . $template) : '';
    if (!str_contains((string) $content, 'interfacing/design/provider-baseline.css')) {
        $errors[] = $template . ' does not load provider-baseline.css';
    }
    if (!str_contains((string) $content, 'interfacing/design/provider-baseline-tokens.js')) {
        $errors[] = $template . ' does not load provider-baseline-tokens.js';
    }
}

$css = is_file($root . '/public/interfacing/design/provider-baseline.css') ? file_get_contents($root . '/public/interfacing/design/provider-baseline.css') : '';
foreach (['--interface-provider-font-family', '--interface-provider-control-height', '--interface-provider-radius', '[data-interface-shell]', '.ant-btn', '.p-button', '!important'] as $needle) {
    if (!str_contains((string) $css, $needle)) {
        $errors[] = 'provider-baseline.css is missing expected marker: ' . $needle;
    }
}

$js = is_file($root . '/public/interfacing/design/provider-baseline-tokens.js') ? file_get_contents($root . '/public/interfacing/design/provider-baseline-tokens.js') : '';
foreach (['InterfacingProviderBaseline', 'antDesign', 'primeReact', 'interfacing:provider-baseline-ready'] as $needle) {
    if (!str_contains((string) $js, $needle)) {
        $errors[] = 'provider-baseline-tokens.js is missing expected marker: ' . $needle;
    }
}

foreach (['public/interfacing/provider/runtime.js', 'assets/interfacing/provider/runtime.js'] as $runtime) {
    $content = is_file($root . '/' . $runtime) ? file_get_contents($root . '/' . $runtime) : '';
    foreach (['attachProviderBaselineToSchema', 'providerBaseline', 'designBaseline'] as $needle) {
        if (!str_contains((string) $content, $needle)) {
            $errors[] = $runtime . ' is missing provider baseline schema marker: ' . $needle;
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[provider-baseline-application-guard] FAIL\n" . implode("\n", $errors) . "\n");
    exit(1);
}

echo "[provider-baseline-application-guard] PASS\n";

