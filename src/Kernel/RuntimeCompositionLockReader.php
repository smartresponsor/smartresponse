<?php

declare(strict_types=1);

namespace App\Kernel;

/**
 * Reads the pre-container runtime scope lock used by Kernel boot.
 *
 * This reader is intentionally independent from the Symfony service container,
 * Doctrine, YAML parsing, and Administering runtime services. Kernel boot must be
 * able to execute it before the dependency injection container exists.
 */
final class RuntimeCompositionLockReader
{
    public const DEFAULT_LOCK_PATH = 'config/kernel/runtime_scope.lock.php';
    public const PROD_LOCK_PATH = 'config/kernel/runtime_scope.prod.lock.php';

    /**
     * @return list<class-string>
     */
    public static function enabledBundleClassList(string $projectDir, string $environment, ?string $lockPath = null): array
    {
        $lock = self::read($projectDir, $environment, $lockPath);

        $enabledBundles = $lock['enabledBundles'] ?? [];
        if (!\is_array($enabledBundles)) {
            throw new \RuntimeException('Runtime scope lock key "enabledBundles" must be an array.');
        }

        $bundleClassList = [];
        foreach ($enabledBundles as $bundleClass) {
            if (!\is_string($bundleClass) || '' === $bundleClass) {
                throw new \RuntimeException('Runtime scope lock contains an invalid bundle class entry.');
            }

            $bundleClassList[] = $bundleClass;
        }

        return array_values(array_unique($bundleClassList));
    }

    /**
     * @return array<string, mixed>
     */
    public static function read(string $projectDir, string $environment, ?string $lockPath = null): array
    {
        $resolvedLockPath = self::resolveLockPath($projectDir, $environment, $lockPath);
        if (!is_file($resolvedLockPath)) {
            if ('prod' === $environment) {
                throw new \RuntimeException(sprintf('Production runtime scope lock "%s" is missing.', $resolvedLockPath));
            }

            return [];
        }

        $lock = require $resolvedLockPath;
        if (!\is_array($lock)) {
            throw new \RuntimeException(sprintf('Runtime scope lock "%s" must return an array.', $resolvedLockPath));
        }

        return $lock;
    }

    public static function isStrict(string $projectDir, string $environment, ?string $lockPath = null): bool
    {
        $lock = self::read($projectDir, $environment, $lockPath);

        return ($lock['strict'] ?? false) === true;
    }

    public static function defaultLockPathForEnvironment(string $environment): string
    {
        return 'prod' === $environment ? self::PROD_LOCK_PATH : self::DEFAULT_LOCK_PATH;
    }

    public static function resolveLockPath(string $projectDir, string $environment, ?string $lockPath = null): string
    {
        $candidate = $lockPath ?: self::defaultLockPathForEnvironment($environment);
        if (self::isAbsolutePath($candidate)) {
            return $candidate;
        }

        return rtrim($projectDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);
    }

    private static function isAbsolutePath(string $path): bool
    {
        if ('' === $path) {
            return false;
        }

        if ('/' === $path[0] || '\\' === $path[0]) {
            return true;
        }

        return \strlen($path) > 2 && ctype_alpha($path[0]) && ':' === $path[1];
    }
}
