<?php

declare(strict_types=1);

namespace App\Tests\Unit\Crud;

use App\Attaching\Entity\Attachment\AttachmentLinkEntity;
use App\Cruding\DTO\CrudOwnershipResolutionContextDTO;
use App\Cruding\Provider\CrudDefaultOwnershipProvider;
use App\Projecting\Entity\Project\ProjectEntity;
use App\Projecting\Entity\Project\ProjectVendorBridgeEntity;
use App\Provider\Crud\AttachmentLinkOwnershipProvider;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;

final class AttachmentLinkOwnershipProviderTest extends TestCase
{
    public function testVendorOwnedLinkMatchesCurrentVendorDirectly(): void
    {
        $actor = new AttachmentOwnershipActor(41);
        $provider = $this->provider(['vendor' => AttachmentOwnershipActor::class], $actor);

        $ownership = $provider->resolve($this->context($this->link('vendor', '41'), $actor));

        self::assertTrue($ownership->supportsOwnership);
        self::assertTrue($ownership->isOwner);
        self::assertTrue($ownership->canMutate());
    }

    public function testReferencedOwnerCanDelegateToDefaultCrudOwnershipSemantics(): void
    {
        $actor = new AttachmentOwnershipActor(41);
        $provider = $this->provider(['product' => AttachmentOwnedResource::class], new AttachmentOwnedResource('41'));

        $ownership = $provider->resolve($this->context($this->link('product', '7'), $actor));

        self::assertTrue($ownership->isOwner);
    }

    public function testReferencedOwnerBelongingToAnotherVendorIsDenied(): void
    {
        $actor = new AttachmentOwnershipActor(41);
        $provider = $this->provider(['product' => AttachmentOwnedResource::class], new AttachmentOwnedResource('99'));

        $ownership = $provider->resolve($this->context($this->link('product', '7'), $actor));

        self::assertFalse($ownership->isOwner);
        self::assertFalse($ownership->canMutate());
    }

    public function testMissingOwnerFailsClosed(): void
    {
        $actor = new AttachmentOwnershipActor(41);
        $provider = $this->provider(['product' => AttachmentOwnedResource::class], null);

        $ownership = $provider->resolve($this->context($this->link('product', '404'), $actor));

        self::assertTrue($ownership->supportsOwnership);
        self::assertFalse($ownership->isOwner);
    }

    public function testUnknownOwnerTypeFailsClosed(): void
    {
        $actor = new AttachmentOwnershipActor(41);
        $provider = $this->provider([], null);

        $ownership = $provider->resolve($this->context($this->link('unknown', '1'), $actor));

        self::assertTrue($ownership->supportsOwnership);
        self::assertFalse($ownership->isOwner);
    }

    public function testStaleMappedClassFailsClosed(): void
    {
        $actor = new AttachmentOwnershipActor(41);
        $provider = $this->provider(['product' => AttachmentOwnedResource::class.'Missing'], null);

        $ownership = $provider->resolve($this->context($this->link('product', '1'), $actor));

        self::assertTrue($ownership->supportsOwnership);
        self::assertFalse($ownership->isOwner);
    }

    public function testResolvedOwnerWithoutCrudOwnershipSemanticFailsClosed(): void
    {
        $actor = new AttachmentOwnershipActor(41);
        $provider = $this->provider(['project' => AttachmentOwnerWithoutOwnershipSemantic::class], new AttachmentOwnerWithoutOwnershipSemantic(7));

        $ownership = $provider->resolve($this->context($this->link('project', '7'), $actor));

        self::assertTrue($ownership->supportsOwnership);
        self::assertFalse($ownership->isOwner);
        self::assertFalse($ownership->canMutate());
    }

    public function testDifferentEntityWithSameIdentifierDoesNotMatchActorIdentity(): void
    {
        $actor = new AttachmentOwnershipActor(41);
        $provider = $this->provider(
            ['category' => AttachmentOwnerWithoutOwnershipSemantic::class],
            new AttachmentOwnerWithoutOwnershipSemantic(41),
        );

        $ownership = $provider->resolve($this->context($this->link('category', '41'), $actor));

        self::assertFalse($ownership->isOwner);
        self::assertFalse($ownership->canMutate());
    }

