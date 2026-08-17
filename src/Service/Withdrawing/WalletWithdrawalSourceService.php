<?php

declare(strict_types=1);

namespace App\Service\Withdrawing;

use App\Walleting\Entity\Account;
use App\Walleting\Entity\LedgerTransaction;
use App\Walleting\Entity\Reservation;
use App\Walleting\Entity\Wallet;
use App\Walleting\Enum\AccountCategory;
use App\Walleting\Ledger\PostingInstruction;
use App\Walleting\Service\FinancialOperationService;
use App\Withdrawing\ServiceInterface\WithdrawalSourceServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class WalletWithdrawalSourceService implements WithdrawalSourceServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FinancialOperationService $financialOperations,
    ) {
    }

    public function supports(string $sourceType): bool
    {
        return 'wallet' === $sourceType;
    }

    public function reserve(string $sourceId, int $amountMinor, string $currency, string $idempotencyKey): string
    {
        $wallet = $this->wallet($sourceId);
        $existing = $this->entityManager->getRepository(Reservation::class)->findOneBy(['idempotencyKey' => $idempotencyKey]);
        if ($existing instanceof Reservation) {
            $this->assertReservation($existing, $wallet, $amountMinor, $currency);

            return $existing->id()->toRfc4122();
        }

        $asset = $this->account($wallet, $currency, AccountCategory::Asset);
        $reserve = $this->account($wallet, $currency, AccountCategory::Reserve);
        $reservation = $this->financialOperations->reserve($wallet, $reserve, $amountMinor, $currency, $idempotencyKey, [
            new PostingInstruction($asset, -$amountMinor),
            new PostingInstruction($reserve, $amountMinor),
        ]);

        return $reservation->id()->toRfc4122();
    }

    public function release(string $sourceId, string $reservationReference, string $idempotencyKey): void
    {
        if ($this->transactionByKey($idempotencyKey) instanceof LedgerTransaction) {
            return;
        }
        $wallet = $this->wallet($sourceId);
        $reservation = $this->reservation($reservationReference, $wallet);
        $asset = $this->account($wallet, $reservation->currency(), AccountCategory::Asset);
        $reserve = $reservation->account();
        $amountMinor = $reservation->amountMinor();

        $this->financialOperations->release($reservation, $idempotencyKey, [
            new PostingInstruction($reserve, -$amountMinor),
            new PostingInstruction($asset, $amountMinor),
        ]);
    }

    public function finalize(string $sourceId, string $reservationReference, string $idempotencyKey): void
    {
        if ($this->transactionByKey($idempotencyKey) instanceof LedgerTransaction) {
            return;
        }
        $wallet = $this->wallet($sourceId);
        $reservation = $this->reservation($reservationReference, $wallet);
        $reserve = $reservation->account();
        $clearing = $this->account($wallet, $reservation->currency(), AccountCategory::Clearing);
        $amountMinor = $reservation->amountMinor();

        $this->financialOperations->capture($reservation, $idempotencyKey, [
            new PostingInstruction($reserve, -$amountMinor),
            new PostingInstruction($clearing, $amountMinor),
        ]);
    }

    public function reverse(string $sourceId, string $reservationReference, string $idempotencyKey): void
    {
        if ($this->transactionByKey($idempotencyKey) instanceof LedgerTransaction) {
            return;
        }
        $wallet = $this->wallet($sourceId);
        $reservation = $this->reservation($reservationReference, $wallet);
        $transactionId = $this->entityManager->getConnection()->fetchOne(
            "SELECT result_transaction_id FROM financial_operation_link WHERE reservation_id = ? AND operation_type = 'capture' ORDER BY id DESC LIMIT 1",
            [$reservation->id()->toRfc4122()],
        );
        if (!is_string($transactionId) || '' === $transactionId) {
            throw new \LogicException('Withdrawal reservation has no captured ledger transaction to reverse.');
        }
        $original = $this->entityManager->find(LedgerTransaction::class, $transactionId);
        if (!$original instanceof LedgerTransaction) {
            throw new \LogicException('Withdrawal capture transaction cannot be resolved.');
        }

        $instructions = [];
        foreach ($original->postings() as $posting) {
            $instructions[] = new PostingInstruction($posting->account(), -$posting->amountMinor());
        }
        $this->financialOperations->reverse($original, $idempotencyKey, $instructions);
    }

    private function wallet(string $sourceId): Wallet
    {
        $wallet = $this->entityManager->find(Wallet::class, $sourceId);
        if (!$wallet instanceof Wallet) {
            throw new \DomainException('Withdrawal wallet source was not found.');
        }

        return $wallet;
    }

    private function reservation(string $reservationReference, Wallet $wallet): Reservation
    {
        $reservation = $this->entityManager->find(Reservation::class, $reservationReference);
        if (!$reservation instanceof Reservation || $reservation->account()->wallet() !== $wallet) {
            throw new \DomainException('Withdrawal reservation does not belong to the source wallet.');
        }

        return $reservation;
    }

    private function account(Wallet $wallet, string $currency, AccountCategory $category): Account
    {
        $accounts = $this->entityManager->getRepository(Account::class)->findBy([
            'wallet' => $wallet,
            'currency' => strtoupper(trim($currency)),
            'category' => $category,
        ]);
        if (1 !== count($accounts) || !$accounts[0] instanceof Account) {
            throw new \DomainException(sprintf('Wallet must expose exactly one %s account for %s withdrawal flow.', $category->value, strtoupper(trim($currency))));
        }

        return $accounts[0];
    }

    private function transactionByKey(string $idempotencyKey): ?LedgerTransaction
    {
        $transaction = $this->entityManager->getRepository(LedgerTransaction::class)->findOneBy(['idempotencyKey' => $idempotencyKey]);

        return $transaction instanceof LedgerTransaction ? $transaction : null;
    }

    private function assertReservation(Reservation $reservation, Wallet $wallet, int $amountMinor, string $currency): void
    {
        if ($reservation->account()->wallet() !== $wallet || $reservation->amountMinor() !== $amountMinor || $reservation->currency() !== strtoupper(trim($currency))) {
            throw new \DomainException('Withdrawal source idempotency key is already bound to a different wallet reservation.');
        }
    }
}
