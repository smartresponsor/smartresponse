<?php

declare(strict_types=1);

namespace App\Resolver\CodeMemory;

/**
 * Resolves the safe Code Memory scope for a filesystem working directory.
 *
 * The resolver is intentionally read-only. It does not call codebase-memory-mcp
 * and it does not mutate Composer, vendor, or graph state. Composer path
 * repositories are used as the source of truth for host-application read scope.
 */
final class CodeMemoryScopeResolver
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(string $cwd): array
    {
        $activeRoot = $this->resolveActiveRoot($cwd);
        $activeProject = $this->memoryProjectName($activeRoot);
        $composerPath = $activeRoot.DIRECTORY_SEPARATOR.'composer.json';
        $composer = $this->readJsonObject($composerPath);
        $lockPackageNames = $this->readComposerLockPackageNames($activeRoot.DIRECTORY_SEPARATOR.'composer.lock');
        $installedPackageNames = $this->readInstalledPackageNames($activeRoot);
        $pathRepositories = $this->resolvePathRepositories($activeRoot, $composer, $lockPackageNames, $installedPackageNames);
        $mode = [] === $pathRepositories ? 'repo-local' : 'host-with-composer-links';

        return [
            'activeRoot' => $this->normalizePath($activeRoot),
            'activeProject' => $activeProject,
            'mode' => $mode,
            'source' => 'composer-path-repository-resolver',
            'dependencyFingerprint' => $this->dependencyFingerprint($activeRoot),
            'editProjects' => [
                [
                    'project' => $activeProject,
                    'root' => $this->normalizePath($activeRoot),
                    'reason' => 'active-root',
                    'weight' => 1.0,
                ],
            ],
            'readProjects' => array_values(array_merge([
                [
                    'project' => $activeProject,
                    'root' => $this->normalizePath($activeRoot),
                    'reason' => 'active-root',
                    'weight' => 1.0,
                ],
            ], $pathRepositories)),
            'globalProject' => [
                'project' => $this->memoryProjectName($this->workspaceRoot($activeRoot)),
                'root' => $this->normalizePath($this->workspaceRoot($activeRoot)),
                'mode' => 'navigation-only',
                'weight' => 0.1,
            ],
            'rules' => [
                'rawUnscopedGraphSearchAllowed' => false,
                'linkedProjectEditAllowedByDefault' => false,
                'relatedProjectSourceOfTruth' => 'composer.json repositories + composer.lock + vendor/composer/installed.* + real local paths',
            ],
        ];
    }

    private function resolveActiveRoot(string $cwd): string
    {
        $path = $this->normalizeNativePath($cwd);

        if (is_file($path)) {
            $path = dirname($path);
        }

        $real = realpath($path);
        if (false !== $real) {
            $path = $real;
        }

        $cursor = rtrim($path, DIRECTORY_SEPARATOR);
        while ('' !== $cursor && $cursor !== dirname($cursor)) {
            if (is_file($cursor.DIRECTORY_SEPARATOR.'composer.json') || is_dir($cursor.DIRECTORY_SEPARATOR.'.git')) {
                return $cursor;
            }

            $cursor = dirname($cursor);
        }

        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * @param array<string, mixed> $composer
     * @param array<string, true>  $lockPackageNames
     * @param array<string, true>  $installedPackageNames
     *
     * @return list<array<string, mixed>>
     */
    private function resolvePathRepositories(string $activeRoot, array $composer, array $lockPackageNames, array $installedPackageNames): array
    {
        $repositories = $composer['repositories'] ?? [];
        if (!is_array($repositories)) {
            return [];
        }

        $resolved = [];
        foreach ($repositories as $repository) {
            if (!is_array($repository)) {
                continue;
            }

            if (($repository['type'] ?? null) !== 'path') {
                continue;
            }

            $url = $repository['url'] ?? null;
            if (!is_string($url) || '' === trim($url)) {
                continue;
            }

            if (str_contains($url, '*') || str_contains($url, '?')) {
                continue;
            }

            $target = $this->resolvePathAgainst($activeRoot, $url);
            $targetComposer = $this->readJsonObject($target.DIRECTORY_SEPARATOR.'composer.json');
            $packageName = $this->packageName($targetComposer);

            $resolved[] = [
                'project' => $this->memoryProjectName($target),
                'root' => $this->normalizePath($target),
                'reason' => 'composer-path-repository',
                'weight' => 0.7,
                'package' => $packageName,
                'repositoryUrl' => $url,
                'exists' => is_dir($target),
                'composerJsonExists' => is_file($target.DIRECTORY_SEPARATOR.'composer.json'),
                'presentInLock' => null !== $packageName && isset($lockPackageNames[$packageName]),
                'presentInInstalled' => null !== $packageName && isset($installedPackageNames[$packageName]),
            ];
        }

        return $this->uniqueProjects($resolved);
    }

    private function resolvePathAgainst(string $basePath, string $path): string
    {
        $nativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if ($this->isAbsolutePath($nativePath)) {
            $candidate = $nativePath;
        } else {
            $candidate = $basePath.DIRECTORY_SEPARATOR.$nativePath;
        }

        $real = realpath($candidate);
        if (false !== $real) {
            return $real;
        }

        return $this->collapsePath($candidate);
    }

    private function isAbsolutePath(string $path): bool
    {
        return (bool) preg_match('/^[A-Za-z]:[\\\\\/]/', $path) || str_starts_with($path, DIRECTORY_SEPARATOR);
    }

    private function collapsePath(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $prefix = '';
        if ((bool) preg_match('/^[A-Za-z]:/', $path, $match)) {
            $prefix = $match[0];
            $path = substr($path, 2);
        }

        $segments = [];
        foreach (explode(DIRECTORY_SEPARATOR, $path) as $segment) {
            if ('' === $segment || '.' === $segment) {
                continue;
            }

            if ('..' === $segment) {
                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        return $prefix.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $segments);
    }

    private function workspaceRoot(string $activeRoot): string
    {
        $normalized = $this->normalizePath($activeRoot);
        $marker = '/PhpstormProjects/www';
        $position = stripos($normalized, $marker);
        if (false !== $position) {
            return str_replace('/', DIRECTORY_SEPARATOR, substr($normalized, 0, $position + strlen($marker)));
        }

        return dirname($activeRoot);
    }

    private function memoryProjectName(string $root): string
    {
        $normalized = $this->normalizePath($root);
        $normalized = preg_replace('/^[\\\\\/]+/', '', $normalized) ?? $normalized;
        $normalized = str_replace(':', '', $normalized);
        $name = preg_replace('/[^A-Za-z0-9]+/', '-', $normalized) ?? $normalized;

        return trim($name, '-');
    }

    private function dependencyFingerprint(string $activeRoot): string
    {
        $hash = hash_init('sha256');
        foreach ([
            'composer.json',
            'composer.lock',
            'vendor/composer/installed.json',
            'vendor/composer/installed.php',
        ] as $relativePath) {
            $path = $activeRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            if (!is_file($path)) {
                hash_update($hash, $relativePath.':missing;');
                continue;
            }

            hash_update($hash, $relativePath.':'.hash_file('sha256', $path).';');
        }

        return 'sha256:'.hash_final($hash);
    }

    /** @return array<string, mixed> */
    private function readJsonObject(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string, true> */
    private function readComposerLockPackageNames(string $path): array
    {
        $lock = $this->readJsonObject($path);
        $names = [];
        foreach (['packages', 'packages-dev'] as $key) {
            $packages = $lock[$key] ?? [];
            if (!is_array($packages)) {
                continue;
            }

            foreach ($packages as $package) {
                if (is_array($package) && isset($package['name']) && is_string($package['name'])) {
                    $names[$package['name']] = true;
                }
            }
        }

        return $names;
    }

    /** @return array<string, true> */
    private function readInstalledPackageNames(string $activeRoot): array
    {
        $installed = $this->readJsonObject($activeRoot.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'composer'.DIRECTORY_SEPARATOR.'installed.json');
        $packages = $installed['packages'] ?? $installed;
        $names = [];
        if (is_array($packages)) {
            foreach ($packages as $package) {
                if (is_array($package) && isset($package['name']) && is_string($package['name'])) {
                    $names[$package['name']] = true;
                }
            }
        }

        return $names;
    }

    /** @param array<string, mixed> $composer */
    private function packageName(array $composer): ?string
    {
        $name = $composer['name'] ?? null;

        return is_string($name) && '' !== $name ? $name : null;
    }

    /**
     * @param list<array<string, mixed>> $projects
     *
     * @return list<array<string, mixed>>
     */
    private function uniqueProjects(array $projects): array
    {
        $seen = [];
        $unique = [];
        foreach ($projects as $project) {
            $key = (string) ($project['project'] ?? '');
            if ('' === $key || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $project;
        }

        return $unique;
    }

    private function normalizeNativePath(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
