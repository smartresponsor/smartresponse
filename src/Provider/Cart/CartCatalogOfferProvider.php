<?php

declare(strict_types=1);

namespace App\Provider\Cart;

use App\Carting\ServiceInterface\Cart\CartAvailabilityCheckerInterface;
use App\Carting\ServiceInterface\Cart\CartOfferProviderInterface;
use App\Carting\Snapshot\Cart\CartOfferSnapshot;
use App\Cataloging\RepositoryInterface\Catalog\CatalogCollectionProjectionRepositoryInterface;

final readonly class CartCatalogOfferProvider implements CartOfferProviderInterface, CartAvailabilityCheckerInterface
{
    public function __construct(
        private CatalogCollectionProjectionRepositoryInterface $catalogRepository,
    ) {
    }

    public function provideOfferSnapshot(string $offerReference, int $quantity): CartOfferSnapshot
    {
        $offer = $this->catalogRepository->find($offerReference);
        if (null === $offer) {
            throw new \LogicException(sprintf('Catalog offer "%s" was not found.', $offerReference));
        }

        $price = $offer['price'] ?? null;
        if (!is_float($price) && !is_int($price)) {
            throw new \LogicException(sprintf('Catalog offer "%s" does not have an authoritative price.', $offerReference));
        }

        if (!$this->isAvailable($offerReference, $quantity)) {
            throw new \LogicException(sprintf('Catalog offer "%s" is unavailable in quantity %d.', $offerReference, $quantity));
        }

        $brand = trim((string) ($offer['brand'] ?? ''));
        $title = '' !== $brand ? sprintf('%s — %s', $brand, $offerReference) : $offerReference;

        return new CartOfferSnapshot(
            offerReference: $offerReference,
            title: $title,
            unitPriceMinor: (int) round(((float) $price) * 100),
            currencyCode: 'USD',
            metadata: [
                'catalogRecordId' => $offerReference,
                'brand' => '' !== $brand ? $brand : null,
                'source' => 'cataloging.record_index',
            ],
        );
    }

    public function isAvailable(string $offerReference, int $quantity): bool
    {
        if ($quantity < 1) {
            return false;
        }

        $offer = $this->catalogRepository->find($offerReference);
        if (null === $offer || null === ($offer['price'] ?? null)) {
            return false;
        }

        $stock = $offer['stock'] ?? null;

        return null === $stock || (is_int($stock) && $stock >= $quantity);
    }
}
