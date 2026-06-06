<?php

declare(strict_types=1);

namespace App\Interfacing\Contribution\Crud;

abstract class InterfaceAbstractCrudResourceContribution
{
    protected function genericResource(
        string $resourceKey,
        string $component,
        string $label,
        string $pathSegment,
        string $description,
    ): array {
        return [
            'resource' => $resourceKey,
            'resourceKey' => $resourceKey,
            'component' => $component,
            'label' => $label,
            'pathSegment' => $pathSegment,
            'description' => $description,
        ];
    }
}
