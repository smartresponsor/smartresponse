<?php

declare(strict_types=1);

namespace App\Service\Retail;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Entity\Retail\RetailResponseEntity;
use App\Retailing\Service\Marketplace\RetailResponseAcceptanceService;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

final readonly class AppRetailResponseApiService
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private RetailResponseAcceptanceService $acceptanceService,
    ) {
    }

    /** @param array<string, mixed> $input */
    public function saveDraft(string $retailId, string $vendorId, array $input): array
    {
        $retail = $this->customerRequest($retailId);
        $manager = $this->manager();
        $response = $manager->getRepository(RetailResponseEntity::class)->findOneBy([
            'retail' => $retail->getId(),
            'vendorId' => $vendorId,
        ]);
        if (!$response instanceof RetailResponseEntity) {
            $response = new RetailResponseEntity($retail, $vendorId);
        }
        if ('draft' !== $response->getStatus()) {
            throw new \DomainException('Only a draft retail response can be changed.');
        }

        if (array_key_exists('serviceId', $input)) {
            $response->setService($this->vendorService($input['serviceId'], $vendorId, $retail));
        }
        if (array_key_exists('description', $input)) {
            $response->setDescription($this->nullableString($input['description']));
        }
        if (isset($input['pricingProfile']) && is_array($input['pricingProfile'])) {
            $response->setPricingProfile($this->pricingProfile($input['pricingProfile'], $retail->getCurrency()));
        }
        foreach (['fulfillmentProfile', 'availabilityProfile', 'locationProfile'] as $key) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            $profile = $this->nullableProfile($input[$key]);
            match ($key) {
                'fulfillmentProfile' => $response->setFulfillmentProfile($profile),
                'availabilityProfile' => $response->setAvailabilityProfile($profile),
                'locationProfile' => $response->setLocationProfile($profile),
            };
        }

        $manager->persist($response);
        $manager->flush();

        return $this->data($response);
    }

    public function submit(string $responseId, string $vendorId): array
    {
        $response = $this->response($responseId);
        if ($response->getVendorId() !== $vendorId) {
            throw new \DomainException('Retail response was not found in the current vendor scope.');
        }
        $response->submit();
        $this->persist($response);

        return $this->data($response);
    }

    /** @return list<array<string, mixed>> */
    public function listForCustomer(string $retailId, string $customerId): array
    {
        $retail = $this->customerRequest($retailId);
        if ($retail->getOwner() !== $customerId) {
            throw new \DomainException('Customer retail request was not found in the current owner scope.');
        }
        $responses = $this->manager()->getRepository(RetailResponseEntity::class)->findBy(
            ['retail' => $retail->getId()],
            ['createdAt' => 'ASC'],
        );

        return array_values(array_map(
            fn (RetailResponseEntity $response): array => $this->data($response),
            array_filter($responses, static fn (mixed $response): bool => $response instanceof RetailResponseEntity && 'draft' !== $response->getStatus()),
        ));
    }

    public function accept(string $responseId, string $customerId): array
    {
        $response = $this->response($responseId);
        $retail = $response->getRetail();
        if ($retail->getOwner() !== $customerId) {
            throw new \DomainException('Retail response was not found in the current customer scope.');
        }

        $retail = $this->acceptanceService->accept($response);

        return [
            'retailId' => (string) $retail->getId(),
            'response' => $this->data($response),
            'selectionProfile' => $retail->getSelectionProfile(),
        ];
    }

    private function customerRequest(string $retailId): RetailEntity
    {
        if (!ctype_digit($retailId)) {
            throw new \DomainException('Customer retail request was not found.');
        }
        $retail = $this->manager()->getRepository(RetailEntity::class)->find((int) $retailId);
        if (!$retail instanceof RetailEntity || 'access' !== $retail->getOwnerType() || !in_array($retail->getKind()->value, ['task', 'project'], true)) {
            throw new \DomainException('Customer retail request was not found.');
        }
        if ('published' !== $retail->getObjectStatus()) {
            throw new \DomainException('Vendor responses require a published customer retail request.');
        }

        return $retail;
    }

    private function response(string $responseId): RetailResponseEntity
    {
        if (!ctype_digit($responseId)) {
            throw new \DomainException('Retail response was not found.');
        }
        $response = $this->manager()->getRepository(RetailResponseEntity::class)->find((int) $responseId);
        if (!$response instanceof RetailResponseEntity) {
            throw new \DomainException('Retail response was not found.');
        }

        return $response;
    }

    private function vendorService(mixed $serviceId, string $vendorId, RetailEntity $request): ?RetailEntity
    {
        if (null === $serviceId || '' === trim((string) $serviceId)) {
            return null;
        }
        $id = trim((string) $serviceId);
        if (!ctype_digit($id) || (int) $id <= 0) {
            throw new \InvalidArgumentException('serviceId must be a positive numeric identifier.');
        }
        $service = $this->manager()->getRepository(RetailEntity::class)->find((int) $id);
        if (!$service instanceof RetailEntity || 'service' !== $service->getKind()->value || 'vendor' !== $service->getOwnerType() || $service->getOwner() !== $vendorId || $service->getTypePath() !== $request->getTypePath()) {
            throw new \DomainException('Marketplace service was not found in the current vendor and category scope.');
        }

        return $service;
    }

    /** @param array<string, mixed> $profile */
    private function pricingProfile(array $profile, string $currency): array
    {
        foreach (['amountMinor', 'minimumAmountMinor', 'maximumAmountMinor', 'hourlyAmountMinor', 'depositAmountMinor'] as $key) {
            if (!array_key_exists($key, $profile) || null === $profile[$key] || '' === $profile[$key]) {
                continue;
            }
            if (!is_numeric($profile[$key]) || (int) $profile[$key] < 0) {
                throw new \InvalidArgumentException(sprintf('%s must be a non-negative amount in minor units.', $key));
            }
            $profile[$key] = (int) $profile[$key];
        }
        $profile['currency'] = strtoupper(trim((string) ($profile['currency'] ?? $currency)));

        return $profile;
    }

    private function nullableProfile(mixed $value): ?array
    {
        if (null === $value || [] === $value) {
            return null;
        }
        if (!is_array($value)) {
            throw new \InvalidArgumentException('Profile value must be a JSON object or null.');
        }

        return $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return '' === $value ? null : $value;
    }

    private function persist(RetailResponseEntity $response): void
    {
        $manager = $this->manager();
        $manager->persist($response);
        $manager->flush();
    }

    private function manager(): ObjectManager
    {
        return $this->managerRegistry->getManagerForClass(RetailResponseEntity::class) ?? $this->managerRegistry->getManager();
    }

    private function data(RetailResponseEntity $response): array
    {
        return [
            'responseId' => (string) $response->getId(),
            'retailId' => (string) $response->getRetail()->getId(),
            'vendorId' => $response->getVendorId(),
            'serviceId' => null === $response->getServiceId() ? null : (string) $response->getServiceId(),
            'status' => $response->getStatus(),
            'description' => $response->getDescription(),
            'pricingProfile' => $response->getPricingProfile(),
            'fulfillmentProfile' => $response->getFulfillmentProfile(),
            'availabilityProfile' => $response->getAvailabilityProfile(),
            'locationProfile' => $response->getLocationProfile(),
            'submittedAt' => $response->getSubmittedAt()?->format(DATE_ATOM),
            'acceptedAt' => $response->getAcceptedAt()?->format(DATE_ATOM),
        ];
    }
}
