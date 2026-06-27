<?php

declare(strict_types=1);

$root = realpath($argv[1] ?? getcwd());
if (false === $root) {
    fwrite(STDERR, "Invalid root path.\n");
    exit(2);
}

$path = $root.'/config/reference.php';
if (!is_file($path)) {
    fwrite(STDERR, "config/reference.php is missing.\n");
    exit(2);
}

$content = (string) file_get_contents($path);
$normalized = str_replace("\r\n", "\n", $content);
$expectedPrefix = "<?php\n\ndeclare(strict_types=1);";

if (!str_starts_with($normalized, $expectedPrefix)) {
    fwrite(STDERR, "App reference strict types guard failed:\n");
    fwrite(STDERR, " - config/reference.php must start with <?php, a blank line, and declare(strict_types=1).\n");
    exit(1);
}

$firstNamespacePosition = strpos($normalized, 'namespace ');
$declarePosition = strpos($normalized, 'declare(strict_types=1);');

if (false === $firstNamespacePosition || false === $declarePosition || $declarePosition > $firstNamespacePosition) {
    fwrite(STDERR, "App reference strict types guard failed:\n");
    fwrite(STDERR, " - config/reference.php declare(strict_types=1) must appear before the first namespace declaration.\n");
    exit(1);
}

fwrite(STDOUT, "App reference strict types guard passed.\n");
exit(0);
