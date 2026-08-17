<?php

declare(strict_types=1);

namespace App\Controller\Retail;

use App\Accessing\Entity\AccessEntity;
use App\Service\Placement\AppRetailPlacementApiService;
use App\Vendoring\Entity\Vendor\VendorEntity;
use App\Vendoring\RepositoryInterface\Vendor\VendorRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ApiRetailPlacementController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly VendorRepositoryInterface $vendorRepository,
        private readonly AppRetailPlacementApiService $placement,
    ) {
    }

    #[Route('/api/retail/{retailId}/placement', name: 'api_retail_placement', requirements: ['retailId' => '[1-9][0-9]*'], methods: ['GET'])]
    public function snapshot(string $retailId): JsonResponse
    {
        return $this->execute($retailId, static fn (AppRetailPlacementApiService $service, string $vendorId, string $ownerUuid): array => $service->snapshot($retailId, $vendorId));
    }

    #[Route('/api/retail/{retailId}/fulfillment', name: 'api_retail_fulfillment', requirements: ['retailId' => '[1-9][0-9]*'], methods: ['POST'])]
    public function fulfillment(string $retailId, Request $request): JsonResponse
    {
        return $this->execute($retailId, fn (AppRetailPlacementApiService $service, string $vendorId, string $ownerUuid): array => $service->fulfillment($retailId, $vendorId, $this->payload($request)));
    }

    #[Route('/api/retail/{retailId}/location', name: 'api_retail_location', requirements: ['retailId' => '[1-9][0-9]*'], methods: ['POST'])]
    public function location(string $retailId, Request $request): JsonResponse
    {
        return $this->execute($retailId, fn (AppRetailPlacementApiService $service, string $vendorId, string $ownerUuid): array => $service->location($retailId, $vendorId, $ownerUuid, $this->payload($request)));
    }

    #[Route('/api/retail/{retailId}/pricing', name: 'api_retail_pricing', requirements: ['retailId' => '[1-9][0-9]*'], methods: ['POST'])]
    public function pricing(string $retailId, Request $request): JsonResponse
    {
        return $this->execute($retailId, fn (AppRetailPlacementApiService $service, string $vendorId, string $ownerUuid): array => $service->pricing($retailId, $vendorId, $this->payload($request)));
    }

    #[Route('/api/retail/{retailId}/publish', name: 'api_retail_publish', requirements: ['retailId' => '[1-9][0-9]*'], methods: ['POST'])]
    public function publish(string $retailId): JsonResponse
    {
        return $this->execute($retailId, static fn (AppRetailPlacementApiService $service, string $vendorId, string $ownerUuid): array => $service->publish($retailId, $vendorId));
    }

    /** @param callable(AppRetailPlacementApiService,string,string):array<string,mixed> $operation */
    private function execute(string $retailId, callable $operation): JsonResponse
    {
        $context = $this->actorContext();
        if ($context instanceof JsonResponse) {
            return $context;
        }
        [$vendorId, $ownerUuid] = $context;

        try {
            return $this->json(['data' => $operation($this->placement, $vendorId, $ownerUuid)]);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['code' => 'invalid_retail_placement', 'message' => $exception->getMessage()], 422);
        } catch (\DomainException $exception) {
            $status = str_contains(strtolower($exception->getMessage()), 'not found') ? 404 : 409;

            return $this->json(['code' => 'retail_placement_rejected', 'message' => $exception->getMessage()], $status);
        }
    }

    /** @return array{0:string,1:string}|JsonResponse */
    private function actorContext(): array|JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof AccessEntity || null === $user->getId()) {
            return $this->json(['code' => 'authentication_required'], 401);
        }
        $vendor = $this->vendorRepository->findOneBy(['ownerUserId' => $user->getId()]);
        if (!$vendor instanceof VendorEntity || null === $vendor->getId()) {
            return $this->json(['code' => 'vendor_not_found'], 404);
        }

        return [(string) $vendor->getId(), $user->getObjectUuid()];
    }

    /** @return array<string, mixed> */
    private function payload(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            throw new \InvalidArgumentException('Request body must be a JSON object.');
        }

        return $payload;
    }
}
