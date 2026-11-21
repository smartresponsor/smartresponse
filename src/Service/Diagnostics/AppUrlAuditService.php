<?php

declare(strict_types=1);

namespace App\Service\Diagnostics;

use App\Administering\Entity\AdministrationConnectedComponentRecordEntity;
use App\Cataloging\Entity\Catalog\CatalogRecordIndexEntity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
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
            $connectionParams = $manager instanceof EntityManagerInterface ? $manager->getConnection()->getParams() : [];
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

        $manager = $this->doctrine->getManagerForClass(AdministrationConnectedComponentRecordEntity::class);

        return [
            'targetClass' => AdministrationConnectedComponentRecordEntity::class,
            'managerForClass' => null === $manager ? null : $manager::class,
            'managerNames' => $this->doctrine->getManagerNames(),
            'managers' => $managers,
        ];
    }

    /** @return array<string, mixed> */
    public function catalogRecordIndexSchemaDiagnostic(): array
    {
        $manager = $this->doctrine->getManagerForClass(CatalogRecordIndexEntity::class);
        if (!$manager instanceof EntityManagerInterface) {
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

        foreach (is_array($composer['repositories'] ?? null) ? $composer['repositories'] : [] as $repository) {
            if (!is_array($repository) || 'path' !== ($repository['type'] ?? null)) {
                continue;
            }
            $configured = is_string($repository['url'] ?? null) ? $repository['url'] : '';
            $resolved = realpath($root.'/'.$configured);
            $repositories[] = [
                'configured' => $configured,
                'resolved' => false === $resolved ? null : str_replace('\\', '/', $resolved),
                'exists' => false !== $resolved,
                'symlinkRequested' => is_array($repository['options'] ?? null) && true === ($repository['options']['symlink'] ?? false),
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
            /** @var list<array<string, mixed>> $firstSample */
            $firstSample = $sampleSets[0];

            return [
                'warmupProbe' => $warmupProbe,
                'probe' => $firstSample[0] ?? null,
                'performance' => is_array($profile['routes'] ?? null) && is_array($profile['routes'][0] ?? null) ? $profile['routes'][0] : [],
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
        /** @var list<array<string, mixed>> $probes */
        $probes = $sampleSets[0];
        $performance = $this->performanceProfile($sampleSets, $slowThresholdMs);
        /** @var array<string, mixed> $performanceSummary */
        $performanceSummary = is_array($performance['summary'] ?? null) ? $performance['summary'] : [];
        $failures = [];
        foreach ($probes as $probe) {
            foreach (is_array($probe['findings'] ?? null) ? $probe['findings'] : [] as $finding) {
                if (!is_string($finding)) {
                    continue;
                }
                $preview = is_string($probe['bodyPreview'] ?? null) ? $probe['bodyPreview'] : '';
                $source = strtolower($finding.'|'.$preview);
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
                $path = is_string($probe['path'] ?? null) ? $probe['path'] : '';
                $failures[$fingerprint]['affectedPaths'][$path] = $path;
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
                'slowRoutes' => $performanceSummary['slowRoutes'],
                'heavyPayloadRoutes' => $performanceSummary['heavyPayloadRoutes'],
                'firstTouchRoutes' => $performanceSummary['firstTouchRoutes'],
                'negativeProbes' => $performanceSummary['negativeProbes'],
                'p50Ms' => $performanceSummary['p50Ms'],
                'p95Ms' => $performanceSummary['p95Ms'],
                'maxMs' => $performanceSummary['maxMs'],
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
        $routes = $this->performanceRouteSamples($sampleSets);
        $allWarm = [];

        foreach ($routes as $key => $route) {
            [$routes[$key], $warm] = $this->finalizePerformanceRoute($route, $slowThresholdMs);
            array_push($allWarm, ...$warm);
        }

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
            'routes' => $routes,
        ];
    }

    /**
     * @param list<list<array<string, mixed>>> $sampleSets
     *
     * @return array<string, array<string, mixed>>
     */
    private function performanceRouteSamples(array $sampleSets): array
    {
        $routes = [];
        foreach ($sampleSets as $sampleIndex => $sampleSet) {
            foreach ($sampleSet as $probe) {
                $routeName = $this->stringValue($probe['route'] ?? null);
                $path = $this->stringValue($probe['path'] ?? null);
                $key = $routeName.'|'.$path;
                $routes[$key] ??= [
                    'route' => $routeName,
                    'path' => $path,
                    'source' => $this->stringValue($probe['source'] ?? null, 'symfony'),
                    'status' => $this->integerValue($probe['status'] ?? null),
                    'contentType' => $this->stringValue($probe['contentType'] ?? null),
                    'responseBytes' => $this->integerValue($probe['responseBytes'] ?? null),
                    'samplesMs' => [],
                    'stageSamples' => [],
                ];
                $routes[$key]['samplesMs'][$sampleIndex] = $this->integerValue($probe['durationMs'] ?? null);
                $this->appendStageSamples($routes[$key]['stageSamples'], $probe['stageTimings'] ?? null, $sampleIndex);
            }
        }

        return $routes;
    }

    /** @param array<string, array<int, float>> $stageSamples */
    private function appendStageSamples(array &$stageSamples, mixed $timings, int $sampleIndex): void
    {
        if (!is_array($timings)) {
            return;
        }

        foreach ($timings as $stage => $duration) {
            if (!is_string($stage) || !is_numeric($duration)) {
                continue;
            }

            $stageSamples[$stage][$sampleIndex] = (float) $duration;
        }
    }

    /**
     * @param array<string, mixed> $route
     *
     * @return array{0: array<string, mixed>, 1: list<int>}
     */
    private function finalizePerformanceRoute(array $route, int $slowThresholdMs): array
    {
        $rawSamples = $route['samplesMs'] ?? [];
        $samples = [];
        if (is_array($rawSamples)) {
            ksort($rawSamples);
            foreach ($rawSamples as $sample) {
                $samples[] = $this->integerValue($sample);
            }
        }
        $warm = count($samples) > 1 ? array_slice($samples, 1) : $samples;
        sort($warm);

        $coldMs = $samples[0] ?? 0;
        $warmAvgMs = [] === $warm ? $coldMs : (int) round(array_sum($warm) / count($warm));
        $p50Ms = $this->percentile($warm, 0.50);
        $p95Ms = $this->percentile($warm, 0.95);
        $maxMs = [] === $warm ? $coldMs : max($warm);
        $minMs = [] === $warm ? $coldMs : min($warm);
        $spreadMs = $maxMs - $minMs;
        [$classification, $investigate] = $this->performanceClassification(
            $route,
            $warm,
            $coldMs,
            $warmAvgMs,
            $p50Ms,
            $p95Ms,
            $spreadMs,
            $slowThresholdMs,
        );

        $route += [
            'coldMs' => $coldMs,
            'warmAvgMs' => $warmAvgMs,
            'p50Ms' => $p50Ms,
            'p95Ms' => $p95Ms,
            'minMs' => $minMs,
            'maxMs' => $maxMs,
            'spreadMs' => $spreadMs,
            'stageProfile' => $this->stagePerformanceProfile($this->floatStageSamples($route['stageSamples'] ?? null)),
            'classification' => $classification,
            'investigate' => $investigate,
        ];

        return [$route, $warm];
    }

    /** @return array<string, array<int, float>> */
    private function floatStageSamples(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $result = [];
        foreach ($value as $stage => $samples) {
            if (!is_string($stage) || !is_array($samples)) {
                continue;
            }
            foreach ($samples as $index => $sample) {
                if (is_int($index) && (is_float($sample) || is_int($sample))) {
                    $result[$stage][$index] = (float) $sample;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<string, array<int, float>> $stageSamples
     *
     * @return array<string, array<string, mixed>>
     */
    private function stagePerformanceProfile(array $stageSamples): array
    {
        $profile = [];
        foreach ($stageSamples as $stage => $samples) {
            ksort($samples);
            $ordered = [];
            foreach ($samples as $sample) {
                $ordered[] = $sample;
            }
            $warm = count($ordered) > 1 ? array_slice($ordered, 1) : $ordered;
            sort($warm);
            $profile[$stage] = [
                'samplesMs' => array_map(static fn (float $value): float => round($value, 2), $ordered),
                'coldMs' => round($ordered[0] ?? 0.0, 2),
                'warmAvgMs' => round([] === $warm ? ($ordered[0] ?? 0.0) : array_sum($warm) / count($warm), 2),
                'warmP50Ms' => round($this->floatPercentile($warm, 0.50), 2),
                'warmP95Ms' => round($this->floatPercentile($warm, 0.95), 2),
            ];
        }

        return $profile;
    }

    /**
     * @param array<string, mixed> $route
     * @param list<int>            $warm
     *
     * @return array{0: string, 1: string}
     */
    private function performanceClassification(
        array $route,
        array $warm,
        int $coldMs,
        int $warmAvgMs,
        int $p50Ms,
        int $p95Ms,
        int $spreadMs,
        int $slowThresholdMs,
    ): array {
        $status = $this->integerValue($route['status'] ?? null);
        $responseBytes = $this->integerValue($route['responseBytes'] ?? null);
        $contentType = $this->stringValue($route['contentType'] ?? null);

        if ($status >= 400) {
            return ['negative_probe', 'Expected negative-path timing; exclude from actionable route optimization.'];
        }
        if ($status >= 300) {
            return ['redirect_response', 'Redirect timing is not actionable route-rendering latency; inspect only when the redirect is reported as unexpected.'];
        }
        if ($responseBytes >= 262144) {
            return ['payload_heavy', 'Inspect pagination, projection size, duplicated shell locations, and embedded diagnostics.'];
        }
        if ($p50Ms >= $slowThresholdMs) {
            return [
                'sustained_slow',
                str_contains($contentType, 'html')
                    ? 'Inspect controller queries, Viewing composition, Twig rendering, and response payload size.'
                    : 'Inspect controller queries, external I/O, serialization, and payload size.',
            ];
        }
        if ($coldMs >= max($slowThresholdMs * 2, $warmAvgMs * 3)) {
            return ['first_touch', 'First-touch effect inside the shared sweep process; verify with an isolated sequence probe before optimizing.'];
        }
        if (count($warm) >= 4 && $p95Ms >= $slowThresholdMs && $spreadMs >= max(100, $p50Ms)) {
            return ['unstable', 'Inspect nondeterministic database work, external I/O, locks, cache misses, and oversized result sets.'];
        }

        return ['healthy', 'No performance action indicated.'];
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
        $path = (string) preg_replace_callback('/\{([^}]+)\}/', static function (array $match) use ($route, $name): string {
            $token = $match[1];
            $default = $route->getDefault($token);
            if (is_scalar($default) && '' !== (string) $default) {
                return rawurlencode((string) $default);
            }
            $token = strtolower($token);

            $requirement = strtolower((string) $route->getRequirement((string) $match[1]));
            $looksLikeUuid = str_contains($token, 'uuid')
                || (str_starts_with($name, 'app_messaging_') && str_contains($token, 'id'))
                || str_contains($requirement, 'uuid')
                || (str_contains($requirement, '0-9a-f') && str_contains($requirement, '36'));
            if ($looksLikeUuid) {
                return '00000000-0000-0000-0000-000000000001';
            }
            if (str_contains($token, 'code') && str_contains($requirement, '{3}')) {
                return 'USD';
            }

            return str_contains($token, 'id') ? '1' : 'audit-sample';
        }, $route->getPath());

        return ['name' => $name, 'path' => $route->getPath(), 'materializedPath' => $path, 'methods' => $methods, 'controller' => $route->getDefault('_controller')];
    }

    /**
     * @param array<string, mixed> $inventory
     *
     * @return list<array<string, mixed>>
     */
    private function candidates(array $inventory): array
    {
        $candidates = [];
        foreach ((array) ($inventory['routes'] ?? []) as $route) {
            if (!is_array($route) || !is_string($route['materializedPath'] ?? null) || !is_string($route['name'] ?? null) || !in_array('GET', (array) ($route['methods'] ?? []), true)) {
                continue;
            }
            $key = $route['materializedPath'].'|'.$route['name'];
            $candidates[$key] = $route + ['source' => 'symfony'];
        }

        foreach ($this->declaredCrudingAliases() as $entity) {
            foreach ([
                '/'.$entity,
                '/'.$entity.'/index',
                '/'.$entity.'/show',
                '/'.$entity.'/page',
                '/'.$entity.'/new',
                '/api/'.$entity,
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

        /** @var list<array<string, mixed>> $result */
        $result = array_values($candidates);

        return $result;
    }

    /** @return list<string> */
    private function declaredCrudingAliases(): array
    {
        $configFile = $this->kernel->getProjectDir().'/config/packages/cruding.yaml';
        if (!is_file($configFile)) {
            return [];
        }

        $config = Yaml::parseFile($configFile);
        $cruding = is_array($config) && is_array($config['cruding'] ?? null) ? $config['cruding'] : [];
        $aliases = $cruding['entity_class_alias_map'] ?? [];
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
                $path = is_string($route['materializedPath'] ?? null) ? $route['materializedPath'] : '/';
                $request = Request::create($path, 'GET', [], [], [], [
                    'HTTP_ACCEPT' => str_starts_with($path, '/api/') ? 'application/json' : 'text/html,application/json;q=0.8',
                    'HTTP_USER_AGENT' => 'SmartResponsor-Platform-URL-Audit/3.0',
                ]);
                $started = microtime(true);
                $response = null;
                try {
                    $response = $this->kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, true);
                    $status = $response->getStatusCode();
                    $contentType = strtolower((string) $response->headers->get('content-type', ''));
                    $location = (string) $response->headers->get('location', '');
                    $body = (string) $response->getContent();
                    if ($this->kernel instanceof TerminableInterface) {
                        $this->kernel->terminate($request, $response);
                    }
                    $error = '';
                } catch (\Throwable $exception) {
                    $status = 500;
                    $contentType = 'text/plain';
                    $location = '';
                    $body = $exception::class.' '.$exception->getMessage()."\n".$exception->getTraceAsString();
                    $error = $exception::class.': '.$exception->getMessage();
                }

                $headers = $response?->headers;
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
                        'interfacingRenderMs' => $headers?->get('X-Interfacing-Render-ms'),
                        'interfacingShellMs' => $headers?->get('X-Interfacing-Shell-ms'),
                        'interfacingSlotsMs' => $headers?->get('X-Interfacing-Slots-ms'),
                        'interfacingTwigMs' => $headers?->get('X-Interfacing-Twig-ms'),
                        'viewingResolveMs' => $headers?->get('X-Viewing-Resolve-ms'),
                        'viewingComposeMs' => $headers?->get('X-Viewing-Compose-ms'),
                        'viewingContextMs' => $headers?->get('X-Viewing-Context-ms'),
                        'viewingTwigMs' => $headers?->get('X-Viewing-Twig-ms'),
                        'crudContractMs' => $headers?->get('X-App-Crud-Contract-ms'),
                        'crudNavigationMs' => $headers?->get('X-App-Crud-Navigation-ms'),
                        'crudContextMs' => $headers?->get('X-Crud-Context-ms'),
                        'crudEntrypointMs' => $headers?->get('X-Crud-Entrypoint-ms'),
                        'crudDefinitionMs' => $headers?->get('X-Crud-Definition-ms'),
                        'crudContractFactoryMs' => $headers?->get('X-Crud-Contract-Factory-ms'),
                        'crudServiceResolutionMs' => $headers?->get('X-Crud-Service-Resolution-ms'),
                        'crudServiceInvocationMs' => $headers?->get('X-Crud-Service-Invocation-ms'),
                        'crudServiceClass' => $headers?->get('X-Crud-Service-Class'),
                        'crudDefaultIndexDefinitionMs' => $headers?->get('X-Crud-Default-Index-Definition-ms'),
                        'crudDefaultIndexContractMs' => $headers?->get('X-Crud-Default-Index-Contract-ms'),
                        'crudObjectFindAllMs' => $headers?->get('X-Crud-Object-Find-All-ms'),
                        'crudCollectionProjectionMs' => $headers?->get('X-Crud-Collection-Projection-ms'),
                    ],
                    'viewLoaderFailures' => $request->attributes->get('_view_loader_failures', []),
                    'viewRenderFailures' => $request->attributes->get('_view_render_failures', []),
                    'durationMs' => (int) round((microtime(true) - $started) * 1000),
                    'responseBytes' => strlen($body),
                    'memoryBytes' => memory_get_usage(true),
                    'peakMemoryBytes' => memory_get_peak_usage(true),
                    'error' => $error,
                    'bodyHash' => hash('sha256', $body),
                    'bodyPreview' => mb_substr(trim(strip_tags($body)), 0, 500),
                    'findings' => array_values(array_unique($findings)),
                ];
                $probes[] = $probe;
                fwrite($append, json_encode($probe, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);
                fflush($append);
                foreach ($this->doctrine->getManagers() as $manager) {
                    $manager->clear();
                }
                gc_collect_cycles();
            }
        } finally {
            fclose($append);
        }

        return $probes;
    }

    private function isExpectedRedirect(string $requestPath, string $location): bool
    {
        $targetPath = (string) parse_url($location, PHP_URL_PATH);
        $expectedTargets = [
            '/access/signup' => '/access/register',
            '/access/reset-password/reset' => '/access/reset-password/request',
            '/access/reset/password/reset' => '/access/reset/password/request',
        ];
        if (isset($expectedTargets[$requestPath]) && $expectedTargets[$requestPath] === $targetPath) {
            return true;
        }
        if (str_starts_with($requestPath, '/access/reset-password/reset/') && '/access/reset-password/reset' === $targetPath) {
            return true;
        }
        if (str_starts_with($requestPath, '/access/reset/password/reset/') && '/access/reset/password/reset' === $targetPath) {
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

        return is_array($decoded) ? $this->stringKeyedData($decoded) : [];
    }

    /** @return array<string, mixed> */
    private function readPhp(string $file): array
    {
        $value = is_file($file) ? require $file : [];

        return is_array($value) ? $this->stringKeyedData($value) : [];
    }

    /**
     * @param array<mixed> $value
     *
     * @return array<string, mixed>
     */
    private function stringKeyedData(array $value): array
    {
        return array_filter($value, static fn (int|string $key): bool => is_string($key), ARRAY_FILTER_USE_KEY);
    }

    private function stringValue(mixed $value, string $default = ''): string
    {
        return is_string($value) ? $value : $default;
    }

    private function integerValue(mixed $value, int $default = 0): int
    {
        if (is_int($value)) {
            return $value;
        }

        return is_numeric($value) ? (int) $value : $default;
    }

    /** @param array<string, mixed>|list<mixed> $value */
    private function writeJson(string $file, array $value): void
    {
        file_put_contents($file, json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);
    }
}
