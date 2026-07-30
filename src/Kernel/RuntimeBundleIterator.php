<?php

declare(strict_types=1);

namespace App\Kernel;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Builds Kernel bundle instances from static Symfony bootstrap config and the
 * runtime scope lock.
 *
 * This class must stay pre-container safe: no service container, no Doctrine,
 * no YAML parser, and no Administering services are used here.
 */
final class RuntimeBundleIterator
{
    /**
     * @return iterable<BundleInterface>
     */
    public static function fromProjectDir(string $projectDir, string $environment): iterable
    {
        yield from self::fromProject($projectDir, $environment);
    }

    /**
     * @return iterable<BundleInterface>
     */
    public static function fromProject(string $projectDir, string $environment): iterable
    {
        $yieldedBundleClassList = [];

        foreach (self::staticBundles($projectDir, $environment) as $bundle) {
            $yieldedBundleClassList[$bundle::class] = true;
            yield $bundle;
        }

        foreach (self::runtimeScopeBundles($projectDir, $environment) as $bundle) {
            if (isset($yieldedBundleClassList[$bundle::class])) {
                continue;
            }

            $yieldedBundleClassList[$bundle::class] = true;
            yield $bundle;
        }
    }

    /**
     * @return iterable<BundleInterface>
     */
    public static function staticBundles(string $projectDir, string $environment): iterable
    {
        $bundleConfigFile = rtrim($projectDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'bundles.php';
        if (!is_file($bundleConfigFile)) {
            return;
        }

        $bundleMap = require $bundleConfigFile;
        if (!\is_array($bundleMap)) {
            throw new \RuntimeException('config/bundles.php must return an array.');
        }

        foreach ($bundleMap as $bundleClass => $environmentMap) {
            if (!\is_string($bundleClass) || '' === $bundleClass) {
                throw new \RuntimeException('config/bundles.php contains an invalid bundle class key.');
            }

            if (!self::isBundleEnabledForEnvironment($environmentMap, $environment)) {
                continue;
            }

            if (!class_exists($bundleClass)) {
                if ('prod' === $environment) {
                    throw new \RuntimeException(sprintf('Static bundle class "%s" is enabled for environment "%s", but the class is not available.', $bundleClass, $environment));
                }

                continue;
            }

            $bundle = new $bundleClass();
            if (!$bundle instanceof BundleInterface) {
                throw new \RuntimeException(sprintf('Class "%s" is not a Symfony bundle.', $bundleClass));
            }

            yield $bundle;
        }
    }

    /**
     * @return iterable<BundleInterface>
     */
    public static function runtimeScopeBundles(string $projectDir, string $environment): iterable
    {
        $strict = RuntimeCompositionLockReader::isStrict($projectDir, $environment);

        foreach (RuntimeCompositionLockReader::enabledBundleClassList($projectDir, $environment) as $bundleClass) {
            if (!class_exists($bundleClass)) {
                if ($strict) {
                    throw new \RuntimeException(sprintf('Runtime scope bundle class "%s" is enabled by lock, but the class is not available.', $bundleClass));
                }

                continue;
            }

            $bundle = new $bundleClass();
            if (!$bundle instanceof BundleInterface) {
                throw new \RuntimeException(sprintf('Runtime scope class "%s" is not a Symfony bundle.', $bundleClass));
            }

            yield $bundle;
        }
    }

    private static function isBundleEnabledForEnvironment(mixed $environmentMap, string $environment): bool
    {
        if (!\is_array($environmentMap)) {
            return false;
        }

        return ($environmentMap['all'] ?? false) === true || ($environmentMap[$environment] ?? false) === true;
    }
}
