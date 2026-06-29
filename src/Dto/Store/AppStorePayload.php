<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
 */

namespace App\Dto\Store;

/**
 * Host store composition payload produced by App and normalized by Viewing
 * before it reaches the Interfacing template hierarchy.
 */
final readonly class AppStorePayload
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private array $data,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toTemplateContext(): array
    {
        return $this->data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toFallbackData(): array
    {
        return $this->data;
    }
}
