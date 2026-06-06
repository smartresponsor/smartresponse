<?php

declare(strict_types=1);

namespace App\Interfacing\ProviderInterface\Shell;

interface InterfaceShellScreenCatalogProviderInterface
{
    public function catalog(?string $activeId = null): array;
}
