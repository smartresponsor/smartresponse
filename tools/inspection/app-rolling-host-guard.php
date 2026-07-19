<?php

declare(strict_types=1);

use App\Kernel;
use App\Rolling\RollingBundle;
use App\Rolling\Service\Http\Role\HealthHttpService;
use App\Rolling\ServiceInterface\Cruding\RollingCrudResourceDefinitionProviderInterface;

$root = realpath($argv[1] ?? getcwd());
if (false === $root) {
    fwrite(STDERR, "Invalid root path.\n");
    exit(2);
}

$findings = [];
$warnings = [];

$composerPath = $root.'/composer.json';
$prodComposerPath = $root.'/composer.prod.json';
$bundlesPath = $root.'/config/bundles.php';
$defaultRuntimeLockPath = $root.'/config/kernel/runtime_scope.lock.php';
$prodRuntimeLockPath = $root.'/config/kernel/runtime_scope.prod.lock.php';
$vendorAutoloadPath = $root.'/vendor/autoload.php';

$composer = readJsonFile($composerPath, $findings);
$prodComposer = readJsonFile($prodComposerPath, $findings);

if (is_array($composer)) {
    assertPackageRequirement($composer, 'rolling/role', '*@dev', 'composer.json', $findings);
    assertPathRepository($composer, '../Rolling', true, 'composer.json', $findings);
}

if (is_array($prodComposer)) {
    assertPackageRequirement($prodComposer, 'rolling/role', 'dev-master', 'composer.prod.json', $findings);
    assertVcsRepository($prodComposer, 'smartresponsor/rolling.git', 'composer.prod.json', $findings);
}

if (!is_file($bundlesPath)) {
    $findings[] = 'config/bundles.php is missing.';
} else {
    $bundles = (string) file_get_contents($bundlesPath);
    if (!str_contains($bundles, 'App\\Rolling\\RollingBundle::class')) {
        $findings[] = 'config/bundles.php does not register App\\Rolling\\RollingBundle.';
    }
}

if (!is_file($vendorAutoloadPath)) {
    $findings[] = 'vendor/autoload.php is missing; host container cannot be inspected.';
} else {
    require_once $vendorAutoloadPath;
}

assertRuntimeLock($defaultRuntimeLockPath, 'config/kernel/runtime_scope.lock.php', false, $findings);
assertRuntimeLock($prodRuntimeLockPath, 'config/kernel/runtime_scope.prod.lock.php', true, $findings);

$vendorPackagePath = $root.'/vendor/rolling/role';
$expectedRollingRoot = realpath($root.'/../Rolling');
$actualRollingRoot = realpath($vendorPackagePath);
if (false === $actualRollingRoot) {
    $findings[] = 'vendor/rolling/role is missing; run composer install for the development host.';
} elseif (false !== $expectedRollingRoot && normalizePath($actualRollingRoot) !== normalizePath($expectedRollingRoot)) {
    $findings[] = sprintf(
        'vendor/rolling/role resolves to %s, expected development path %s.',
        normalizePath($actualRollingRoot),
        normalizePath($expectedRollingRoot),
    );
}

if ([] === $findings && is_file($vendorAutoloadPath)) {
    if (!class_exists(Kernel::class)) {
        $findings[] = 'App\\Kernel class is not autoloadable.';
    }

    if (!class_exists(RollingBundle::class)) {
        $findings[] = 'App\\Rolling\\RollingBundle class is not autoloadable from host vendor.';
    }

    if ([] === $findings) {
        fwrite(STDOUT, "phase: kernel_construct\n");
        $kernel = new Kernel('test', true);
        fwrite(STDOUT, "phase: kernel_boot\n");
        $kernel->boot();
        fwrite(STDOUT, "phase: kernel_booted\n");

        try {
            $bundleClasses = array_map(static fn (object $bundle): string => $bundle::class, $kernel->getBundles());
            if (!in_array(RollingBundle::class, $bundleClasses, true)) {
                $findings[] = 'App\\Rolling\\RollingBundle is not loaded by App\\Kernel.';
            }

            $container = $kernel->getContainer();
            if (!$container->has(HealthHttpService::class)) {
                $findings[] = 'Host container does not expose Rolling HealthHttpService.';
            }

            if (!$container->has(RollingCrudResourceDefinitionProviderInterface::class)) {
                $warnings[] = 'Host test container does not expose RollingCrudResourceDefinitionProviderInterface as a public service; this can be acceptable if the alias is private.';
            }

            if (!$container->has('router')) {
                $findings[] = 'Host container does not expose router service.';
            } else {
                $routes = $container->get('router')->getRouteCollection();
                foreach (['role_access_check', 'role_health'] as $routeName) {
                    if (null === $routes->get($routeName)) {
                        $findings[] = sprintf('Host router does not include Rolling route %s.', $routeName);
                    }
                }
            }
        } finally {
            $kernel->shutdown();
        }
    }
}

