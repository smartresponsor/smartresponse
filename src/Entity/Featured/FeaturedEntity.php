<?php

declare(strict_types=1);

namespace App\Entity\Featured;

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
#[ORM\Table(name: 'featured')]
#[ORM\UniqueConstraint(name: 'uniq_featured_tenant_code', columns: ['object_tenant', 'object_code'])]
#[ORM\UniqueConstraint(name: 'uniq_featured_tenant_slug', columns: ['object_tenant', 'object_slug'])]
final class FeaturedEntity implements ObjectAuditedInterface, ObjectCodedInterface, ObjectIdentifiedInterface, ObjectScopedInterface, ObjectStatefulInterface, ObjectTitledInterface
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
    #[ORM\JoinColumn(name: 'module_id', nullable: true, onDelete: 'SET NULL')]
    private ?ModuleEntity $module = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $visible = true;

    public function __construct(string $code, string $slug, string $name, ?ModuleEntity $module = null, bool $visible = true, bool $active = true, string $tenantId = 'default')
    {
        $tenantId = $this->normalizeTenantId($tenantId);
        $this->initializeObjectScope(null, $tenantId);
        $this->initializeObjectCode($code);
        $this->initializeObjectIdentity(null, $slug);
        $this->initializeObjectState($active, true, null);
        $this->initializeObjectTitle($name);
        $this->module = $module;
        $this->visible = $visible;
        $this->initializeTimestamps();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function module(): ?ModuleEntity
    {
        return $this->module;
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

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function isActive(): bool
    {
        return $this->isObjectActive();
    }
}
