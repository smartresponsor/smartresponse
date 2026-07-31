<?php

declare(strict_types=1);

namespace App\Service\Diagnostics;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;

final readonly class AppRouteAuditService
{
    private const AUDIT_PATH = '/interfacing/route/audit';

    public function __construct(
        private RouterInterface $router,
        private HttpKernelInterface $kernel,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $limit = max(0, min(500, (int) $request->query->get('limit', 0)));
        $prefix = trim((string) $request->query->get('prefix', ''));
        $paths = $this->collectAuditablePaths($prefix, $limit);

        $rows = [];
        foreach ($paths as $path) {
            $human = $this->probe($path, 'human');
            $bot = $this->probe($path, 'bot');

            $rows[] = [
                'path' => $path,
                'human' => $human,
                'bot' => $bot,
            ];
        }

        $summary = [
            'total' => count($rows),
            'httpOkHuman' => count(array_filter($rows, static fn (array $row): bool => (($row['human']['status'] ?? 0) >= 200 && ($row['human']['status'] ?? 0) < 400))),
            'httpOkBot' => count(array_filter($rows, static fn (array $row): bool => (($row['bot']['status'] ?? 0) >= 200 && ($row['bot']['status'] ?? 0) < 400))),
            'fallbackHuman' => count(array_filter($rows, static fn (array $row): bool => (bool) ($row['human']['fallback'] ?? false))),
            'fallbackBot' => count(array_filter($rows, static fn (array $row): bool => (bool) ($row['bot']['fallback'] ?? false))),
            'botJsonPolicy' => count(array_filter($rows, static fn (array $row): bool => (bool) ($row['bot']['viewingBotJsonPolicy'] ?? false))),
        ];

        return new JsonResponse([
            'ok' => true,
            'component' => 'app',
            'surface' => 'route-audit',
            'summary' => $summary,
            'filters' => [
                'prefix' => $prefix,
                'limit' => $limit,
            ],
            'rows' => $rows,
            'notes' => [
                'human probe uses Accept:text/html and a browser-like User-Agent.',
                'bot probe uses Accept:text/html and a bot User-Agent; Viewing should choose JSON mode without requiring Interfacing templates.',
                'fallback=true means either Viewing template_missing_json_fallback or Interfacing root fallback marker in HTML.',
                'Deep probing is opt-in: pass ?limit=N; the default limit=0 avoids synchronous request fan-out.',
            ],
        ]);
    }

    /**
     * @return list<string>
     */
    private function collectAuditablePaths(string $prefix, int $limit): array
    {
        $paths = [];
        foreach ($this->router->getRouteCollection()->all() as $route) {
            $path = (string) $route->getPath();
            if ('' === $path || !str_starts_with($path, '/')) {
                continue;
            }
            if (str_contains($path, '{')) {
                continue;
            }
            if (self::AUDIT_PATH === $path) {
                continue;
            }
            if ('' !== $prefix && !str_starts_with($path, $prefix)) {
                continue;
            }
            if ($this->isExcludedPath($path)) {
                continue;
            }

            $methods = $route->getMethods();
            if ([] !== $methods && !in_array('GET', $methods, true)) {
                continue;
            }

            $paths[$path] = true;
            if (count($paths) >= $limit) {
                break;
            }
        }

        $result = array_keys($paths);
        sort($result);

        return $result;
    }

    private function isExcludedPath(string $path): bool
    {
        return str_starts_with($path, '/_profiler')
            || str_starts_with($path, '/_wdt')
            || str_starts_with($path, '/assets')
            || str_starts_with($path, '/build')
            || str_starts_with($path, '/api');
    }

    /**
     * @return array<string, mixed>
     */
    private function probe(string $path, string $profile): array
    {
        $isBot = 'bot' === $profile;
        $request = Request::create($path, 'GET');
        $request->headers->set('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
        $request->headers->set('User-Agent', $isBot ? 'Googlebot/2.1 (+http://www.google.com/bot.html)' : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        $request->headers->set('Host', '127.0.0.1');

        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST, true);
        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        $content = (string) $response->getContent();

        $isJson = str_contains($contentType, 'application/json');
        $isHtml = str_contains($contentType, 'text/html');
        $fallback = false;
        $fallbackReason = null;
        $viewingReasons = [];

        if ($isJson) {
            $payload = json_decode($content, true);
            if (is_array($payload)) {
                $viewingReasons = is_array($payload['_viewing']['reasons'] ?? null) ? $payload['_viewing']['reasons'] : [];
                if (in_array('template_missing_json_fallback', $viewingReasons, true)) {
                    $fallback = true;
                    $fallbackReason = 'template_missing_json_fallback';
                }
            }
        }

        if ($isHtml && str_contains($content, 'data-interfacing-root-fallback="json"')) {
            $fallback = true;
            $fallbackReason = 'interfacing_root_fallback_html';
        }

        $viewingBotJsonPolicy = $isBot && $isJson && in_array('actor_type_forces_json', $viewingReasons, true);

        return [
            'status' => $response->getStatusCode(),
            'contentType' => $contentType,
            'isJson' => $isJson,
            'isHtml' => $isHtml,
            'viewingRendered' => '1' === (string) $response->headers->get('X-Viewing-Rendered', ''),
            'fallback' => $fallback,
            'fallbackReason' => $fallbackReason,
            'viewingReasons' => $viewingReasons,
            'viewingBotJsonPolicy' => $viewingBotJsonPolicy,
        ];
    }
}
