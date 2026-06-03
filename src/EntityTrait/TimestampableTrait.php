<?php

declare(strict_types=1);

namespace App\EntityTrait;

use App\Objecting\EntityTrait\Embeddable\ObjectAuditEmbeddableTrait;

trait TimestampableTrait
{
    use ObjectAuditEmbeddableTrait;

    protected function initializeTimestamps(?\DateTimeImmutable $now = null): void
    {
        $now ??= new \DateTimeImmutable();
        $this->initializeObjectAudit($now);
        $this->touchObject($now);
    }

    public function touch(?\DateTimeImmutable $now = null): void
    {
        $this->touchObject($now);
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->getObjectCreatedAt();
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->getObjectUpdatedAt() ?? $this->getObjectCreatedAt();
    }

    protected function normalizeTenantId(string $tenantId): string
    {
        $tenantId = trim($tenantId);

        return '' === $tenantId ? 'default' : $tenantId;
    }
}
