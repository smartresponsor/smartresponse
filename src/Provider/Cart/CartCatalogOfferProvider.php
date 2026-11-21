<?php

declare(strict_types=1);

namespace App\Provider\Cart;

use App\Carting\DTO\CartAvailabilityResultDTO;
use App\Carting\ServiceInterface\CartAvailabilityCheckerInterface;
use App\Carting\ServiceInterface\CartOfferProviderInterface;
use App\Carting\Snapshot\CartOfferSnapshot;
use App\Cataloging\RepositoryInterface\Catalog\CatalogCollectionProjectionRepositoryInterface;
use App\Currencing\ServiceInterface\CurrencyPrecisionResolverInterface;

final readonly class CartCatalogOfferProvider implements CartOfferProviderInterface, CartAvailabilityCheckerInterface
{
    public function __construct(
        private CatalogCollectionProjectionRepositoryInterface $catalogRepository,
        private CurrencyPrecisionResolverInterface $currencyPrecisionResolver,
    ) {
    }

    public function provideOfferSnapshot(string $offerReference, int $quantity): CartOfferSnapshot
    {
        $offer = $this->catalogRepository->find($offerReference);
        if (null === $offer) {
            throw new \LogicException(sprintf('Catalog offer "%s" was not found.', $offerReference));
        }

        $price = $offer['price'] ?? null;
        if (!is_float($price)) {
            throw new \LogicException(sprintf('Catalog offer "%s" does not have an authoritative price.', $offerReference));
        }

        if (!$this->checkAvailability($offerReference, $quantity)->available) {
            throw new \LogicException(sprintf('Catalog offer "%s" is unavailable in quantity %d.', $offerReference, $quantity));
        }

        $brand = trim((string) ($offer['brand'] ?? ''));
        $title = '' !== $brand ? sprintf('%s — %s', $brand, $offerReference) : $offerReference;
        $currencyCode = strtoupper(trim((string) 'USD'));
        $factor = $this->currencyPrecisionResolver->factorFor($currencyCode);

        return new CartOfferSnapshot(
            offerReference: $offerReference,
            title: $title,
            unitPriceMinor: (int) round(((float) $price) * $factor),
            currencyCode: $currencyCode,
            metadata: [
                'catalogRecordId' => $offerReference,
                'brand' => '' !== $brand ? $brand : null,
                'source' => 'cataloging.record_index',
            ],
        );
    }

    public function checkAvailability(string $offerReference, int $quantity): CartAvailabilityResultDTO
    {
        if ($quantity < 1) {
            return new CartAvailabilityResultDTO(false, null, $offerReference);
        }

        $offer = $this->catalogRepository->find($offerReference);
        if (null === $offer || null === ($offer['price'] ?? null)) {
            return new CartAvailabilityResultDTO(false, null, $offerReference);
        }

        $stock = $offer['stock'] ?? null;
        $availableQuantity = null === $stock ? null : max(0, $stock);
        $available = null === $stock || $stock >= $quantity;

        return new CartAvailabilityResultDTO($available, $availableQuantity, $offerReference);
    }

    public function isAvailable(string $offerReference, int $quantity): bool
    {
        return $this->checkAvailability($offerReference, $quantity)->available;
    }
}
