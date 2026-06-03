<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$failures = [];

$base = $root . '/template/interfacing/shell/base.html.twig';
$css = $root . '/public/interfacing/design/provider-baseline.css';

$baseContents = is_file($base) ? (string) file_get_contents($base) : '';
$cssContents = is_file($css) ? (string) file_get_contents($css) : '';

foreach ([
    'class="interfacing-provider-body"',
    'class="interfacing-shell"',
    'data-interfacing-shell=',
    'class="interfacing-shell-top"',
    'class="interfacing-shell-grid"',
    'class="interfacing-brand"',
    'interfacing-brand__logo',
    'Smart Response',
    'class="interfacing-shell-search__button" type="submit" aria-label="Search" title="Search"></button>',
] as $needle) {
    if (!str_contains($baseContents, $needle)) {
        $failures[] = sprintf('Missing canonical source marker in shell base: %s', $needle);
    }
}

foreach ([
    'class="sr-provider-body"',
    'class="sr-shell"',
    'data-sr-shell=',
    'class="sr-shell-top"',
    'Smart Responsor',
    '>Search</button>',
    'provider-baseline-20260527c',
] as $forbidden) {
    if (str_contains($baseContents, $forbidden)) {
        $failures[] = sprintf('Forbidden legacy runtime marker in shell base: %s', $forbidden);
    }
}

foreach ([
    '.interfacing-shell-grid',
    '.interfacing-shell-top .interfacing-shell-grid',
    '.interfacing-brand__logo',
    '.interfacing-shell-search__button::before',
    '--interfacing-provider-font-family',
] as $needle) {
    if (!str_contains($cssContents, $needle)) {
        $failures[] = sprintf('Missing canonical CSS marker: %s', $needle);
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, '[interface-runtime-source-guard] ' . $failure . PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, '[interface-runtime-source-guard] PASS' . PHP_EOL);
