<?php

declare(strict_types=1);

namespace App\Tests\Unit\Runtime;

use App\Applicating\Enum\ApplicationRuntimeMode;
use App\Applicating\ServiceInterface\ApplicationRuntimeAssignmentResolverInterface;
use App\Domaining\Dto\DomainRuntimeOverlay;
use App\Domaining\ServiceInterface\Runtime\DomainRuntimeOverlayServiceInterface;
use App\Service\Runtime\AppEffectiveRuntimeService;
use PHPUnit\Framework\TestCase;

final class AppEffectiveRuntimeServiceTest extends TestCase
{
    public function testSharedHostRemainsEffectiveUntilPublishedCustomDomainIsAssigned(): void
    {
        $overlayService = $this->createMock(DomainRuntimeOverlayServiceInterface::class);
        $overlayService->expects(self::once())
            ->method('forApplication')
            ->with('one_tasker', 'production')
            ->willReturn($this->overlay(false, 'app.example.com'));

        $assignmentResolver = $this->createMock(ApplicationRuntimeAssignmentResolverInterface::class);
        $assignmentResolver->expects(self::never())->method('resolveMode');

        $service = new AppEffectiveRuntimeService(
            $overlayService,
            $assignmentResolver,
            'https://host.example.com/',
        );

        $runtime = $service->resolve('one_tasker', 'production');

        self::assertSame('host_shared', $runtime->runtimeMode);
        self::assertSame('https://host.example.com', $runtime->bootstrapOrigin);
        self::assertSame('https://host.example.com', $runtime->effectiveOrigin);
        self::assertSame('https://host.example.com', $runtime->fallbackOrigin);
    }

    public function testPublishedCustomDomainBecomesEffectiveOnlyWhenApplicatingAssignsCustomDomainRuntime(): void
    {
        $overlayService = $this->createMock(DomainRuntimeOverlayServiceInterface::class);
        $overlayService->expects(self::once())
            ->method('forApplication')
            ->with('one_tasker', 'production')
            ->willReturn($this->overlay(true, 'app.example.com'));

        $assignmentResolver = $this->createMock(ApplicationRuntimeAssignmentResolverInterface::class);
        $assignmentResolver->expects(self::once())
            ->method('resolveMode')
            ->with('one_tasker', 'production')
            ->willReturn(ApplicationRuntimeMode::CustomDomain);

        $service = new AppEffectiveRuntimeService(
            $overlayService,
            $assignmentResolver,
            'https://host.example.com',
        );

        $runtime = $service->resolve('one_tasker', 'production');

        self::assertSame('custom_domain', $runtime->runtimeMode);
        self::assertSame('https://app.example.com', $runtime->effectiveOrigin);
        self::assertSame('https://host.example.com', $runtime->fallbackOrigin);
    }

    private function overlay(bool $published, ?string $domainName): DomainRuntimeOverlay
    {
        return new DomainRuntimeOverlay(
            'one_tasker',
            null,
            'production',
            $domainName,
            null,
            null,
            null,
            null,
            null,
            null,
            null !== $domainName,
            $published,
            $published,
            $published,
        );
    }
}
