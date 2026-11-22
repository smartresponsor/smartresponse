<?php

declare(strict_types=1);

namespace App\Dto\Runtime;

use App\Domaining\DTO\DomainRuntimeOverlayDTO;

final readonly class AppEffectiveRuntime
{
    public function __construct(
        public string $applicationKey,
        public string $environment,
        public string $runtimeMode,
        public string $bootstrapOrigin,
        public string $effectiveOrigin,
        public string $fallbackOrigin,
        public DomainRuntimeOverlayDTO $domainOverlay,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'applicationKey' => $this->applicationKey,
            'environment' => $this->environment,
            'runtimeMode' => $this->runtimeMode,
            'bootstrapOrigin' => $this->bootstrapOrigin,
            'effectiveOrigin' => $this->effectiveOrigin,
            'fallbackOrigin' => $this->fallbackOrigin,
            'domain' => $this->domainOverlay->toArray(),
        ];
    }
}
