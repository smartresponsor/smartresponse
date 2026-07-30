<?php

declare(strict_types=1);

namespace App\Service\Diagnostics;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

final readonly class AppUrlAuditService
{
    private const BODY_MARKERS = [
        'fatal error',
        'uncaught ',
        'stack trace',
        'template_missing_json_fallback',
        'data-interfacing-root-fallback',
        'interfacing fallback',
        'was not found in the chain configured namespaces',
    ];

    public function __construct(private KernelInterface $kernel, private RouterInterface $router)
    {
    }

    /** @return array<string, mixed> */
    public function inventory(): array
    {
        $root = $this->kernel->getProjectDir();
        $composer = $this->readJson($root.'/composer.json');
        $lock = $this->readJson($root.'/composer.lock');
        $installed = $this->readJson($root.'/vendor/composer/installed.json');
        $repositories = [];

        foreach (($composer['repositories'] ?? []) as $repository) {
            if (!is_array($repository) || 'path' !== ($repository['type'] ?? null)) {
                continue;
            }
            $configured = (string) ($repository['url'] ?? '');
            $resolved = realpath($root.'/'.$configured);
            $repositories[] = [
                'configured' => $configured,
                'resolved' => false === $resolved ? null : str_replace('\\', '/', $resolved),
                'exists' => false !== $resolved,
                'symlinkRequested' => (bool) ($repository['options']['symlink'] ?? false),
            ];
        }

        $routes = [];
        foreach ($this->router->getRouteCollection()->all() as $name => $route) {
            $routes[] = $this->route((string) $name, $route);
        }

        return [
            'schema' => 'app.url-audit.inventory.v1',
            'generatedAt' => gmdate(DATE_ATOM),
            'projectDir' => $root,
            'composer' => [
                'pathRepositories' => $repositories,
                'requiredPackages' => array_keys((array) ($composer['require'] ?? [])),
                'lockedPackageCount' => count((array) ($lock['packages'] ?? [])),
                'installedPackageCount' => count((array) ($installed['packages'] ?? $installed)),
            ],
            'runtime' => [
                'environment' => $this->kernel->getEnvironment(),
                'loadedBundles' => array_keys($this->kernel->getBundles()),
                'developmentLock' => $this->readPhp($root.'/config/kernel/runtime_scope.lock.php'),
                'productionLock' => $this->readPhp($root.'/config/kernel/runtime_scope.prod.lock.php'),
            ],
            'routes' => $routes,
            'routeCount' => count($routes),
        ];
    }

    /** @return array<string, mixed> */
    public function run(string $baseUrl, int $timeout): array
    {
        $inventory = $this->inventory();
        $runId = gmdate('Ymd-His').'-'.substr(hash('sha256', random_bytes(16)), 0, 8);
        $directory = $this->kernel->getProjectDir().'/var/url-audit/'.$runId;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create URL audit run directory.');
        }

        $probes = [];
        $failures = [];
        foreach ($inventory['routes'] as $route) {
            if (!is_array($route) || !in_array('GET', $route['methods'], true)) {
                continue;
            }
            $probe = $this->probe(rtrim($baseUrl, '/').$route['materializedPath'], $route, $timeout);
            $probes[] = $probe;
            foreach ($probe['findings'] as $finding) {
                $source = strtolower($finding.'|'.$probe['path'].'|'.$probe['bodyPreview']);
                $source = (string) preg_replace('/\b\d+\b/', '{n}', $source);
                $fingerprint = hash('sha256', $source);
                $failures[$fingerprint] ??= [
                    'fingerprint' => $fingerprint,
                    'type' => $finding,
                    'route' => $probe['route'],
                    'path' => $probe['path'],
                    'status' => $probe['status'],
                    'evidence' => $probe['bodyPreview'],
                    'occurrences' => 0,
                ];
                ++$failures[$fingerprint]['occurrences'];
            }
        }

        $report = [
            'schema' => 'app.url-audit.run.v1',
            'runId' => $runId,
            'runDirectory' => $directory,
            'summary' => [
                'inventoryRoutes' => $inventory['routeCount'],
                'probes' => count($probes),
                'passedProbes' => count(array_filter($probes, static fn (array $probe): bool => [] === $probe['findings'])),
                'rootCauses' => count($failures),
            ],
            'failures' => array_values($failures),
            'probes' => $probes,
        ];
        $this->writeJson($directory.'/inventory.json', $inventory);
        $this->writeJson($directory.'/report.json', $report);
        $this->writeJson($directory.'/failures.json', array_values($failures));

        return $report;
    }

    /** @return array<string, mixed> */
    private function route(string $name, Route $route): array
    {
        $methods = [] === $route->getMethods() ? ['GET'] : $route->getMethods();
        $path = (string) preg_replace_callback('/\{([^}]+)\}/', static function (array $match) use ($route): string {
            $token = $match[1];
            $default = $route->getDefault($token);
            if (is_scalar($default) && '' !== (string) $default) {
                return rawurlencode((string) $default);
            }
            $token = strtolower($token);

            return str_contains($token, 'uuid') ? '00000000-0000-0000-0000-000000000001' : (str_contains($token, 'id') ? '1' : 'audit-sample');
        }, $route->getPath());

        return ['name' => $name, 'path' => $route->getPath(), 'materializedPath' => $path, 'methods' => $methods, 'controller' => $route->getDefault('_controller')];
    }

    /** @param array<string, mixed> $route @return array<string, mixed> */
    private function probe(string $url, array $route, int $timeout): array
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => $timeout, CURLOPT_HTTPHEADER => ['Accept: '.(str_starts_with($route['materializedPath'], '/api/') ? 'application/json' : 'text/html,application/json;q=0.8'), 'User-Agent: SmartResponsor-Platform-URL-Audit/1.0']]);
        $raw = curl_exec($curl);
        $error = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $contentType = strtolower((string) curl_getinfo($curl, CURLINFO_CONTENT_TYPE));
        curl_close($curl);
        $body = substr(is_string($raw) ? $raw : '', $headerSize);
        $findings = [];
        if ('' !== $error) {
            $findings[] = str_contains(strtolower($error), 'timed out') ? 'timeout' : 'transport_error';
        }
        if (0 === $status || $status >= 500) {
            $findings[] = 'http_'.$status;
        } elseif ($status >= 300 && $status < 400) {
            $findings[] = 'redirect_'.$status;
        }
        if (str_contains($contentType, 'json')) {
            json_decode($body, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                $findings[] = 'malformed_json';
            }
        }
        $lower = strtolower($body);
        foreach (self::BODY_MARKERS as $marker) {
            if (str_contains($lower, $marker)) {
                $findings[] = 'body_marker:'.$marker;
            }
        }

        return ['route' => $route['name'], 'path' => $route['materializedPath'], 'url' => $url, 'status' => $status, 'contentType' => $contentType, 'error' => $error, 'bodyHash' => hash('sha256', $body), 'bodyPreview' => mb_substr(trim(strip_tags($body)), 0, 500), 'findings' => array_values(array_unique($findings))];
    }

    /** @return array<string, mixed> */
    private function readJson(string $file): array
    {
        $decoded = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];

        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string, mixed> */
    private function readPhp(string $file): array
    {
        $value = is_file($file) ? require $file : [];

        return is_array($value) ? $value : [];
    }

    /** @param array<string, mixed>|list<mixed> $value */
    private function writeJson(string $file, array $value): void
    {
        file_put_contents($file, json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);
    }
}
