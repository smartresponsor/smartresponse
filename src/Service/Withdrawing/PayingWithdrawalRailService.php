<?php

declare(strict_types=1);

namespace App\Service\Withdrawing;

use App\Paying\ServiceInterface\PayoutRailInterface;
use App\Paying\Value\PayoutDestination;
use App\Vendoring\Entity\Vendor\VendorPayoutAccountEntity;
use App\Walleting\Entity\PaymentInstrument;
use App\Walleting\Enum\PaymentInstrumentStatus;
use App\Withdrawing\ServiceInterface\WithdrawalRailServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

final class PayingWithdrawalRailService implements WithdrawalRailServiceInterface
{
    /** @var list<PayoutRailInterface> */
    private array $rails = [];

    /** @param iterable<PayoutRailInterface> $rails */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        iterable $rails,
    ) {
        foreach ($rails as $rail) {
            $this->rails[] = $rail;
        }
    }

    public function supports(string $destinationReference): bool
    {
        return str_starts_with($destinationReference, 'payment-instrument:');
    }

    public function submit(string $destinationReference, int $amountMinor, string $currency, string $idempotencyKey): string
    {
        $destination = $this->destination($destinationReference, $currency);
        foreach ($this->rails as $rail) {
            if ($rail->supports($destination)) {
                return $rail->submit($destination, $amountMinor, $currency, $idempotencyKey);
            }
        }

        throw new \DomainException(sprintf('No Paying payout rail supports provider "%s" and destination type "%s".', $destination->provider, $destination->type));
    }

    public function compensateFailure(string $railReference, string $idempotencyKey): void
    {
        foreach ($this->rails as $rail) {
            if ($rail->supportsReference($railReference)) {
                $rail->compensateFailure($railReference, $idempotencyKey);

                return;
            }
        }

        throw new \DomainException('No Paying payout rail supports the withdrawal rail reference.');
    }

    public function reverse(string $railReference, string $idempotencyKey): void
    {
        foreach ($this->rails as $rail) {
            if ($rail->supportsReference($railReference)) {
                $rail->reverse($railReference, $idempotencyKey);

                return;
            }
        }

        throw new \DomainException('No Paying payout rail supports the withdrawal rail reference.');
    }

    private function destination(string $destinationReference, string $currency): PayoutDestination
    {
        $id = substr($destinationReference, strlen('payment-instrument:'));
        if ('' === $id) {
            throw new \DomainException('Withdrawal payment instrument reference is invalid.');
        }

        $instrument = $this->entityManager->find(PaymentInstrument::class, $id);
        if (!$instrument instanceof PaymentInstrument || PaymentInstrumentStatus::Active !== $instrument->status()) {
            throw new \DomainException('Withdrawal payment instrument is unavailable.');
        }
        $wallet = $instrument->wallet();
        if ('vendor' !== $wallet->ownerType()) {
            throw new \DomainException('Withdrawal payout destinations require a vendor-owned wallet.');
        }

        $connectedAccountReference = 'demo';
        if ('demo' !== $instrument->provider()) {
            $accounts = $this->entityManager->getRepository(VendorPayoutAccountEntity::class)->findBy([
                'vendorId' => $wallet->ownerId(),
                'provider' => $instrument->provider(),
                'active' => true,
            ]);
            if (1 !== count($accounts) || !$accounts[0] instanceof VendorPayoutAccountEntity) {
                throw new \DomainException('Vendor must expose exactly one active payout account for the selected provider.');
            }
            $account = $accounts[0];
            if (strtoupper($account->currency) !== strtoupper(trim($currency))) {
                throw new \DomainException('Vendor payout account currency does not match the withdrawal currency.');
            }
            $connectedAccountReference = $account->accountRef;
        }

        return new PayoutDestination(
            $instrument->provider(),
            $instrument->type()->value,
            $connectedAccountReference,
            $instrument->providerReference(),
        );
    }
}
