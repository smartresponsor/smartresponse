<?php

declare(strict_types=1);

namespace App\Provider\Catalog;

use App\ProviderInterface\Context\AppContextTreeProjectionProviderInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

final readonly class CatalogContextTreeProjectionProvider implements AppContextTreeProjectionProviderInterface
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

        $categoriesByCatalog = $this->categoriesByCatalog($categoryRows);

        $tree = [];
        foreach ($catalogRows as $index => $catalogRow) {
            $catalogIdValue = filter_var($catalogRow['id'] ?? null, FILTER_VALIDATE_INT);
            $catalogId = false === $catalogIdValue ? 0 : (int) $catalogIdValue;
            $title = $catalogRow['title'] ?? null;
            $name = $catalogRow['name'] ?? null;
            $catalogLabel = is_string($title) ? $title : (is_string($name) ? $name : (string) $catalogId);

            $tree[] = [
                'label' => $catalogLabel,
                'expanded' => 0 === $index,
                'children' => $categoriesByCatalog[$catalogId] ?? [],
            ];
        }

        return $tree;
    }

    /**
     * @param list<array<string, mixed>> $categoryRows
     *
     * @return array<int, list<array<string, mixed>>>
     */
    private function categoriesByCatalog(array $categoryRows): array
    {
        $categoriesByCatalog = [];
        foreach ($categoryRows as $categoryRow) {
            $catalogIdValue = filter_var($categoryRow['catalog_id'] ?? null, FILTER_VALIDATE_INT);
            $categoryIdValue = filter_var($categoryRow['id'] ?? null, FILTER_VALIDATE_INT);
            $categoryLabel = $categoryRow['name_entity'] ?? null;
            if (false === $catalogIdValue || false === $categoryIdValue || !is_string($categoryLabel)) {
                continue;
            }

            $catalogId = (int) $catalogIdValue;
            $categoryId = (int) $categoryIdValue;
            $categoriesByCatalog[$catalogId][] = [
                'label' => $categoryLabel,
                'href' => '/category/show/'.$categoryId,
                'children' => [],
            ];
        }

        return $categoriesByCatalog;
    }
}