    public function testProjectAttachmentUsesProjectVendorOwnerBridge(): void
    {
        $actor = new AttachmentOwnershipActor(41);
        $project = new ProjectEntity('Owned project');
        $provider = $this->projectProvider($project, new \stdClass());

        $ownership = $provider->resolve($this->context($this->link('project', '7'), $actor));

        self::assertTrue($ownership->supportsOwnership);
        self::assertTrue($ownership->isOwner);
        self::assertTrue($ownership->canMutate());
    }

    public function testProjectAttachmentWithoutVendorOwnerBridgeFailsClosed(): void
    {
        $actor = new AttachmentOwnershipActor(41);
        $project = new ProjectEntity('Foreign project');
        $provider = $this->projectProvider($project, null);

        $ownership = $provider->resolve($this->context($this->link('project', '7'), $actor));

        self::assertTrue($ownership->supportsOwnership);
        self::assertFalse($ownership->isOwner);
        self::assertFalse($ownership->canMutate());
    }

    public function testAdminRetainsExistingCrudAdminOverrideEvenWhenOwnerCannotBeResolved(): void
    {
        $actor = new AttachmentOwnershipActor(41);
        $provider = $this->provider([], null);

        $ownership = $provider->resolve($this->context($this->link('unknown', '1'), $actor, true));

        self::assertFalse($ownership->isOwner);
        self::assertTrue($ownership->isAdmin);
        self::assertTrue($ownership->canMutate());
    }

    /** @param array<string, string> $ownerClassByType */
    private function provider(array $ownerClassByType, ?object $resolvedOwner): AttachmentLinkOwnershipProvider
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('find')->willReturn($resolvedOwner);

        $manager = $this->createMock(ObjectManager::class);
        $manager->method('getRepository')->willReturn($repository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        return new AttachmentLinkOwnershipProvider(
            $registry,
            new CrudDefaultOwnershipProvider(),
            $ownerClassByType,
        );
    }

    private function projectProvider(ProjectEntity $project, ?object $bridge): AttachmentLinkOwnershipProvider
    {
        $projectRepository = $this->createMock(ObjectRepository::class);
        $projectRepository->expects(self::once())->method('find')->with('7')->willReturn($project);

        $bridgeRepository = $this->createMock(ObjectRepository::class);
        $bridgeRepository->expects(self::once())
            ->method('findOneBy')
            ->with([
                'project' => $project,
                'vendorId' => '41',
                'role' => 'owner',
            ])
            ->willReturn($bridge);

        $projectManager = $this->createMock(ObjectManager::class);
        $projectManager->method('getRepository')->with(ProjectEntity::class)->willReturn($projectRepository);

        $bridgeManager = $this->createMock(ObjectManager::class);
        $bridgeManager->method('getRepository')->with(ProjectVendorBridgeEntity::class)->willReturn($bridgeRepository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturnMap([
            [ProjectEntity::class, $projectManager],
            [ProjectVendorBridgeEntity::class, $bridgeManager],
        ]);

        return new AttachmentLinkOwnershipProvider(
            $registry,
            new CrudDefaultOwnershipProvider(),
            ['project' => ProjectEntity::class],
        );
    }

    private function link(string $ownerType, string $ownerId): AttachmentLinkEntity
    {
        $link = $this->createMock(AttachmentLinkEntity::class);
        $link->method('getOwnerType')->willReturn($ownerType);
        $link->method('getOwnerId')->willReturn($ownerId);

        return $link;
    }

    private function context(AttachmentLinkEntity $link, object $actor, bool $isAdmin = false): CrudOwnershipResolutionContextDTO
    {
        return new CrudOwnershipResolutionContextDTO($link, $actor, $isAdmin);
    }
}

final readonly class AttachmentOwnershipActor
{
    public function __construct(private int $id)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }
}

final readonly class AttachmentOwnedResource
{
    public function __construct(private string $owner)
    {
    }

    public function getOwner(): string
    {
        return $this->owner;
    }
}

final readonly class AttachmentOwnerWithoutOwnershipSemantic
{
    public function __construct(private int $id)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
