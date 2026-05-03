<?php

declare(strict_types=1);

namespace App\Entity\Product;

use App\Entity\Module\ModuleEntity;
use App\EntityTrait\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_type')]
#[ORM\UniqueConstraint(name: 'uniq_product_type_tenant_code', columns: ['tenant_id', 'code'])]
#[ORM\UniqueConstraint(name: 'uniq_product_type_tenant_slug', columns: ['tenant_id', 'slug'])]
final class ProductTypeEntity
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ModuleEntity::class)]
    #[ORM\JoinColumn(name: 'module_id', nullable: false, onDelete: 'CASCADE')]
    private ModuleEntity $module;

    #[ORM\Column(name: 'tenant_id', length: 64)]
    private string $tenantId;

    #[ORM\Column(length: 64)]
    private string $code;

    #[ORM\Column(length: 180)]
    private string $slug;

    #[ORM\Column(length: 160)]
    private string $name;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $active = true;

    public function __construct(ModuleEntity $module, string $code, string $slug, string $name, bool $active = true, string $tenantId = 'default')
    {
        $this->module = $module;
        $this->tenantId = $this->normalizeTenantId($tenantId);
        $this->code = $code;
        $this->slug = $slug;
        $this->name = $name;
        $this->active = $active;
        $this->initializeTimestamps();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function module(): ModuleEntity
    {
        return $this->module;
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

    public function isActive(): bool
    {
        return $this->active;
    }
}
