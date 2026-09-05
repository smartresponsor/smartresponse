<?php

declare(strict_types=1);

function inspectAppSourceStructure(string $root): array
{
    $root = rtrim($root, '/\\');
    $src = $root.DIRECTORY_SEPARATOR.'src';
    $violations = [];

    $legacyTestService = $root.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Service';
    if (is_dir($legacyTestService)) {
        $testIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($legacyTestService, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($testIterator as $testFile) {
            if ($testFile instanceof SplFileInfo && 'php' === strtolower($testFile->getExtension())) {
                $relativeTest = str_replace('\\', '/', substr($testFile->getPathname(), strlen($root) + 1));
                $violations[] = $relativeTest.': unit service tests belong under tests/Unit/Service/...';
            }
        }
    }

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
        if (str_starts_with($relative, 'src/Service/Retail/Placement/')) {
            $violations[] = $relative.': domain token Retail must not precede direction Placement; use src/Service/Placement/Retail/...';
        }
        if (str_starts_with($relative, 'src/Dto/Retail/Placement/')) {
            $violations[] = $relative.': domain token Retail must not precede direction Placement; use src/Dto/Placement/Retail/...';
        }
        if (str_starts_with($relative, 'src/Form/Retail/Placement/')) {
            $violations[] = $relative.': domain token Retail must not precede direction Placement; use src/Form/Placement/Retail/...';
        }
        if (str_starts_with($relative, 'src/EventSubscriber/Retail/Placement/')) {
            $violations[] = $relative.': domain token Retail must not precede direction Placement; use src/EventSubscriber/Placement/Retail/...';
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

        if (!preg_match('/^(?:(?:final|abstract|readonly)\s+)*(class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $content, $classMatch)) {
            continue;
        }

        $kind = $classMatch[1];
        $name = $classMatch[2];
        if (pathinfo($relative, PATHINFO_FILENAME) !== $name) {
            $violations[] = sprintf('%s: filename must match class-like symbol %s', $relative, $name);
        }

        if ('interface' === $kind) {
            if (!preg_match('#^src/[^/]*Interface(?:/|$)#', $relative)) {
                $violations[] = $relative.': interfaces must live in an explicit mirrored src/*Interface/... tree';
            }

            $implementationRoots = [
                'src/Service/',
                'src/Provider/',
                'src/Resolver/',
                'src/Registry/',
                'src/Manager/',
                'src/Worker/',
                'src/Normalizer/',
                'src/Repository/',
                'src/Controller/',
                'src/Form/',
                'src/Voter/',
                'src/EventSubscriber/',
                'src/Subscriber/',
                'src/Listener/',
                'src/Command/',
            ];
            foreach ($implementationRoots as $implementationRoot) {
                if (str_starts_with($relative, $implementationRoot)) {
                    $violations[] = sprintf('%s: interfaces must live in the mirrored *Interface tree, not %s', $relative, $implementationRoot);
                }
            }

            $interfaceMirrors = [
                'ServiceInterface' => ['src/ServiceInterface/'],
                'ProviderInterface' => ['src/ProviderInterface/'],
                'ResolverInterface' => ['src/ResolverInterface/'],
                'RegistryInterface' => ['src/RegistryInterface/'],
                'ManagerInterface' => ['src/ManagerInterface/'],
                'WorkerInterface' => ['src/WorkerInterface/'],
                'NormalizerInterface' => ['src/NormalizerInterface/'],
                'EntityInterface' => ['src/EntityInterface/'],
                'RepositoryInterface' => ['src/RepositoryInterface/'],
                'ControllerInterface' => ['src/ControllerInterface/'],
                'TypeInterface' => ['src/TypeInterface/'],
                'VoterInterface' => ['src/VoterInterface/'],
                'SubscriberInterface' => ['src/EventSubscriberInterface/', 'src/SubscriberInterface/'],
                'ListenerInterface' => ['src/ListenerInterface/'],
                'CommandInterface' => ['src/CommandInterface/'],
            ];
            foreach ($interfaceMirrors as $suffix => $allowedPrefixes) {
                if (!str_ends_with($name, $suffix)) {
                    continue;
                }
                $inMirror = false;
                foreach ($allowedPrefixes as $allowedPrefix) {
                    if (str_starts_with($relative, $allowedPrefix)) {
                        $inMirror = true;
                        break;
                    }
                }
                if (!$inMirror) {
                    $violations[] = sprintf('%s: %s symbols belong in their mirrored interface tree', $relative, $suffix);
                }
            }
        }
        if (str_starts_with($name, 'App') && str_contains(substr($name, 3), 'Application')) {
            $violations[] = $relative.': App-owned symbols must not use Application as an alias for the App token';
        }

        $dependencyOwnedAppPrefixes = [
            'AppAccess',
            'AppAddress',
            'AppAddressing',
            'AppAttachment',
            'AppCase',
            'AppCrud',
            'AppDelivering',
            'AppFulfillment',
            'AppNotifying',
            'AppOrder',
            'AppPayment',
            'AppPricing',
            'AppProjecting',
            'AppRetail',
            'AppWithdrawal',
        ];
        foreach ($dependencyOwnedAppPrefixes as $dependencyOwnedAppPrefix) {
            if (str_starts_with($name, $dependencyOwnedAppPrefix)) {
                $violations[] = sprintf(
                    '%s: dependency-owned symbol %s must use its dependency ownership token without the App prefix',
                    $relative,
                    $name,
                );
                break;
            }
        }
        if (str_contains($name, 'Github')) {
            $violations[] = $relative.': use canonical GitHub brand casing in class-like symbols';
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
            'Provider' => 'src/Provider/',
            'Resolver' => 'src/Resolver/',
            'Registry' => 'src/Registry/',
            'Manager' => 'src/Manager/',
            'Worker' => 'src/Worker/',
            'Normalizer' => 'src/Normalizer/',
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
