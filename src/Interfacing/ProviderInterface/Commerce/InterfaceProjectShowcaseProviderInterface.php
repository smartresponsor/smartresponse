<?php

declare(strict_types=1);

namespace App\Interfacing\ProviderInterface\Commerce;

/**
 * Host-owned provider contract for the public project showcase payload.
 *
 * Interfacing owns the rendering surface. The host app owns the business
 * content provider so the public /project page can be swapped from demo data
 * to a real data source without depending on the Interfacing package boundary.
 */
interface InterfaceProjectShowcaseProviderInterface
{
    /**
     * @param array<string, mixed> $criteria
     *
     * @return array<string, mixed>
     */
    public function provide(array $criteria = []): array;
}
