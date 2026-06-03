<?php

declare(strict_types=1);

namespace App\Service\Interfacing\Shell;

use Symfony\Component\Routing\RouterInterface;

final class ShellChromeUrlResolverService
{
    /** @var array<string, bool> */
    private array $routeExistsCache = [];

    /** @var array<string, string> */
    private array $generatedUrlCache = [];

    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    /** @param array<string, string> $parameters */
    public function safeUrl(string $route, string $fallback, array $parameters = []): string
    {
        $cacheKey = $route.'|'.md5(json_encode($parameters, JSON_THROW_ON_ERROR));

        if (array_key_exists($cacheKey, $this->generatedUrlCache)) {
            return $this->generatedUrlCache[$cacheKey];
        }

        if (array_key_exists($route, $this->routeExistsCache) && !$this->routeExistsCache[$route]) {
            return $this->generatedUrlCache[$cacheKey] = $fallback;
        }

        if (null === $this->router->getRouteCollection()?->get($route)) {
            $this->routeExistsCache[$route] = false;

            return $this->generatedUrlCache[$cacheKey] = $fallback;
        }

        $this->routeExistsCache[$route] = true;

        try {
            return $this->generatedUrlCache[$cacheKey] = $this->router->generate($route, $parameters);
        } catch (\Throwable) {
            return $this->generatedUrlCache[$cacheKey] = $fallback;
        }
    }

    public function screenUrl(string $screenId): string
    {
        return $this->safeUrl('interfacing_screen', '/interfacing/'.$screenId, ['id' => $screenId]);
    }
}
