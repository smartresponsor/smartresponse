<?php

declare(strict_types=1);

namespace App\Dto\Placement;

final class AppFulfillmentPlacementFormData
{
    public string $mode = 'onsite';
    public ?string $serviceArea = null;
    public ?float $radiusKm = null;
    public ?float $weightKg = null;
    public ?string $priority = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'serviceArea' => $this->serviceArea,
            'radiusKm' => $this->radiusKm,
            'weightKg' => $this->weightKg,
            'priority' => $this->priority,
        ];
    }
}
