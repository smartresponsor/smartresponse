<?php

declare(strict_types=1);

namespace App\Dto\Placement\Retail;

final class RetailPlacementPricingFormData
{
    public string $model = 'fixed';
    public ?int $amountMinor = null;
    public ?int $maximumAmountMinor = null;
    public string $currency = 'USD';

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'model' => $this->model,
            'amountMinor' => $this->amountMinor,
            'maximumAmountMinor' => $this->maximumAmountMinor,
            'currency' => $this->currency,
        ];
    }
}
