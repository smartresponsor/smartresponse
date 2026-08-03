<?php

declare(strict_types=1);

namespace App\Service\Catalog;

use App\ServiceInterface\Context\AppContextTreeProjectionProviderInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

final readonly class AppCatalogContextTreeProjectionService implements AppContextTreeProjectionProviderInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function key(): string
    {
        return 'catalog.tree';
    }

    /**
     * @param list<array<string, mixed>> $catalogRows
     *
     * @return list<array<string, mixed>>
     */
    public function project(array $catalogRows, array $routeContext, Request $request): array
    {
        if ([] === $catalogRows) {
            return [];
        }

        $catalogIds = [];
        foreach ($catalogRows as $catalogRow) {
            $catalogId = filter_var($catalogRow['id'] ?? null, FILTER_VALIDATE_INT);
            if (false !== $catalogId) {
                $catalogIds[] = (int) $catalogId;
            }
        }

        if ([] === $catalogIds) {
            return [];
        }

        $categoryRows = $this->connection->fetchAllAssociative(
            'SELECT id, name_entity, catalog_id
             FROM category
             WHERE catalog_id IN (?) AND published = TRUE AND depth = 1
             ORDER BY catalog_id, path',
            [$catalogIds],
            [ArrayParameterType::INTEGER],
        );

        $categoriesByCatalog = [];
        foreach ($categoryRows as $categoryRow) {
            $catalogId = (int) $categoryRow['catalog_id'];
            $categoryId = (int) $categoryRow['id'];
            $categoriesByCatalog[$catalogId][] = [
                'label' => (string) $categoryRow['name_entity'],
                'href' => '/category/show/'.$categoryId,
                'children' => [],
            ];
        }

        $tree = [];
        foreach ($catalogRows as $index => $catalogRow) {
            $catalogId = (int) ($catalogRow['id'] ?? 0);
            $catalogLabel = (string) ($catalogRow['title'] ?? $catalogRow['name'] ?? $catalogId);

            $tree[] = [
                'label' => $catalogLabel,
                'expanded' => 0 === $index,
                'children' => $categoriesByCatalog[$catalogId] ?? [],
            ];
        }

        return $tree;
    }
}
