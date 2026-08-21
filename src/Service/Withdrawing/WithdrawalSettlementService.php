<?php

declare(strict_types=1);

namespace App\Service\Withdrawing;

use App\Withdrawing\Entity\Withdrawal;
use App\Withdrawing\Service\WithdrawalApplicationService;
use Doctrine\ORM\EntityManagerInterface;

final readonly class WithdrawalSettlementService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WithdrawalApplicationService $withdrawing,
    ) {
    }

    public function succeed(string $railReference): Withdrawal
    {
        $withdrawal = $this->withdrawal($railReference);
        $this->withdrawing->succeed($withdrawal);

        return $withdrawal;
    }

    public function fail(string $railReference, string $reason): Withdrawal
    {
        $withdrawal = $this->withdrawal($railReference);
        $this->withdrawing->fail($withdrawal);

        return $withdrawal;
    }

    public function reverse(string $railReference): Withdrawal
    {
        $withdrawal = $this->withdrawal($railReference);
        $this->withdrawing->reverse($withdrawal);

        return $withdrawal;
    }

    private function withdrawal(string $railReference): Withdrawal
    {
        $railReference = trim($railReference);
        if ('' === $railReference) {
            throw new \InvalidArgumentException('Withdrawal rail reference must not be empty.');
        }

        $matches = $this->entityManager->getRepository(Withdrawal::class)->findBy(['railReference' => $railReference]);
        if (1 !== count($matches) || !$matches[0] instanceof Withdrawal) {
            throw new \DomainException('Withdrawal rail reference must resolve to exactly one withdrawal.');
        }

        return $matches[0];
    }
}
