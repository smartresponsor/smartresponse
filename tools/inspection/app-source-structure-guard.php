<?php

declare(strict_types=1);

function inspectAppSourceStructure(string $root): array
{
    $src = rtrim($root, '/\\').DIRECTORY_SEPARATOR.'src';
    $violations = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || 'php' !== strtolower($file->getExtension())) {
            continue;
        }

        $path = $file->getPathname();
        $relative = str_replace('\\', '/', substr($path, strlen(rtrim($root, '/\\')) + 1));
        $content = file_get_contents($path);
        if (false === $content) {
            $violations[] = $relative.': unreadable';
            continue;
        }

        if (preg_match('/^src\/[^\/]+\/Service\//', $relative)) {
            $violations[] = $relative.': technical role must precede dependency/context (use src/Service/<context>/...)';
        }
        if (str_starts_with($relative, 'src/EntityTrait/')) {
            $violations[] = $relative.': use src/Entity/Trait/...';
        }
        if (str_starts_with($relative, 'src/ServiceInterface/')) {
            $violations[] = $relative.': service interfaces belong beside services under src/Service/<context>/...';
        }

        if (!preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
            if ('src/Kernel.php' !== $relative && 'src/Schedule.php' !== $relative) {
                $violations[] = $relative.': namespace not found';
            }
            continue;
        }

        $directory = dirname(substr($relative, 4));
        $expectedNamespace = 'App'.('.' === $directory ? '' : '\\'.str_replace('/', '\\', $directory));
        if ($namespaceMatch[1] !== $expectedNamespace) {
            $violations[] = sprintf('%s: namespace %s must match %s', $relative, $namespaceMatch[1], $expectedNamespace);
        }

        if (!preg_match('/^(?:(?:final|abstract|readonly)\s+)*(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $content, $classMatch)) {
            continue;
        }

        $name = $classMatch[1];
        if (pathinfo($relative, PATHINFO_FILENAME) !== $name) {
            $violations[] = sprintf('%s: filename must match class-like symbol %s', $relative, $name);
        }
        if (str_starts_with($name, 'App') && str_contains(substr($name, 3), 'Application')) {
            $violations[] = $relative.': App-owned symbols must not use Application as an alias for the App token';
        }
        if (str_starts_with($name, 'Api') && str_ends_with($name, 'Controller')) {
            $violations[] = $relative.': controller ownership token must precede Api';
        }

        $rules = [
            'Subscriber' => 'src/EventSubscriber/',
            'Listener' => 'src/EventListener/',
            'Controller' => 'src/Controller/',
            'Command' => 'src/Command/',
            'Handler' => 'src/MessageHandler/',
            'Message' => 'src/Message/',
            'Type' => 'src/Form/',
            'Extension' => 'src/Twig/',
            'Pass' => 'src/DependencyInjection/Compiler/',
        ];

        foreach ($rules as $suffix => $prefix) {
            if (str_ends_with($name, $suffix) && !str_starts_with($relative, $prefix)) {
                $violations[] = sprintf('%s: %s symbols belong under %s', $relative, $suffix, $prefix);
            }
        }

        if (str_ends_with($name, 'Service') && !str_starts_with($relative, 'src/Service/')) {
            $violations[] = $relative.': Service symbols belong under src/Service/';
        }
    }

    sort($violations);

    return array_values(array_unique($violations));
}

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $violations = inspectAppSourceStructure(dirname(__DIR__, 2));
    if ([] !== $violations) {
        fwrite(STDERR, implode(PHP_EOL, $violations).PHP_EOL);
        exit(1);
    }

    fwrite(STDOUT, "App source structure: OK".PHP_EOL);
}
