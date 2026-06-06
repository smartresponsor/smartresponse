<?php

declare(strict_types=1);

namespace App\Interfacing\ProviderInterface\Shell;

interface InterfaceShellNavigationMapProviderInterface
{
    public function map(?string $activeId = null): array;
}
