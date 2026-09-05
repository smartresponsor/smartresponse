<?php

declare(strict_types=1);

namespace App\Resolver\Context;

use App\Registry\Context\AppContextTreeProjectionRegistry;
use Symfony\Component\HttpFoundation\Request;

final readonly class AppContextTreeProjectionResolver
{
    public function __construct(private AppContextTreeProjectionRegistry $registry)
    {
    }

    /**
     * @param array<string, list<array<string, mixed>>> $locations
     * @param list<array<string, mixed>>                $rows
     * @param array<string, mixed>                      $routeContext
     *
     * @return list<array<string, mixed>>
     */
    public function resolve(array $locations, array $rows, array $routeContext, Request $request): array
    {
        $providerKey = $this->activeProviderKey($locations);
        if (null === $providerKey || !$this->registry->has($providerKey)) {
            return [];
        }

        return $this->registry->get($providerKey)->project($rows, $routeContext, $request);
    }

    /** @param array<string, list<array<string, mixed>>> $locations */
    private function activeProviderKey(array $locations): ?string
    {
        foreach ($locations as $location => $items) {
            if (!str_starts_with($location, 'shell.left.')) {
                continue;
            }

            foreach ($items as $item) {
                if (true !== ($item['active'] ?? false)) {
                    continue;
                }

                $metadata = is_array($item['metadata'] ?? null) ? $item['metadata'] : [];
                if (!is_scalar($metadata['context_provider'] ?? null)) {
                    continue;
                }

                $providerKey = trim((string) $metadata['context_provider']);
                if ('' !== $providerKey) {
                    return $providerKey;
                }
            }
        }

        return null;
    }
}
