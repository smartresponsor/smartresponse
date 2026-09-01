<?php

declare(strict_types=1);

namespace App\Dto\Placement;

final class RetailReviewFormData
{
    public string $title = '';
    public string $kind = '';
    public string $catalog = '';
    public string $typePath = '';
    public string $location = '';
    public string $fulfillment = '';
    public string $pricing = '';
    public bool $publish = false;
}
