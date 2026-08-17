<?php

declare(strict_types=1);

namespace App\Service\Withdrawing;

use App\Paying\ServiceInterface\PayoutRailInterface;
use App\Paying\Value\PayoutDestination;
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
        $destination = $this->destination($destinationReference);
        foreach ($this->rails as $rail) {
            if ($rail->supports($destination)) {
                return $rail->submit($destination, $amountMinor, $currency, $idempotencyKey);
            }
        }

        throw new \DomainException(sprintf('No Paying payout rail supports provider "%s" and destination type "%s".', $destination->provider, $destination->type));
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

    private function destination(string $destinationReference): PayoutDestination
    {
        $id = substr($destinationReference, strlen('payment-instrument:'));
        if ('' === $id) {
            throw new \DomainException('Withdrawal payment instrument reference is invalid.');
        }

        $instrument = $this->entityManager->find(PaymentInstrument::class, $id);
        if (!$instrument instanceof PaymentInstrument || PaymentInstrumentStatus::Active !== $instrument->status()) {
            throw new \DomainException('Withdrawal payment instrument is unavailable.');
        }

        return new PayoutDestination(
            $instrument->provider(),
            $instrument->type()->value,
            $instrument->providerReference(),
        );
    }
}
