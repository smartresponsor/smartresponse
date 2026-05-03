<?php

declare(strict_types=1);

namespace App\Entity\Menu;

use App\Entity\Module\ModuleEntity;
use App\EntityTrait\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'menu')]
#[ORM\UniqueConstraint(name: 'uniq_menu_tenant_module_slug', columns: ['tenant_id', 'module_id', 'slug'])]
final class MenuEntity
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $route = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $visible = true;

    public function __construct(ModuleEntity $module, string $code, string $slug, string $name, ?string $route = null, ?self $parent = null, int $position = 0, bool $visible = true, string $tenantId = 'default')
    {
        $this->module = $module;
        $this->code = $code;
        $this->tenantId = $this->normalizeTenantId($tenantId);
        $this->slug = $slug;
        $this->name = $name;
        $this->route = $route;
        $this->parent = $parent;
        $this->position = $position;
        $this->visible = $visible;
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

    public function route(): ?string
    {
        return $this->route;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }
}
