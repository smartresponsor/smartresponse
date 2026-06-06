<?php

declare(strict_types=1);

namespace App\Interfacing\Contract\View;

interface CrudResourceLinkSetInterface
{
    public function id(): string;

    public function component(): string;

    public function label(): string;

    public function resourcePath(): string;

    public function status(): string;

    public function note(): ?string;

    public function indexUrl(): string;

    public function newUrl(): string;

    public function showSampleUrl(): string;

    public function editSampleUrl(): string;

    public function deleteSampleUrl(): string;

    public function operationUrls(): array;
}
