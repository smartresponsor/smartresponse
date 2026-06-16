<?php

declare(strict_types=1);

namespace App\ServiceInterface\InterfaceLocation;

use Symfony\Component\HttpFoundation\Request;

interface AppInterfaceLocationComposeServiceInterface
{
    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function composeLocations(Request $request): array;
}
