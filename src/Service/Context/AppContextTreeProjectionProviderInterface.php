<?php

declare(strict_types=1);

namespace App\Service\Context;

use Symfony\Component\HttpFoundation\Request;

interface AppContextTreeProjectionProviderInterface
{
    public function key(): string;

    /**
     * @param list<array<string, mixed>> $rows
     * @param array<string, mixed>       $routeContext
     *
     * @return list<array<string, mixed>>
     */
    public function project(array $rows, array $routeContext, Request $request): array;
}
