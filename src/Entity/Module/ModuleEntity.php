<?php

declare(strict_types=1);

namespace App\Entity\Module;

use App\EntityTrait\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'module')]
#[ORM\UniqueConstraint(name: 'uniq_module_tenant_code', columns: ['tenant_id', 'code'])]
#[ORM\UniqueConstraint(name: 'uniq_module_tenant_slug', columns: ['tenant_id', 'slug'])]
final class ModuleEntity
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'tenant_id', length: 64)]
    private string $tenantId;

    #[ORM\Column(length: 64)]
    private string $code;

    #[ORM\Column(length: 180)]
    private string $slug;

    #[ORM\Column(length: 160)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $active = true;

    public function __construct(string $code, string $slug, string $name, ?string $description = null, bool $active = true, string $tenantId = 'default')
    {
        $this->tenantId = $this->normalizeTenantId($tenantId);
        $this->code = $code;
        $this->slug = $slug;
        $this->name = $name;
        $this->description = $description;
        $this->active = $active;
        $this->initializeTimestamps();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->touch();
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
        $this->touch();
    }
}
