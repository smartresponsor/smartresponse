<?php

declare(strict_types=1);

namespace App\Interfacing\ProviderInterface\Shell;

interface InterfaceShellChromeProviderInterface
{
    public function provide(?string $activeId = null, bool $includeResourceSummaries = false, bool $includeApplicationDashboard = false): array;
}
