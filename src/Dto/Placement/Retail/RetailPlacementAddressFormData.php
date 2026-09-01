<?php

declare(strict_types=1);

namespace App\Dto\Placement\Retail;

use Symfony\Component\Validator\Constraints as Assert;

final class RetailPlacementAddressFormData
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 256)]
    public string $line1 = '';

    #[Assert\Length(max: 256)]
    public ?string $line2 = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    public string $city = '';

    #[Assert\Length(max: 32)]
    public ?string $region = null;

    #[Assert\Length(max: 32)]
    public ?string $postalCode = null;

    #[Assert\Country]
    public string $countryCode = 'US';
}
