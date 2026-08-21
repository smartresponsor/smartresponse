<?php

declare(strict_types=1);

namespace App\Service\Paying;

use App\Paying\ServiceInterface\PayoutRailInterface;
use App\Paying\Value\PayoutDestination;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

final class StripePayoutRailService implements PayoutRailInterface
{
    private const string REFERENCE_PREFIX = 'stripe-payout:';

    public function __construct(private readonly string $secretKey)
    {
    }

    public function supports(PayoutDestination $destination): bool
    {
        return 'stripe' === $destination->provider
            && str_starts_with($destination->connectedAccountReference, 'acct_')
            && (str_starts_with($destination->providerReference, 'ba_') || str_starts_with($destination->providerReference, 'card_'));
    }

    public function submit(PayoutDestination $destination, int $amountMinor, string $currency, string $idempotencyKey): string
    {
        $this->assertConfigured();
        $currency = strtolower(trim($currency));
        $idempotencyKey = trim($idempotencyKey);
        if (!$this->supports($destination) || $amountMinor <= 0 || 1 !== preg_match('/^[a-z]{3}$/', $currency) || '' === $idempotencyKey) {
            throw new \InvalidArgumentException('Stripe payout submission is invalid.');
        }

        $stripe = new StripeClient($this->secretKey);
        $transfer = $stripe->transfers->create([
            'amount' => $amountMinor,
            'currency' => $currency,
            'destination' => $destination->connectedAccountReference,
            'metadata' => ['withdrawal_idempotency_key' => $idempotencyKey],
        ], ['idempotency_key' => $idempotencyKey.':transfer']);

        try {
            $payout = $stripe->payouts->create([
                'amount' => $amountMinor,
                'currency' => $currency,
                'destination' => $destination->providerReference,
                'metadata' => [
                    'platform_transfer_id' => $transfer->id,
                    'withdrawal_idempotency_key' => $idempotencyKey,
                ],
            ], [
                'stripe_account' => $destination->connectedAccountReference,
                'idempotency_key' => $idempotencyKey.':payout',
            ]);
        } catch (ApiErrorException $exception) {
            $this->reverseTransfer($stripe, $transfer->id, $idempotencyKey.':transfer-compensation');
            throw $exception;
        }

        return self::REFERENCE_PREFIX.$destination->connectedAccountReference.':'.$payout->id;
    }

    public function supportsReference(string $railReference): bool
    {
        try {
            $this->parseReference($railReference);

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    public function compensateFailure(string $railReference, string $idempotencyKey): void
    {
        $this->assertConfigured();
        [$accountReference, $payoutReference] = $this->parseReference($railReference);
        $stripe = new StripeClient($this->secretKey);
        $payout = $stripe->payouts->retrieve($payoutReference, [], ['stripe_account' => $accountReference]);
        $transferReference = trim((string) ($payout->metadata['platform_transfer_id'] ?? ''));
        if ('' === $transferReference) {
            throw new \DomainException('Stripe payout is missing its platform transfer reference.');
        }
        $this->reverseTransfer($stripe, $transferReference, trim($idempotencyKey));
    }

    public function reverse(string $railReference, string $idempotencyKey): void
    {
        $this->assertConfigured();
        $this->parseReference($railReference);

        throw new \DomainException('Stripe post-success payout reversal requires asynchronous reversal settlement and is not enabled yet.');
    }

    /** @return array{0:string,1:string} */
    private function parseReference(string $railReference): array
    {
        if (!str_starts_with($railReference, self::REFERENCE_PREFIX)) {
            throw new \InvalidArgumentException('Stripe payout rail reference is invalid.');
        }
        $parts = explode(':', substr($railReference, strlen(self::REFERENCE_PREFIX)), 2);
        if (2 !== count($parts) || !str_starts_with($parts[0], 'acct_') || !str_starts_with($parts[1], 'po_')) {
            throw new \InvalidArgumentException('Stripe payout rail reference is invalid.');
        }

        return [$parts[0], $parts[1]];
    }

    private function reverseTransfer(StripeClient $stripe, string $transferReference, string $idempotencyKey): void
    {
        $stripe->transfers->createReversal($transferReference, [], ['idempotency_key' => $idempotencyKey]);
    }

    private function assertConfigured(): void
    {
        if ('' === trim($this->secretKey)) {
            throw new \DomainException('Stripe payout rail is not configured: STRIPE_SECRET_KEY is missing.');
        }
    }
}
