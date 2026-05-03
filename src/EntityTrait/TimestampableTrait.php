<?php

declare(strict_types=1);

namespace App\EntityTrait;

trait TimestampableTrait
{
    #[\Doctrine\ORM\Mapping\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[\Doctrine\ORM\Mapping\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    protected function initializeTimestamps(?\DateTimeImmutable $now = null): void
    {
        $now ??= new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function touch(?\DateTimeImmutable $now = null): void
    {
        $this->updatedAt = $now ?? new \DateTimeImmutable();
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    protected function normalizeTenantId(string $tenantId): string
    {
        $tenantId = trim($tenantId);

        return '' === $tenantId ? 'default' : $tenantId;
    }
}
