<?php

declare(strict_types=1);

namespace App\Service\Placement;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Service\Pricing\RetailPricingProfileNormalizer;
use App\Shipping\Service\Placement\ListingFulfillmentProfileNormalizer;
use Doctrine\Persistence\ManagerRegistry;

final readonly class AppRetailPlacementApiService
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private ListingFulfillmentProfileNormalizer $fulfillmentNormalizer,
        private RetailPricingProfileNormalizer $pricingNormalizer,
        private AppRetailLocationPlacementService $locationPlacement,
    ) {
    }

    /** @return array<string, mixed> */
    public function snapshot(string $retailId, string $vendorId): array
    {
        return $this->data($this->retail($retailId, $vendorId));
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function fulfillment(string $retailId, string $vendorId, array $input): array
    {
        $retail = $this->retail($retailId, $vendorId);
        $retail->setFulfillmentProfile($this->fulfillmentNormalizer->normalize($retail->getKind()->value, $input));
        $this->persist($retail);

        return $this->data($retail);
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function location(string $retailId, string $vendorId, string $ownerUuid, array $input): array
    {
        $retail = $this->retail($retailId, $vendorId);
        $this->locationPlacement->place($retail, $ownerUuid, $vendorId, $this->locationInput($input));

        return $this->data($retail);
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function pricing(string $retailId, string $vendorId, array $input): array
    {
        $retail = $this->retail($retailId, $vendorId);
        if (null === $retail->getFulfillmentProfile()) {
            throw new \DomainException('Fulfillment profile must be configured before pricing.');
        }
        if ($this->requiresExactLocation($retail) && null === $retail->getLocationProfile()) {
            throw new \DomainException('Exact listing location must be configured before pricing.');
        }

        $profile = $this->pricingNormalizer->normalize($retail->getKind()->value, $input);
        $retail->setPricingProfile($profile);
        if (isset($profile['amountMinor']) && is_int($profile['amountMinor'])) {
            $retail->setAmountMinor($profile['amountMinor']);
        }
        $retail->setCurrency((string) $profile['currency']);
        $this->persist($retail);

        return $this->data($retail);
    }

    /** @return array<string, mixed> */
    public function publish(string $retailId, string $vendorId): array
    {
        $retail = $this->retail($retailId, $vendorId);
        $retail->publish();
        $this->persist($retail);

        return $this->data($retail);
    }

    private function retail(string $retailId, string $vendorId): RetailEntity
    {
        if (!ctype_digit($retailId)) {
            throw new \DomainException('Retail listing was not found.');
        }
        $manager = $this->managerRegistry->getManagerForClass(RetailEntity::class) ?? $this->managerRegistry->getManager();
        $retail = $manager->getRepository(RetailEntity::class)->find((int) $retailId);
        if (!$retail instanceof RetailEntity || $retail->getOwner() !== $vendorId) {
            throw new \DomainException('Retail listing was not found in the current vendor scope.');
        }

        return $retail;
    }

    private function persist(RetailEntity $retail): void
    {
        $manager = $this->managerRegistry->getManagerForClass(RetailEntity::class) ?? $this->managerRegistry->getManager();
        $manager->persist($retail);
        $manager->flush();
    }

    /** @param array<string, mixed> $input
     * @return array{line1:string,line2:?string,city:string,region:?string,postalCode:?string,countryCode:string}
     */
    private function locationInput(array $input): array
    {
        $line1 = trim((string) ($input['line1'] ?? ''));
        $city = trim((string) ($input['city'] ?? ''));
        $countryCode = strtoupper(trim((string) ($input['countryCode'] ?? '')));
        if ('' === $line1 || '' === $city || 2 !== strlen($countryCode)) {
            throw new \InvalidArgumentException('Address, city, and two-letter countryCode are required.');
        }

        return [
            'line1' => $line1,
            'line2' => $this->nullable($input['line2'] ?? null),
            'city' => $city,
            'region' => $this->nullable($input['region'] ?? null),
            'postalCode' => $this->nullable($input['postalCode'] ?? null),
            'countryCode' => $countryCode,
        ];
    }

    private function requiresExactLocation(RetailEntity $retail): bool
    {
        $mode = $retail->getFulfillmentProfile()['mode'] ?? null;

        return match ($retail->getKind()->value) {
            'goods' => in_array($mode, ['shipping', 'pickup'], true),
            'task' => in_array($mode, ['onsite', 'hybrid'], true),
            default => false,
        };
    }

    /** @return array<string, mixed> */
    private function data(RetailEntity $retail): array
    {
        return [
            'retailId' => (string) $retail->getId(),
            'kind' => $retail->getKind()->value,
            'catalogCode' => $retail->getCatalogCode(),
            'categoryId' => $retail->getCategoryId(),
            'title' => $retail->getTitle(),
            'description' => $retail->getDescription(),
            'locationProfile' => $retail->getLocationProfile(),
            'fulfillmentProfile' => $retail->getFulfillmentProfile(),
            'pricingProfile' => $retail->getPricingProfile(),
            'amountMinor' => $retail->getAmountMinor(),
            'currency' => $retail->getCurrency(),
            'status' => $retail->getObjectStatus(),
            'requiresExactLocation' => $this->requiresExactLocation($retail),
            'nextStep' => $this->nextStep($retail),
        ];
    }

    private function nextStep(RetailEntity $retail): string
    {
        if (null === $retail->getFulfillmentProfile()) {
            return 'fulfillment';
        }
        if ($this->requiresExactLocation($retail) && null === $retail->getLocationProfile()) {
            return 'location';
        }
        if (null === $retail->getPricingProfile()) {
            return 'pricing';
        }

        return 'published' === $retail->getObjectStatus() ? 'complete' : 'review';
    }

    private function nullable(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return '' === $value ? null : $value;
    }
}
