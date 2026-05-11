<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$asset = $root . '/public/interfacing/admin-body/canonical-providers.js';
if (!is_file($asset)) {
    fwrite(STDERR, "Missing canonical provider bundle: {$asset}\n");
    exit(1);
}
$content = (string) file_get_contents($asset);
$required = [
    'Interfacing Wave 46 browser process/env shim',
    'globalThis.process',
    'process.env.NODE_ENV',
    'production',
];
foreach ($required as $needle) {
    if (!str_contains($content, $needle)) {
        fwrite(STDERR, "Missing browser process/env shim marker: {$needle}\n");
        exit(1);
    }
}
if (preg_match('/^\s*process\.env\.NODE_ENV/m', $content) === 1) {
    fwrite(STDERR, "Provider bundle still starts with unguarded process.env.NODE_ENV usage.\n");
    exit(1);
}

echo "Interfacing admin body browser process/env guard passed.\n";
