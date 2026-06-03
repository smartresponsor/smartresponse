<?php

declare(strict_types=1);

namespace App\Routing;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

final class SiblingRoutesLoader extends Loader
{
    /**
     * Sibling route discovery is no longer a producer route ingestion mechanism.
     *
     * Producer routes are resolved by Cruding grammar and returned as payloads for
     * Viewing/Interfacing. If this loader is re-enabled accidentally, keep it from
     * importing arbitrary producer routes into the host router.
     *
     * @var array<string, true>
     */
    private const CENTRAL_ROUTE_COMPONENTS = [
        'Cruding' => true,
        'Interfacing' => true,
        'Viewing' => true,
    ];

    /**
     * @param list<string> $connectedComponents
     */
    public function __construct(
        private readonly string $projectDir,
        private readonly array $connectedComponents,
    ) {
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'sibling_routes' === $type;
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        $collection = new RouteCollection();

        foreach ($this->connectedComponents as $component) {
            if (!isset(self::CENTRAL_ROUTE_COMPONENTS[$component])) {
                continue;
            }

            $componentDir = $this->siblingComponentDir($component);
            if (null === $componentDir || !is_dir($componentDir)) {
                continue;
            }

            $configDir = $componentDir.DIRECTORY_SEPARATOR.'config';
            $locator = new FileLocator($configDir);
            foreach ($this->discoverRouteFiles($componentDir) as $routeFile) {
                $loader = new YamlFileLoader($locator);
                $collection->addCollection($loader->load($routeFile));
            }
        }

        return $collection;
    }

    private function siblingComponentDir(string $component): ?string
    {
        $candidate = rtrim($this->projectDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.$component;
        $real = realpath($candidate);

        return false === $real ? null : $real;
    }

    /**
     * @return list<string>
     */
    private function discoverRouteFiles(string $componentDir): array
    {
        $configDir = $componentDir.DIRECTORY_SEPARATOR.'config';
        if (!is_dir($configDir)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($configDir));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            if (!str_ends_with($path, '.yaml') && !str_ends_with($path, '.yml')) {
                continue;
            }

            if (!str_contains($path, '/routes')) {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($configDir) + 1);
            if ('routes.yaml' === $relative || 'routes.yml' === $relative) {
                continue;
            }

            if ('component/routes.yaml' === str_replace('\\', '/', $relative)) {
                continue;
            }

            $contents = (string) @file_get_contents($file->getPathname());
            if ($this->isRouteImportFile($contents)) {
                continue;
            }

            $files[] = $relative;
        }

        sort($files);

        return array_values(array_unique($files));
    }

    private function isRouteImportFile(string $contents): bool
    {
        if (preg_match('/^\s*resource\s*:/m', $contents)) {
            return true;
        }

        return 1 === preg_match('/^\s*[^#\s][^:\n]*:\s*\R\s+resource\s*:/m', $contents);
    }
}
