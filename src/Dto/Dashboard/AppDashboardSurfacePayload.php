<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
 */

namespace App\Dto\Dashboard;

/**
 * Host dashboard UI composition payload produced by App and normalized by
 * Bridging before it reaches the Interfacing provider document.
 */
final readonly class AppDashboardSurfacePayload
{
    /**
     * @param array<string, mixed> $surface
     */
    public function __construct(
        private array $surface,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->surface;
    }
}
