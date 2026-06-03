<?php

declare(strict_types=1);

namespace App\Entity\Menu;

use App\Entity\Module\ModuleEntity;
use App\EntityTrait\TimestampableTrait;
use App\Objecting\EntityInterface\ObjectAuditedInterface;
use App\Objecting\EntityInterface\ObjectCodedInterface;
use App\Objecting\EntityInterface\ObjectIdentifiedInterface;
use App\Objecting\EntityInterface\ObjectScopedInterface;
use App\Objecting\EntityInterface\ObjectStatefulInterface;
use App\Objecting\EntityInterface\ObjectTitledInterface;
use App\Objecting\EntityTrait\Embeddable\ObjectCodeEmbeddableTrait;
use App\Objecting\EntityTrait\Embeddable\ObjectIdentityEmbeddableTrait;
use App\Objecting\EntityTrait\Embeddable\ObjectScopeEmbeddableTrait;
use App\Objecting\EntityTrait\Embeddable\ObjectStateEmbeddableTrait;
use App\Objecting\EntityTrait\Embeddable\ObjectTitleEmbeddableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'menu')]
#[ORM\UniqueConstraint(name: 'uniq_menu_tenant_module_slug', columns: ['object_tenant', 'module_id', 'object_slug'])]
final class MenuEntity implements ObjectAuditedInterface, ObjectCodedInterface, ObjectIdentifiedInterface, ObjectScopedInterface, ObjectStatefulInterface, ObjectTitledInterface
{
    use TimestampableTrait;
    use ObjectCodeEmbeddableTrait;
    use ObjectIdentityEmbeddableTrait;
    use ObjectScopeEmbeddableTrait;
    use ObjectStateEmbeddableTrait;
    use ObjectTitleEmbeddableTrait;

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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $route = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $position = 0;
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $visible = true;

    public function __construct(ModuleEntity $module, string $code, string $slug, string $name, ?string $route = null, ?self $parent = null, int $position = 0, bool $visible = true, string $tenantId = 'default')
    {
        $this->module = $module;
        $tenantId = $this->normalizeTenantId($tenantId);
        $this->initializeObjectScope(null, $tenantId);
        $this->initializeObjectCode($code);
        $this->initializeObjectIdentity(null, $slug);
        $this->initializeObjectState(true, true, null);
        $this->initializeObjectTitle($name);
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
        return $this->getObjectCode() ?? '';
    }

    public function tenantId(): string
    {
        return $this->getObjectTenant() ?? 'default';
    }

    public function slug(): string
    {
        return $this->getObjectSlug() ?? '';
    }

    public function name(): string
    {
        return $this->getFirstTitle() ?? '';
    }

    public function getTitle(): string
    {
        return $this->name();
    }

    public function setTitle(string $title): void
    {
        $this->setFirstTitle($title);
        $this->touch();
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
