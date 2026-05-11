<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
 */

namespace App\Contract\Ui;

use App\Dto\Dashboard\AppDashboardSurfacePayload;
use Symfony\Component\HttpFoundation\Request;

/**
 * App-level UI contract for the host dashboard surface.
 *
 * App owns host runtime routes and composition decisions, but it does not own
 * primary visual rendering. Implementations return dashboard composition
 * payloads that Bridging normalizes for the Interfacing provider surface.
 */
interface AppDashboardSurfaceContract
{
    public function buildDashboardSurface(Request $request): AppDashboardSurfacePayload;
}
