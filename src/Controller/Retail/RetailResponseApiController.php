<?php

declare(strict_types=1);

namespace App\Controller\Retail;

use App\Accessing\Entity\AccessEntity;
use App\Service\Retail\RetailResponseApiService;
use App\Vendoring\Entity\Vendor\VendorEntity;
use App\Vendoring\RepositoryInterface\Vendor\VendorRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class RetailResponseApiController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly VendorRepositoryInterface $vendorRepository,
        private readonly RetailResponseApiService $responses,
    ) {
    }

    #[Route('/api/retail/response/{retailId}', name: 'api_retail_response_save', requirements: ['retailId' => '[1-9][0-9]*'], methods: ['POST'])]
    public function save(string $retailId, Request $request): JsonResponse
    {
        $vendorId = $this->vendorId();
        if ($vendorId instanceof JsonResponse) {
            return $vendorId;
        }

        return $this->execute(fn (): array => $this->responses->saveDraft($retailId, $vendorId, $this->payload($request)));
    }

    #[Route('/api/retail/response/submit/{responseId}', name: 'api_retail_response_submit', requirements: ['responseId' => '[1-9][0-9]*'], methods: ['POST'])]
    public function submit(string $responseId): JsonResponse
    {
        $vendorId = $this->vendorId();
        if ($vendorId instanceof JsonResponse) {
            return $vendorId;
        }

        return $this->execute(fn (): array => $this->responses->submit($responseId, $vendorId));
    }

    #[Route('/api/retail/response/{retailId}', name: 'api_retail_response_list', requirements: ['retailId' => '[1-9][0-9]*'], methods: ['GET'])]
    public function responses(string $retailId): JsonResponse
    {
        $customerId = $this->customerId();
        if ($customerId instanceof JsonResponse) {
            return $customerId;
        }

        return $this->execute(fn (): array => ['items' => $this->responses->listForCustomer($retailId, $customerId)]);
    }

    #[Route('/api/retail/response/accept/{responseId}', name: 'api_retail_response_accept', requirements: ['responseId' => '[1-9][0-9]*'], methods: ['POST'])]
    public function accept(string $responseId): JsonResponse
    {
        $customerId = $this->customerId();
        if ($customerId instanceof JsonResponse) {
            return $customerId;
        }

        return $this->execute(fn (): array => $this->responses->accept($responseId, $customerId));
    }

    /** @param callable(): array<string, mixed> $operation */
    private function execute(callable $operation): JsonResponse
    {
        try {
            return $this->json(['data' => $operation()]);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['code' => 'invalid_retail_response', 'message' => $exception->getMessage()], 422);
        } catch (\DomainException $exception) {
            $status = str_contains(strtolower($exception->getMessage()), 'not found') ? 404 : 409;

            return $this->json(['code' => 'retail_response_rejected', 'message' => $exception->getMessage()], $status);
        }
    }

    private function customerId(): string|JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof AccessEntity || null === $user->getId()) {
            return $this->json(['code' => 'authentication_required'], 401);
        }

        return (string) $user->getId();
    }

    private function vendorId(): string|JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof AccessEntity || null === $user->getId()) {
            return $this->json(['code' => 'authentication_required'], 401);
        }
        $vendor = $this->vendorRepository->findOneBy(['ownerUserId' => $user->getId()]);
        if (!$vendor instanceof VendorEntity || null === $vendor->getId()) {
            return $this->json(['code' => 'vendor_not_found'], 404);
        }

        return (string) $vendor->getId();
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
