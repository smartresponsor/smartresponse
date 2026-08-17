<?php

declare(strict_types=1);

namespace App\Service\Paying;

use App\Paying\ServiceInterface\PayoutRailInterface;
use App\Paying\Value\PayoutDestination;

final class DemoPayoutRailService implements PayoutRailInterface
{
    private const string REFERENCE_PREFIX = 'demo-payout:';

    public function supports(PayoutDestination $destination): bool
    {
        return 'demo' === $destination->provider;
    }

    public function submit(PayoutDestination $destination, int $amountMinor, string $currency, string $idempotencyKey): string
    {
        $currency = strtoupper(trim($currency));
        $idempotencyKey = trim($idempotencyKey);
        if (!$this->supports($destination) || $amountMinor <= 0 || 1 !== preg_match('/^[A-Z]{3}$/', $currency) || '' === $idempotencyKey) {
            throw new \InvalidArgumentException('Demo payout submission is invalid.');
        }

        return self::REFERENCE_PREFIX.substr(hash(
            'sha256',
            implode('|', [$destination->providerReference, (string) $amountMinor, $currency, $idempotencyKey]),
        ), 0, 32);
    }

    public function supportsReference(string $railReference): bool
    {
        return str_starts_with($railReference, self::REFERENCE_PREFIX);
    }

    public function reverse(string $railReference, string $idempotencyKey): void
    {
        if (!$this->supportsReference($railReference) || '' === trim($idempotencyKey)) {
            throw new \InvalidArgumentException('Demo payout reversal is invalid.');
        }
    }
}
