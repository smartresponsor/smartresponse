<?php

declare(strict_types=1);

namespace App\Interfacing\Contract\View;

final readonly class CrudResourceLinkSet implements CrudResourceLinkSetInterface
{
    public function __construct(
        private string $id,
        private string $component,
        private string $label,
        private string $resourcePath,
        private string $status,
        private string $indexUrl,
        private string $newUrl,
        private string $showSampleUrl,
        private string $editSampleUrl,
        private string $deleteSampleUrl,
        private ?string $note = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function component(): string
    {
        return $this->component;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function resourcePath(): string
    {
        return $this->resourcePath;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function note(): ?string
    {
        return $this->note;
    }

    public function indexUrl(): string
    {
        return $this->indexUrl;
    }

    public function newUrl(): string
    {
        return $this->newUrl;
    }

    public function showSampleUrl(): string
    {
        return $this->showSampleUrl;
    }

    public function editSampleUrl(): string
    {
        return $this->editSampleUrl;
    }

    public function deleteSampleUrl(): string
    {
        return $this->deleteSampleUrl;
    }

    public function operationUrls(): array
    {
        return [
            'index' => $this->indexUrl,
            'new' => $this->newUrl,
            'show' => $this->showSampleUrl,
            'edit' => $this->editSampleUrl,
            'delete' => $this->deleteSampleUrl,
        ];
    }
}
