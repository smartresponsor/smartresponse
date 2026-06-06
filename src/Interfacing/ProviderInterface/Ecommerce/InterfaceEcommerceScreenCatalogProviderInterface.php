<?php

declare(strict_types=1);

namespace App\Interfacing\ProviderInterface\Ecommerce;

interface InterfaceEcommerceScreenCatalogProviderInterface
{
    public function provide(): array;

    public function groupedByZone(): array;

    public function statusCounts(): array;

    public function componentSummaryByZone(): array;
}
