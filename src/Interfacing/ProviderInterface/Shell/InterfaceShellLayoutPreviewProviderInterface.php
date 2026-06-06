<?php

declare(strict_types=1);

namespace App\Interfacing\ProviderInterface\Shell;

interface InterfaceShellLayoutPreviewProviderInterface
{
    public function preview(?string $activeId = null): array;
}
