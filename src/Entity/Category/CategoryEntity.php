<?php

declare(strict_types=1);

namespace App\Entity\Category;

use App\Entity\Module\ModuleEntity;
use App\EntityTrait\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'category')]
#[ORM\UniqueConstraint(name: 'uniq_category_tenant_module_slug', columns: ['tenant_id', 'module_id', 'slug'])]
final class CategoryEntity
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ModuleEntity::class)]
    #[ORM\JoinColumn(name: 'module_id', nullable: false, onDelete: 'CASCADE')]
    private ModuleEntity $module;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parent_id', nullable: true, onDelete: 'SET NULL')]
    private ?self $parent = null;

    #[ORM\Column(length: 64)]
    private string $code;

    #[ORM\Column(name: 'tenant_id', length: 64)]
    private string $tenantId;

    #[ORM\Column(length: 180)]
    private string $slug;

    #[ORM\Column(length: 160)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $active = true;

    public function __construct(ModuleEntity $module, string $code, string $slug, string $name, ?string $description = null, ?self $parent = null, bool $active = true, string $tenantId = 'default')
    {
        $this->module = $module;
        $this->code = $code;
        $this->tenantId = $this->normalizeTenantId($tenantId);
        $this->slug = $slug;
        $this->name = $name;
        $this->description = $description;
        $this->parent = $parent;
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

    public function parent(): ?self
    {
        return $this->parent;
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
