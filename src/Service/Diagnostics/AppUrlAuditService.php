<?php

declare(strict_types=1);

namespace App\Service\Diagnostics;

use App\Administering\Entity\AdministrationConnectedComponentRecord;
use App\Cataloging\Entity\Catalog\CatalogRecordIndexEntity;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Yaml\Yaml;

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

    public function __construct(
        private KernelInterface $kernel,
        private RouterInterface $router,
        private ManagerRegistry $doctrine,
    ) {
    }

    /** @return array<string, mixed> */
    public function doctrineManagerDiagnostic(): array
    {
        $managers = [];
        foreach ($this->doctrine->getManagers() as $name => $manager) {
            $metadata = $manager->getMetadataFactory()->getAllMetadata();
            $classes = array_map(static fn ($item): string => $item->getName(), $metadata);
            sort($classes);
            $connectionParams = $manager->getConnection()->getParams();
            $managers[(string) $name] = [
                'class' => $manager::class,
                'metadataCount' => count($classes),
                'databasePath' => 'system' === (string) $name ? ($connectionParams['path'] ?? null) : null,
                'driver' => $connectionParams['driver'] ?? null,
                'administeringClasses' => array_values(array_filter(
                    $classes,
                    static fn (string $class): bool => str_starts_with($class, 'App\\Administering\\Entity\\'),
                )),
            ];
        }

        $manager = $this->doctrine->getManagerForClass(AdministrationConnectedComponentRecord::class);

        return [
            'targetClass' => AdministrationConnectedComponentRecord::class,
            'managerForClass' => null === $manager ? null : $manager::class,
            'managerNames' => $this->doctrine->getManagerNames(),
            'managers' => $managers,
        ];
    }

    /** @return array<string, mixed> */
    public function catalogRecordIndexSchemaDiagnostic(): array
    {
        $manager = $this->doctrine->getManagerForClass(CatalogRecordIndexEntity::class);
        if (null === $manager) {
            return [
                'entity' => CatalogRecordIndexEntity::class,
                'valid' => false,
                'reason' => 'entity_manager_missing',
            ];
        }

        $metadata = $manager->getClassMetadata(CatalogRecordIndexEntity::class);
        $tableName = $metadata->getTableName();
        $entityColumns = $metadata->getColumnNames();
        sort($entityColumns);
        $entityIndexes = array_keys((array) ($metadata->table['indexes'] ?? []));
        sort($entityIndexes);

        $schemaManager = $manager->getConnection()->createSchemaManager();
        if (!$schemaManager->tablesExist([$tableName])) {
            return [
                'entity' => CatalogRecordIndexEntity::class,
                'table' => $tableName,
                'valid' => false,
                'reason' => 'table_missing',
                'entityColumns' => $entityColumns,
                'entityIndexes' => $entityIndexes,
            ];
        }

        $databaseColumns = array_keys($schemaManager->listTableColumns($tableName));
        sort($databaseColumns);
        $databaseIndexes = array_keys($schemaManager->listTableIndexes($tableName));
        sort($databaseIndexes);
        $missingColumns = array_values(array_diff($entityColumns, $databaseColumns));
        $unexpectedColumns = array_values(array_diff($databaseColumns, $entityColumns));
        $missingIndexes = array_values(array_diff($entityIndexes, $databaseIndexes));

        return [
            'entity' => CatalogRecordIndexEntity::class,
            'table' => $tableName,
            'valid' => [] === $missingColumns && [] === $unexpectedColumns && [] === $missingIndexes,
            'entityColumns' => $entityColumns,
            'databaseColumns' => $databaseColumns,
            'entityIndexes' => $entityIndexes,
            'databaseIndexes' => $databaseIndexes,
            'missingColumns' => $missingColumns,
            'unexpectedColumns' => $unexpectedColumns,
            'missingIndexes' => $missingIndexes,
        ];
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
    public function probePath(string $path, int $samples = 1, int $slowThresholdMs = 250, ?string $warmupPath = null): array
    {
        if (!str_starts_with($path, '/')) {
            $path = '/'.$path;
        }
        $checkpoint = tempnam(sys_get_temp_dir(), 'app-url-audit-');
        if (false === $checkpoint) {
            throw new \RuntimeException('Unable to create targeted URL audit checkpoint.');
        }

        try {
            $route = [[
                'name' => 'targeted',
                'path' => $path,
                'materializedPath' => $path,
                'methods' => ['GET'],
                'controller' => null,
                'source' => 'targeted',
            ]];
            $warmupProbe = null;
            if (null !== $warmupPath && '' !== trim($warmupPath)) {
                $warmupPath = str_starts_with($warmupPath, '/') ? $warmupPath : '/'.$warmupPath;
                $warmupRoute = [[
                    'name' => 'warmup',
                    'path' => $warmupPath,
                    'materializedPath' => $warmupPath,
                    'methods' => ['GET'],
                    'controller' => null,
                    'source' => 'targeted-warmup',
                ]];
                $warmupProbe = $this->probeKernelMany($warmupRoute, $checkpoint.'.warmup')[0] ?? null;
            }

            $sampleSets = [];
            for ($sample = 0; $sample < max(1, $samples); ++$sample) {
                $sampleSets[] = $this->probeKernelMany($route, $checkpoint.'.'.$sample);
            }
            $profile = $this->performanceProfile($sampleSets, $slowThresholdMs);

            return [
                'warmupProbe' => $warmupProbe,
                'probe' => $sampleSets[0][0],
                'performance' => $profile['routes'][0] ?? [],
            ];
        } finally {
            @unlink($checkpoint);
            foreach (glob($checkpoint.'.*') ?: [] as $sampleCheckpoint) {
                @unlink($sampleCheckpoint);
            }
        }
    }

    /** @return array<string, mixed> */
    public function run(string $baseUrl, int $timeout, int $samples = 1, int $slowThresholdMs = 250): array
    {
        $inventory = $this->inventory();
        $runId = gmdate('Ymd-His').'-'.substr(hash('sha256', random_bytes(16)), 0, 8);
        $directory = $this->kernel->getProjectDir().'/var/url-audit/'.$runId;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create URL audit run directory.');
        }

        $candidates = $this->candidates($inventory);
        $checkpoint = $directory.'/probes.jsonl';
        $sampleSets = [];
        for ($sample = 0; $sample < max(1, $samples); ++$sample) {
            $sampleCheckpoint = 0 === $sample ? $checkpoint : $directory.'/probes.sample-'.$sample.'.jsonl';
            $sampleSets[] = $this->probeKernelMany($candidates, $sampleCheckpoint);
        }
        $probes = $sampleSets[0];
        $performance = $this->performanceProfile($sampleSets, $slowThresholdMs);
        $failures = [];
        foreach ($probes as $probe) {
            foreach ($probe['findings'] as $finding) {
                $source = strtolower($finding.'|'.$probe['bodyPreview']);
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
                    'affectedPaths' => [],
                ];
                ++$failures[$fingerprint]['occurrences'];
                $failures[$fingerprint]['affectedPaths'][$probe['path']] = $probe['path'];
                $failures[$fingerprint]['affectedPaths'] = array_values($failures[$fingerprint]['affectedPaths']);
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
                'performanceSamples' => max(1, $samples),
                'slowThresholdMs' => $slowThresholdMs,
                'slowRoutes' => $performance['summary']['slowRoutes'],
                'heavyPayloadRoutes' => $performance['summary']['heavyPayloadRoutes'],
                'firstTouchRoutes' => $performance['summary']['firstTouchRoutes'],
                'negativeProbes' => $performance['summary']['negativeProbes'],
                'p50Ms' => $performance['summary']['p50Ms'],
                'p95Ms' => $performance['summary']['p95Ms'],
                'maxMs' => $performance['summary']['maxMs'],
            ],
            'failures' => array_values($failures),
            'performance' => $performance,
            'probes' => $probes,
        ];
        $this->writeJson($directory.'/inventory.json', $inventory);
        $this->writeJson($directory.'/report.json', $report);
        $this->writeJson($directory.'/failures.json', array_values($failures));

        return $report;
    }

    /**
     * @param list<list<array<string, mixed>>> $sampleSets
     *
     * @return array<string, mixed>
     */
    private function performanceProfile(array $sampleSets, int $slowThresholdMs): array
    {
        $routes = [];
        foreach ($sampleSets as $sampleIndex => $sampleSet) {
            foreach ($sampleSet as $probe) {
                $key = (string) $probe['route'].'|'.(string) $probe['path'];
                $routes[$key] ??= [
                    'route' => $probe['route'],
                    'path' => $probe['path'],
                    'source' => $probe['source'] ?? 'symfony',
                    'status' => $probe['status'],
                    'contentType' => $probe['contentType'],
                    'responseBytes' => (int) ($probe['responseBytes'] ?? 0),
                    'samplesMs' => [],
                    'stageSamples' => [],
                ];
                $routes[$key]['samplesMs'][$sampleIndex] = (int) $probe['durationMs'];
                $stageTimings = is_array($probe['stageTimings'] ?? null) ? $probe['stageTimings'] : [];
                foreach ($stageTimings as $stage => $duration) {
                    if (!is_string($stage) || !is_numeric($duration)) {
                        continue;
                    }
                    $routes[$key]['stageSamples'][$stage][$sampleIndex] = (float) $duration;
                }
            }
        }

        $allWarm = [];
        foreach ($routes as &$route) {
            ksort($route['samplesMs']);
            $samples = array_values($route['samplesMs']);
            $warm = count($samples) > 1 ? array_slice($samples, 1) : $samples;
            sort($warm);
            $coldMs = $samples[0] ?? 0;
            $warmAvgMs = [] === $warm ? $coldMs : (int) round(array_sum($warm) / count($warm));
            $p50Ms = $this->percentile($warm, 0.50);
            $p95Ms = $this->percentile($warm, 0.95);
            $maxMs = [] === $warm ? $coldMs : max($warm);
            $minMs = [] === $warm ? $coldMs : min($warm);
            $spreadMs = $maxMs - $minMs;
            $stageProfile = [];
            foreach ($route['stageSamples'] as $stage => $stageSamples) {
                ksort($stageSamples);
                $orderedStageSamples = array_values($stageSamples);
                $warmStageSamples = count($orderedStageSamples) > 1 ? array_slice($orderedStageSamples, 1) : $orderedStageSamples;
                sort($warmStageSamples);
                $stageProfile[$stage] = [
                    'samplesMs' => array_map(static fn (float $value): float => round($value, 2), $orderedStageSamples),
                    'coldMs' => round($orderedStageSamples[0] ?? 0.0, 2),
                    'warmAvgMs' => round([] === $warmStageSamples ? ($orderedStageSamples[0] ?? 0.0) : array_sum($warmStageSamples) / count($warmStageSamples), 2),
                    'warmP50Ms' => round($this->floatPercentile($warmStageSamples, 0.50), 2),
                    'warmP95Ms' => round($this->floatPercentile($warmStageSamples, 0.95), 2),
                ];
            }
            $classification = 'healthy';
            $investigate = 'No performance action indicated.';
            if ((int) $route['status'] >= 400) {
                $classification = 'negative_probe';
                $investigate = 'Expected negative-path timing; exclude from actionable route optimization.';
            } elseif ((int) $route['status'] >= 300) {
                $classification = 'redirect_response';
                $investigate = 'Redirect timing is not actionable route-rendering latency; inspect only when the redirect is reported as unexpected.';
            } elseif ((int) $route['responseBytes'] >= 262144) {
                $classification = 'payload_heavy';
                $investigate = 'Inspect pagination, projection size, duplicated shell locations, and embedded diagnostics.';
            } elseif ($p50Ms >= $slowThresholdMs) {
                $classification = 'sustained_slow';
                $investigate = str_contains((string) $route['contentType'], 'html')
                    ? 'Inspect controller queries, Viewing composition, Twig rendering, and response payload size.'
                    : 'Inspect controller queries, external I/O, serialization, and payload size.';
            } elseif ($coldMs >= max($slowThresholdMs * 2, $warmAvgMs * 3)) {
                $classification = 'first_touch';
                $investigate = 'First-touch effect inside the shared sweep process; verify with an isolated sequence probe before optimizing.';
            } elseif (count($warm) >= 4 && $p95Ms >= $slowThresholdMs && $spreadMs >= max(100, $p50Ms)) {
                $classification = 'unstable';
                $investigate = 'Inspect nondeterministic database work, external I/O, locks, cache misses, and oversized result sets.';
            }

            $route += [
                'coldMs' => $coldMs,
                'warmAvgMs' => $warmAvgMs,
                'p50Ms' => $p50Ms,
                'p95Ms' => $p95Ms,
                'minMs' => $minMs,
                'maxMs' => $maxMs,
                'spreadMs' => $spreadMs,
                'stageProfile' => $stageProfile,
                'classification' => $classification,
                'investigate' => $investigate,
            ];
            foreach ($warm as $duration) {
                $allWarm[] = $duration;
            }
        }
        unset($route);

        usort($routes, static fn (array $left, array $right): int => $right['warmAvgMs'] <=> $left['warmAvgMs']);
        sort($allWarm);

        return [
            'summary' => [
                'routes' => count($routes),
                'samplesPerRoute' => count($sampleSets),
                'slowThresholdMs' => $slowThresholdMs,
                'slowRoutes' => count(array_filter($routes, static fn (array $route): bool => in_array($route['classification'], ['sustained_slow', 'unstable'], true))),
                'heavyPayloadRoutes' => count(array_filter($routes, static fn (array $route): bool => 'payload_heavy' === $route['classification'])),
                'firstTouchRoutes' => count(array_filter($routes, static fn (array $route): bool => 'first_touch' === $route['classification'])),
                'negativeProbes' => count(array_filter($routes, static fn (array $route): bool => 'negative_probe' === $route['classification'])),
                'p50Ms' => $this->percentile($allWarm, 0.50),
                'p95Ms' => $this->percentile($allWarm, 0.95),
                'maxMs' => [] === $allWarm ? 0 : max($allWarm),
            ],
            'routes' => array_values($routes),
        ];
    }

    /** @param list<int> $values */
    private function percentile(array $values, float $percentile): int
    {
        if ([] === $values) {
            return 0;
        }
        sort($values);
        $index = (int) ceil($percentile * count($values)) - 1;

        return $values[max(0, min($index, count($values) - 1))];
    }

    /** @param list<float> $values */
    private function floatPercentile(array $values, float $percentile): float
    {
        if ([] === $values) {
            return 0.0;
        }
        sort($values);
        $index = (int) ceil($percentile * count($values)) - 1;

        return (float) $values[max(0, min($index, count($values) - 1))];
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

    /** @param array<string, mixed> $inventory @return list<array<string, mixed>> */
    private function candidates(array $inventory): array
    {
        $candidates = [];
        foreach ((array) ($inventory['routes'] ?? []) as $route) {
            if (!is_array($route) || !in_array('GET', (array) ($route['methods'] ?? []), true)) {
                continue;
            }
            $key = (string) $route['materializedPath'].'|'.(string) $route['name'];
            $candidates[$key] = $route + ['source' => 'symfony'];
        }

        foreach ($this->declaredCrudingAliases() as $entity) {
            foreach ([
                '/'.$entity,
                '/'.$entity.'/index',
                '/'.$entity.'/new',
                '/my/'.$entity.'/index',
                '/api/'.$entity,
                '/my/api/'.$entity,
            ] as $path) {
                $name = 'generated_cruding_'.str_replace(['/', '-'], '_', trim($path, '/'));
                $candidates[$path.'|'.$name] = [
                    'name' => $name,
                    'path' => $path,
                    'materializedPath' => $path,
                    'methods' => ['GET'],
                    'controller' => null,
                    'source' => 'cruding-runtime-entity',
                ];
            }
        }

        ksort($candidates);

        return array_values($candidates);
    }

    /** @return list<string> */
    private function declaredCrudingAliases(): array
    {
        $configFile = $this->kernel->getProjectDir().'/config/packages/cruding.yaml';
        if (!is_file($configFile)) {
            return [];
        }

        $config = Yaml::parseFile($configFile);
        $aliases = is_array($config) ? ($config['cruding']['entity_class_alias_map'] ?? []) : [];
        if (!is_array($aliases)) {
            return [];
        }

        $declared = [];
        foreach ($aliases as $alias => $entityClass) {
            if (!is_string($alias) || '' === $alias || !is_string($entityClass) || !class_exists($entityClass)) {
                continue;
            }
            if (null === $this->doctrine->getManagerForClass($entityClass)) {
                continue;
            }
            $declared[] = $alias;
        }
        sort($declared);

        return $declared;
    }

    /**
     * @param list<array<string, mixed>> $routes
     *
     * @return list<array<string, mixed>>
     */
    private function probeKernelMany(array $routes, string $checkpoint): array
    {
        $probes = [];
        $append = fopen($checkpoint, 'w');
        if (false === $append) {
            throw new \RuntimeException('Unable to open URL audit checkpoint.');
        }

        try {
            foreach ($routes as $route) {
                $path = (string) $route['materializedPath'];
                $request = Request::create($path, 'GET', [], [], [], [
                    'HTTP_ACCEPT' => str_starts_with($path, '/api/') || str_starts_with($path, '/my/api/') ? 'application/json' : 'text/html,application/json;q=0.8',
                    'HTTP_USER_AGENT' => 'SmartResponsor-Platform-URL-Audit/3.0',
                ]);
                $started = microtime(true);
                try {
                    $response = $this->kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, true);
                    $status = $response->getStatusCode();
                    $contentType = strtolower((string) $response->headers->get('content-type', ''));
                    $location = (string) $response->headers->get('location', '');
                    $body = (string) $response->getContent();
                    $this->kernel->terminate($request, $response);
                    $error = '';
                } catch (\Throwable $exception) {
                    $status = 500;
                    $contentType = 'text/plain';
                    $location = '';
                    $body = $exception::class.' '.$exception->getMessage()."\n".$exception->getTraceAsString();
                    $error = $exception::class.': '.$exception->getMessage();
                }

                $findings = [];
                if ($status >= 500) {
                    $findings[] = 'http_'.$status;
                } elseif ($status >= 300 && $status < 400 && !$this->isExpectedRedirect($path, $location)) {
                    $findings[] = 'redirect_'.$status;
                }
                if (str_contains($contentType, 'json')) {
                    json_decode($body, true);
                    if (JSON_ERROR_NONE !== json_last_error()) {
                        $findings[] = 'malformed_json';
                    }
                }
                $viewLoaderFailures = $request->attributes->get('_view_loader_failures', []);
                $viewRenderFailures = $request->attributes->get('_view_render_failures', []);
                if (is_array($viewLoaderFailures) && [] !== $viewLoaderFailures) {
                    $findings[] = 'view_template_loader_failure';
                }
                if (is_array($viewRenderFailures) && [] !== $viewRenderFailures) {
                    $findings[] = 'view_template_render_failure';
                }
                if (str_contains($contentType, 'html')) {
                    $lower = strtolower($body);
                    foreach (self::BODY_MARKERS as $marker) {
                        if (str_contains($lower, $marker)) {
                            $findings[] = 'body_marker:'.$marker;
                        }
                    }
                }

                $probe = [
                    'route' => $route['name'],
                    'source' => $route['source'] ?? 'symfony',
                    'path' => $path,
                    'url' => $path,
                    'transport' => 'kernel_main_request',
                    'status' => $status,
                    'contentType' => $contentType,
                    'location' => $location,
                    'stageTimings' => [
                        'interfacingRenderMs' => $response->headers->get('X-Interfacing-Render-ms'),
                        'interfacingShellMs' => $response->headers->get('X-Interfacing-Shell-ms'),
                        'interfacingSlotsMs' => $response->headers->get('X-Interfacing-Slots-ms'),
                        'interfacingTwigMs' => $response->headers->get('X-Interfacing-Twig-ms'),
                        'viewingResolveMs' => $response->headers->get('X-Viewing-Resolve-ms'),
                        'viewingComposeMs' => $response->headers->get('X-Viewing-Compose-ms'),
                        'viewingContextMs' => $response->headers->get('X-Viewing-Context-ms'),
                        'viewingTwigMs' => $response->headers->get('X-Viewing-Twig-ms'),
                        'crudContractMs' => $response->headers->get('X-App-Crud-Contract-ms'),
                        'crudNavigationMs' => $response->headers->get('X-App-Crud-Navigation-ms'),
                        'crudContextMs' => $response->headers->get('X-Crud-Context-ms'),
                        'crudEntrypointMs' => $response->headers->get('X-Crud-Entrypoint-ms'),
                        'crudDefinitionMs' => $response->headers->get('X-Crud-Definition-ms'),
                        'crudContractFactoryMs' => $response->headers->get('X-Crud-Contract-Factory-ms'),
                        'crudServiceResolutionMs' => $response->headers->get('X-Crud-Service-Resolution-ms'),
                        'crudServiceInvocationMs' => $response->headers->get('X-Crud-Service-Invocation-ms'),
                        'crudServiceClass' => $response->headers->get('X-Crud-Service-Class'),
                        'crudDefaultIndexDefinitionMs' => $response->headers->get('X-Crud-Default-Index-Definition-ms'),
                        'crudDefaultIndexContractMs' => $response->headers->get('X-Crud-Default-Index-Contract-ms'),
                        'crudObjectFindAllMs' => $response->headers->get('X-Crud-Object-Find-All-ms'),
                    ],
                    'viewLoaderFailures' => $request->attributes->get('_view_loader_failures', []),
                    'viewRenderFailures' => $request->attributes->get('_view_render_failures', []),
                    'durationMs' => (int) round((microtime(true) - $started) * 1000),
                    'responseBytes' => strlen($body),
                    'error' => $error,
                    'bodyHash' => hash('sha256', $body),
                    'bodyPreview' => mb_substr(trim(strip_tags($body)), 0, 500),
                    'findings' => array_values(array_unique($findings)),
                ];
                $probes[] = $probe;
                fwrite($append, json_encode($probe, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);
                fflush($append);
            }
        } finally {
            fclose($append);
        }

        return $probes;
    }

    /**
     * @param list<array<string, mixed>> $routes
     *
     * @return list<array<string, mixed>>
     */
    private function probeMany(string $baseUrl, array $routes, int $timeout, int $concurrency, string $checkpoint): array
    {
        $completed = [];
        if (is_file($checkpoint)) {
            foreach (file($checkpoint, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $probe = json_decode($line, true);
                if (is_array($probe) && is_string($probe['path'] ?? null)) {
                    $completed[$probe['path'].'|'.($probe['route'] ?? '')] = $probe;
                }
            }
        }

        $queue = [];
        foreach ($routes as $route) {
            $key = (string) $route['materializedPath'].'|'.(string) $route['name'];
            if (!isset($completed[$key])) {
                $queue[] = $route;
            }
        }

        $multi = curl_multi_init();
        $active = [];
        $append = fopen($checkpoint, 'a');
        if (false === $append) {
            throw new \RuntimeException('Unable to open URL audit checkpoint.');
        }

        try {
            do {
                while (count($active) < max(1, $concurrency) && [] !== $queue) {
                    $route = array_shift($queue);
                    $url = $baseUrl.(string) $route['materializedPath'];
                    $curl = curl_init($url);
                    curl_setopt_array($curl, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HEADER => true,
                        CURLOPT_FOLLOWLOCATION => false,
                        CURLOPT_CONNECTTIMEOUT => min(3, max(1, $timeout)),
                        CURLOPT_TIMEOUT => max(1, $timeout),
                        CURLOPT_HTTPHEADER => [
                            'Accept: '.(str_starts_with((string) $route['materializedPath'], '/api/') || str_starts_with((string) $route['materializedPath'], '/my/api/') ? 'application/json' : 'text/html,application/json;q=0.8'),
                            'User-Agent: SmartResponsor-Platform-URL-Audit/2.0',
                        ],
                    ]);
                    curl_multi_add_handle($multi, $curl);
                    $active[(int) $curl] = ['handle' => $curl, 'route' => $route, 'url' => $url];
                }

                do {
                    $status = curl_multi_exec($multi, $running);
                } while (CURLM_CALL_MULTI_PERFORM === $status);

                while (false !== ($info = curl_multi_info_read($multi))) {
                    $curl = $info['handle'];
                    $entry = $active[(int) $curl];
                    $probe = $this->finalizeProbe($curl, $entry['url'], $entry['route']);
                    $key = $probe['path'].'|'.$probe['route'];
                    $completed[$key] = $probe;
                    fwrite($append, json_encode($probe, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);
                    fflush($append);
                    curl_multi_remove_handle($multi, $curl);
                    curl_close($curl);
                    unset($active[(int) $curl]);
                }

                if ($running > 0) {
                    curl_multi_select($multi, 0.25);
                }
            } while ([] !== $queue || [] !== $active);
        } finally {
            fclose($append);
            foreach ($active as $entry) {
                curl_multi_remove_handle($multi, $entry['handle']);
                curl_close($entry['handle']);
            }
            curl_multi_close($multi);
        }

        return array_values($completed);
    }

    /** @param array<string, mixed> $route @return array<string, mixed> */
    private function finalizeProbe(\CurlHandle $curl, string $url, array $route): array
    {
        $raw = curl_multi_getcontent($curl);
        $error = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $contentType = strtolower((string) curl_getinfo($curl, CURLINFO_CONTENT_TYPE));
        $durationMs = (int) round(((float) curl_getinfo($curl, CURLINFO_TOTAL_TIME)) * 1000);
        $body = substr(is_string($raw) ? $raw : '', $headerSize);
        $findings = [];
        if ('' !== $error) {
            $findings[] = str_contains(strtolower($error), 'timed out') ? 'timeout' : 'transport_error';
        }
        if (0 === $status || $status >= 500) {
            $findings[] = 'http_'.$status;
        } elseif (404 === $status && 'cruding-runtime-entity' === ($route['source'] ?? null)) {
            $findings[] = 'declared_component_route_404';
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

        return [
            'route' => $route['name'],
            'source' => $route['source'] ?? 'symfony',
            'path' => $route['materializedPath'],
            'url' => $url,
            'status' => $status,
            'contentType' => $contentType,
            'durationMs' => $durationMs,
            'error' => $error,
            'bodyHash' => hash('sha256', $body),
            'bodyPreview' => mb_substr(trim(strip_tags($body)), 0, 500),
            'findings' => array_values(array_unique($findings)),
        ];
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

    private function isExpectedRedirect(string $requestPath, string $location): bool
    {
        $targetPath = (string) parse_url($location, PHP_URL_PATH);
        $expectedTargets = [
            '/access/signup' => '/access/register',
            '/access/reset-password/reset' => '/access/reset-password/request',
        ];
        if (isset($expectedTargets[$requestPath]) && $expectedTargets[$requestPath] === $targetPath) {
            return true;
        }
        if ('/access/signin' === $targetPath) {
            return true;
        }

        return '' !== $targetPath && rtrim($targetPath, '/') === rtrim($requestPath, '/');
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
