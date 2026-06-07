<?php

declare(strict_types=1);

namespace App\EntityTrait;

use Doctrine\ORM\Mapping as ORM;

trait TimestampableTrait
{
    #[ORM\Embedded(class: \App\Objecting\Embeddable\ObjectAuditEmbeddable::class, columnPrefix: false)]
    private \App\Objecting\Embeddable\ObjectAuditEmbeddable $objectAudit;

    protected function initializeObjectAudit(?\DateTimeImmutable $createdAt = null, ?string $createdBy = null): void
    {
        $this->objectAudit = new \App\Objecting\Embeddable\ObjectAuditEmbeddable($createdAt, $createdBy);
    }

    private function objectAuditEmbeddable(): \App\Objecting\Embeddable\ObjectAuditEmbeddable
    {
        if (!isset($this->objectAudit)) {
            $this->objectAudit = new \App\Objecting\Embeddable\ObjectAuditEmbeddable();
        }

        return $this->objectAudit;
    }

    public function getObjectCreatedAt(): \DateTimeImmutable
    {
        return $this->objectAuditEmbeddable()->getObjectCreatedAt();
    }

    public function getObjectUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->objectAuditEmbeddable()->getObjectUpdatedAt();
    }

    public function getObjectCreatedBy(): ?string
    {
        return $this->objectAuditEmbeddable()->getObjectCreatedBy();
    }

    public function getObjectUpdatedBy(): ?string
    {
        return $this->objectAuditEmbeddable()->getObjectUpdatedBy();
    }

    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->objectAuditEmbeddable()->getModifiedAt();
    }

    public function getModifiedBy(): ?string
    {
        return $this->objectAuditEmbeddable()->getModifiedBy();
    }

    public function touchObject(?\DateTimeImmutable $updatedAt = null, ?string $updatedBy = null): void
    {
        $this->objectAuditEmbeddable()->touch($updatedAt, $updatedBy);
    }

    public function touchModified(?\DateTimeImmutable $modifiedAt = null, ?string $modifiedBy = null): void
    {
        $this->touchObject($modifiedAt, $modifiedBy);
    }

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