if ([] !== $findings) {
    fwrite(STDERR, "App Rolling host guard failed:\n");
    foreach ($findings as $finding) {
        fwrite(STDERR, ' - '.$finding."\n");
    }
    foreach ($warnings as $warning) {
        fwrite(STDERR, ' warning: '.$warning."\n");
    }
    exit(1);
}

fwrite(STDOUT, "App Rolling host guard passed.\n");
foreach ($warnings as $warning) {
    fwrite(STDOUT, 'warning: '.$warning."\n");
}
exit(0);

/** @return array<string, mixed>|null */
function readJsonFile(string $path, array &$findings): ?array
{
    if (!is_file($path)) {
        $findings[] = sprintf('%s is missing.', basename($path));
        return null;
    }

    try {
        $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        $findings[] = sprintf('%s contains invalid JSON: %s.', basename($path), $exception->getMessage());
        return null;
    }

    return is_array($decoded) ? $decoded : null;
}

/** @param array<string, mixed> $composer */
function assertPackageRequirement(array $composer, string $package, string $expectedConstraint, string $label, array &$findings): void
{
    $actual = $composer['require'][$package] ?? null;
    if ($actual !== $expectedConstraint) {
        $findings[] = sprintf('%s requires %s as %s, expected %s.', $label, $package, var_export($actual, true), $expectedConstraint);
    }
}

/** @param array<string, mixed> $composer */
function assertPathRepository(array $composer, string $expectedUrl, bool $expectedSymlink, string $label, array &$findings): void
{
    foreach (($composer['repositories'] ?? []) as $repository) {
        if (($repository['type'] ?? null) === 'path' && ($repository['url'] ?? null) === $expectedUrl) {
            if (($repository['options']['symlink'] ?? null) !== $expectedSymlink) {
                $findings[] = sprintf('%s path repository %s must set symlink=%s.', $label, $expectedUrl, $expectedSymlink ? 'true' : 'false');
            }
            return;
        }
    }

    $findings[] = sprintf('%s does not declare path repository %s.', $label, $expectedUrl);
}

/** @param array<string, mixed> $composer */
function assertVcsRepository(array $composer, string $expectedUrlPart, string $label, array &$findings): void
{
    foreach (($composer['repositories'] ?? []) as $repository) {
        if (($repository['type'] ?? null) === 'vcs' && str_contains((string) ($repository['url'] ?? ''), $expectedUrlPart)) {
            return;
        }
    }

    $findings[] = sprintf('%s does not declare VCS repository containing %s.', $label, $expectedUrlPart);
}

function assertRuntimeLock(string $path, string $label, bool $production, array &$findings): void
{
    if (!is_file($path)) {
        $findings[] = sprintf('%s is missing.', $label);
        return;
    }

    $lock = require $path;
    if (!is_array($lock)) {
        $findings[] = sprintf('%s must return an array.', $label);
        return;
    }

    if (!in_array(App\Rolling\RollingBundle::class, $lock['enabledBundles'] ?? [], true)) {
        $findings[] = sprintf('%s does not enable App\\Rolling\\RollingBundle.', $label);
    }

    if ($production && ($lock['sourceComposerFile'] ?? null) !== 'composer.prod.json') {
        $findings[] = sprintf('%s must use composer.prod.json as sourceComposerFile.', $label);
    }
}

function normalizePath(string $path): string
{
    return str_replace('\\', '/', strtolower($path));
}
