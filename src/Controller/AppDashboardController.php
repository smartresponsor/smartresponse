<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
 */

namespace App\Controller;

use App\Bridging\ServiceInterface\AppHostInterfacing\AppDashboardSurfaceResponderInterface;
use App\Contract\Ui\AppDashboardSurfaceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Host application dashboard entrypoint.
 *
 * App owns the route and dashboard composition contract. Bridging owns the
 * handoff and rendering bridge into the Interfacing provider surface.
 */
#[AsController]
final readonly class AppDashboardController
{
    public function __construct(
        private readonly AppDashboardSurfaceContract $dashboardSurface,
        private readonly AppDashboardSurfaceResponderInterface $dashboardResponder,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $dashboardSurface = $this->dashboardSurface->buildDashboardSurface($request);

        return $this->dashboardResponder->respond($dashboardSurface, [
            'request_uri' => $request->getRequestUri(),
            'contentLocale' => $request->query->get('contentLocale', $request->getLocale()),
        ]);
    }
}
