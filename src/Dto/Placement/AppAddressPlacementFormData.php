<?php

declare(strict_types=1);

namespace App\Dto\Placement;

use Symfony\Component\Validator\Constraints as Assert;

final class AppAddressPlacementFormData
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 256)]
    public string $originLine1 = '';

    #[Assert\Length(max: 256)]
    public ?string $originLine2 = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    public string $originCity = '';

    #[Assert\Length(max: 32)]
    public ?string $originRegion = null;

    #[Assert\Length(max: 32)]
    public ?string $originPostalCode = null;

    #[Assert\Country]
    public string $originCountryCode = 'US';

    #[Assert\NotBlank]
    #[Assert\Length(max: 256)]
    public string $destinationLine1 = '';

    #[Assert\Length(max: 256)]
    public ?string $destinationLine2 = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    public string $destinationCity = '';

    #[Assert\Length(max: 32)]
    public ?string $destinationRegion = null;

    #[Assert\Length(max: 32)]
    public ?string $destinationPostalCode = null;

    #[Assert\Country]
    public string $destinationCountryCode = 'US';
}
