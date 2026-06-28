<?php

// Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Vendoring\Service\Runtime\Mobile;

use App\Vendoring\RepositoryInterface\Vendor\VendorPayoutRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class VendorMobilePayoutShowService
{
    public function __construct(private VendorPayoutRepositoryInterface $payoutRepository)
    {
    }

    public function __invoke(string $vendorId): JsonResponse
    {
        $normalizedVendorId = trim($vendorId);
        if ('' === $normalizedVendorId) {
            return new JsonResponse(['code' => 'invalid_vendor_id', 'message' => 'Vendor payout request requires a vendor id.'], 400);
        }
        $payouts = $this->payoutRepository->findBy(['vendorId' => $normalizedVendorId]);
        $currency = null;
        $availableCents = 0;
        $pendingCents = 0;
        $latestStatus = null;
        foreach ($payouts as $payout) {
            $currency = $this->stringProperty($payout, 'currency') ?? $currency;
            $status = $this->stringProperty($payout, 'status');
            $latestStatus ??= $status;
            $netCents = $this->integerProperty($payout, 'netCents');
            if ('processed' === $status) {
                $availableCents += $netCents;
                continue;
            }
            $pendingCents += $netCents;
        }

        return new JsonResponse(['data' => ['vendorId' => $normalizedVendorId, 'payoutStatus' => $latestStatus ?? 'unavailable', 'currency' => $currency ?? 'USD', 'availableAmount' => $availableCents / 100, 'pendingAmount' => $pendingCents / 100, 'payoutAccountLabel' => null, 'payoutCount' => count($payouts)]], 200);
    }

    private function stringProperty(object $object, string $property): ?string
    {
        if (!property_exists($object, $property)) {
            return null;
        }
        $value = $object->{$property};

        return is_scalar($value) && '' !== trim((string) $value) ? trim((string) $value) : null;
    }

    private function integerProperty(object $object, string $property): int
    {
        if (!property_exists($object, $property)) {
            return 0;
        }
        $value = $object->{$property};

        return is_int($value) ? $value : 0;
    }
}
