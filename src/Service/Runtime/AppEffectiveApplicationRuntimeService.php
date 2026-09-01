<?php

declare(strict_types=1);

namespace App\Service\Runtime;

use App\Applicating\Enum\ApplicationRuntimeMode;
use App\Applicating\ServiceInterface\ApplicationRuntimeAssignmentResolverInterface;
use App\Domaining\ServiceInterface\Runtime\DomainRuntimeOverlayServiceInterface;
use App\Dto\Runtime\AppEffectiveApplicationRuntime;

final readonly class AppEffectiveApplicationRuntimeService
{
    public function __construct(
        private DomainRuntimeOverlayServiceInterface $domainRuntimeOverlayService,
        private ApplicationRuntimeAssignmentResolverInterface $runtimeAssignmentResolver,
        private string $centralHostOrigin,
    ) {
    }

    public function resolve(string $applicationKey, string $environment): AppEffectiveApplicationRuntime
    {
        $applicationKey = trim($applicationKey);
        $environment = trim($environment);
        if ('' === $applicationKey || '' === $environment) {
            throw new \InvalidArgumentException('Application key and environment must not be empty.');
        }

        $centralOrigin = $this->normalizeOrigin($this->centralHostOrigin);
        $overlay = $this->domainRuntimeOverlayService->forApplication($applicationKey, $environment);

        $customOrigin = null;
        if (
            $overlay->customDomainPublished
            && null !== $overlay->domainName
            && ApplicationRuntimeMode::CustomDomain === $this->runtimeAssignmentResolver->resolveMode($applicationKey, $environment)
        ) {
            $customOrigin = 'https://'.$overlay->domainName;
        }

        return new AppEffectiveApplicationRuntime(
            $applicationKey,
            $environment,
            null === $customOrigin ? 'host_shared' : 'custom_domain',
            $centralOrigin,
            $customOrigin ?? $centralOrigin,
            $centralOrigin,
            $overlay,
        );
    }

    private function normalizeOrigin(string $origin): string
    {
        $origin = rtrim(trim($origin), '/');
        if ('' === $origin || false === filter_var($origin, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException(sprintf('Central host origin "%s" is invalid.', $origin));
        }

        return $origin;
    }
}
