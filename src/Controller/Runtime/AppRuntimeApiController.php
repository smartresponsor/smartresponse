<?php

declare(strict_types=1);

namespace App\Controller\Runtime;

use App\Service\Runtime\AppEffectiveRuntimeService;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class AppRuntimeApiController
{
    public function __construct(private AppEffectiveRuntimeService $runtimeService)
    {
    }

    public function __invoke(string $applicationKey, string $environment): JsonResponse
    {
        return new JsonResponse(
            $this->runtimeService->resolve($applicationKey, $environment)->toArray(),
            JsonResponse::HTTP_OK,
        );
    }
}
