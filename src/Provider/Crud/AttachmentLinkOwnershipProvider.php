<?php

declare(strict_types=1);

namespace App\Provider\Crud;

use App\Attaching\Entity\Attachment\AttachmentLinkEntity;
use App\Cruding\DTO\CrudOwnershipDTO;
use App\Cruding\DTO\CrudOwnershipResolutionContextDTO;
use App\Cruding\Provider\CrudDefaultOwnershipProvider;
use App\Cruding\ServiceInterface\CrudOwnershipProviderInterface;
use App\Projecting\Entity\Project\ProjectEntity;
use App\Projecting\Entity\Project\ProjectVendorBridgeEntity;
use Doctrine\Persistence\ManagerRegistry;

final readonly class AttachmentLinkOwnershipProvider implements CrudOwnershipProviderInterface
{
    /** @param array<string, string> $ownerClassByType Configured class names, validated before Doctrine lookup. */
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private CrudDefaultOwnershipProvider $defaultOwnershipProvider,
        private array $ownerClassByType,
    ) {
    }

    public function supports(CrudOwnershipResolutionContextDTO $context): bool
    {
        return $context->object instanceof AttachmentLinkEntity;
    }

    public function resolve(CrudOwnershipResolutionContextDTO $context): CrudOwnershipDTO
    {
        $authenticated = null !== $context->actor;
        /** @var AttachmentLinkEntity $link */
        $link = $context->object;
        $ownerType = strtolower(trim($link->getOwnerType()));
        $ownerClass = $this->ownerClassByType[$ownerType] ?? null;

        if (null === $ownerClass || !class_exists($ownerClass)) {
            return $this->unresolved($authenticated, $context->isAdmin, $ownerType);
        }

        $manager = $this->managerRegistry->getManagerForClass($ownerClass);
        if (null === $manager) {
            return $this->unresolved($authenticated, $context->isAdmin, $ownerType);
        }

        $owner = $manager->getRepository($ownerClass)->find($link->getOwnerId());
        if (!is_object($owner)) {
            return $this->unresolved($authenticated, $context->isAdmin, $ownerType);
        }

        if (null !== $context->actor && $this->sameIdentity($context->actor, $owner)) {
            return new CrudOwnershipDTO(true, true, true, $context->isAdmin, 'attachment:'.$ownerType);
        }

        if ($owner instanceof ProjectEntity) {
            return $this->resolveProjectOwnership($owner, $context, $ownerType);
        }

        $resolved = $this->defaultOwnershipProvider->resolve(
            new CrudOwnershipResolutionContextDTO($owner, $context->actor, $context->isAdmin),
        );

        if (!$resolved->supportsOwnership) {
            return $this->unresolved($authenticated, $context->isAdmin, $ownerType);
        }

        return new CrudOwnershipDTO(
            true,
            $authenticated,
            $resolved->isOwner,
            $context->isAdmin,
            'attachment:'.$ownerType.':'.$resolved->ownerField,
        );
    }

    private function resolveProjectOwnership(
        ProjectEntity $project,
        CrudOwnershipResolutionContextDTO $context,
        string $ownerType,
    ): CrudOwnershipDTO {
        if (null === $context->actor || !method_exists($context->actor, 'getId')) {
            return $this->unresolved(false, $context->isAdmin, $ownerType);
        }

        $actorId = $context->actor->getId();
        if (!is_int($actorId) && !is_string($actorId)) {
            return $this->unresolved(true, $context->isAdmin, $ownerType);
        }

        $manager = $this->managerRegistry->getManagerForClass(ProjectVendorBridgeEntity::class);
        if (null === $manager) {
            return $this->unresolved(true, $context->isAdmin, $ownerType);
        }

        $bridge = $manager->getRepository(ProjectVendorBridgeEntity::class)->findOneBy([
            'project' => $project,
            'vendorId' => (string) $actorId,
            'role' => 'owner',
        ]);

        return new CrudOwnershipDTO(
            true,
            true,
            null !== $bridge,
            $context->isAdmin,
            'attachment:'.$ownerType.':project_vendor_bridge',
        );
    }

    private function unresolved(bool $authenticated, bool $isAdmin, string $ownerType): CrudOwnershipDTO
    {
        return new CrudOwnershipDTO(true, $authenticated, false, $isAdmin, 'attachment:'.$ownerType);
    }

    private function sameIdentity(object $left, object $right): bool
    {
        if ($left === $right) {
            return true;
        }

        $leftClass = $left::class;
        $rightClass = $right::class;
        if (!is_a($left, $rightClass) && !is_a($right, $leftClass)) {
            return false;
        }

        if (!method_exists($left, 'getId') || !method_exists($right, 'getId')) {
            return false;
        }

        $leftId = $left->getId();
        $rightId = $right->getId();

        return (is_string($leftId) || is_int($leftId))
            && (is_string($rightId) || is_int($rightId))
            && (string) $leftId === (string) $rightId;
    }
}
