<?php

declare(strict_types=1);

$root = realpath($argv[1] ?? getcwd());
if (false === $root) {
    fwrite(STDERR, "Invalid root path.\n");
    exit(2);
}

$findings = [];

$readerPath = $root.'/src/Kernel/RuntimeCompositionLockReader.php';
$defaultLockPath = $root.'/config/kernel/runtime_scope.lock.php';
$prodLockPath = $root.'/config/kernel/runtime_scope.prod.lock.php';

foreach ([$readerPath, $defaultLockPath, $prodLockPath] as $requiredPath) {
    if (!is_file($requiredPath)) {
        $findings[] = sprintf('%s is missing.', relativePath($root, $requiredPath));
    }
}

if (is_file($readerPath)) {
    $reader = (string) file_get_contents($readerPath);
    foreach ([
        "public const DEFAULT_LOCK_PATH = 'config/kernel/runtime_scope.lock.php';",
        "public const PROD_LOCK_PATH = 'config/kernel/runtime_scope.prod.lock.php';",
        'enabledBundleClassList',
        'resolveLockPath',
    ] as $needle) {
        if (!str_contains($reader, $needle)) {
            $findings[] = sprintf('src/Kernel/RuntimeCompositionLockReader.php is missing required lock-reader marker %s.', $needle);
        }
    }

    foreach (['APP_RUNTIME_SCOPE', 'APP_RUNTIME_ENTITY', 'getenv(', '$_ENV', '$_SERVER'] as $forbidden) {
        if (str_contains($reader, $forbidden)) {
            $findings[] = sprintf('src/Kernel/RuntimeCompositionLockReader.php must not use %s as runtime authority.', $forbidden);
        }
    }
}

if (is_file($defaultLockPath)) {
    $defaultLock = require $defaultLockPath;
    if (!is_array($defaultLock)) {
        $findings[] = 'config/kernel/runtime_scope.lock.php must return an array.';
    } else {
        foreach (['strict', 'enabledBundles', 'scope', 'entity', 'packages'] as $requiredKey) {
            if (!array_key_exists($requiredKey, $defaultLock)) {
                $findings[] = sprintf('config/kernel/runtime_scope.lock.php is missing key %s.', $requiredKey);
            }
        }

        foreach (['enabledBundles', 'scope', 'entity', 'packages'] as $listKey) {
            if (array_key_exists($listKey, $defaultLock) && !is_array($defaultLock[$listKey])) {
                $findings[] = sprintf('config/kernel/runtime_scope.lock.php key %s must be an array.', $listKey);
            }
        }
    }
}

if (is_file($prodLockPath)) {
    $prodLock = require $prodLockPath;
    if (!is_array($prodLock)) {
        $findings[] = 'config/kernel/runtime_scope.prod.lock.php must return an array.';
    } else {
        foreach (['schema', 'sourceComposerFile', 'strict', 'enabledBundles', 'disabledComponents'] as $requiredKey) {
            if (!array_key_exists($requiredKey, $prodLock)) {
                $findings[] = sprintf('config/kernel/runtime_scope.prod.lock.php is missing key %s.', $requiredKey);
            }
        }

        if (($prodLock['schema'] ?? null) !== 'app.kernel.runtime_scope.v1') {
            $findings[] = 'config/kernel/runtime_scope.prod.lock.php schema must be app.kernel.runtime_scope.v1.';
        }

        if (($prodLock['sourceComposerFile'] ?? null) !== 'composer.prod.json') {
            $findings[] = 'config/kernel/runtime_scope.prod.lock.php sourceComposerFile must be composer.prod.json.';
        }

        foreach (['enabledBundles', 'disabledComponents'] as $listKey) {
            if (array_key_exists($listKey, $prodLock) && !is_array($prodLock[$listKey])) {
                $findings[] = sprintf('config/kernel/runtime_scope.prod.lock.php key %s must be an array.', $listKey);
            }
        }
    }
}

foreach (runtimeSourceFiles($root) as $filePath) {
    $relative = relativePath($root, $filePath);
    if (str_starts_with($relative, 'config/reference.php')) {
        continue;
    }

    $contents = (string) file_get_contents($filePath);
    foreach (['APP_RUNTIME_SCOPE', 'APP_RUNTIME_ENTITY'] as $legacyEnvName) {
        if (str_contains($contents, $legacyEnvName)) {
            $findings[] = sprintf('%s uses %s; App runtime authority must come from config/kernel/runtime_scope*.lock.php.', $relative, $legacyEnvName);
        }
    }
}

if ([] !== $findings) {
    fwrite(STDERR, "App runtime lock authority guard failed:\n");
    foreach ($findings as $finding) {
        fwrite(STDERR, ' - '.$finding."\n");
    }
    exit(1);
}

fwrite(STDOUT, "App runtime lock authority guard passed.\n");
exit(0);

function relativePath(string $root, string $path): string
{
    return str_replace('\\', '/', substr($path, strlen($root) + 1));
}

/** @return list<string> */
function runtimeSourceFiles(string $root): array
{
    $roots = ['src', 'config'];
    $files = [];

    foreach ($roots as $scanRoot) {
        $directory = $root.DIRECTORY_SEPARATOR.$scanRoot;
        if (!is_dir($directory)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            if (in_array($extension, ['php', 'yaml', 'yml', 'xml', 'neon'], true)) {
                $files[] = $file->getPathname();
            }
        }
    }

    return $files;
}

