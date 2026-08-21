<?php

declare(strict_types=1);

namespace App\Service\Withdrawing;

use App\Withdrawing\Entity\WithdrawalSettlementEvent;
use Doctrine\ORM\EntityManagerInterface;

final readonly class WithdrawalSettlementEventService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /** @return array{0:WithdrawalSettlementEvent,1:bool} */
    public function receive(string $provider, string $providerEventId, string $eventType, ?string $railReference, string $payloadHash): array
    {
        $provider = strtolower(trim($provider));
        $providerEventId = trim($providerEventId);
        $existing = $this->entityManager->getRepository(WithdrawalSettlementEvent::class)->findOneBy([
            'provider' => $provider,
            'providerEventId' => $providerEventId,
        ]);
        if ($existing instanceof WithdrawalSettlementEvent) {
            if (!hash_equals($existing->payloadHash(), strtolower(trim($payloadHash)))) {
                throw new \DomainException('Provider settlement event payload changed for an existing event identity.');
            }

            return [$existing, in_array($existing->outcome(), ['received', 'failed'], true)];
        }

        $event = new WithdrawalSettlementEvent($provider, $providerEventId, $eventType, $railReference, $payloadHash);
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return [$event, true];
    }

    public function processed(WithdrawalSettlementEvent $event, ?string $failureCode = null, ?string $failureMessage = null): void
    {
        $event->markProcessed($failureCode, $failureMessage);
        $this->entityManager->flush();
    }

    public function ignored(WithdrawalSettlementEvent $event): void
    {
        $event->markIgnored();
        $this->entityManager->flush();
    }

    public function failed(WithdrawalSettlementEvent $event, string $code, string $message): void
    {
        $event->markFailed($code, $message);
        $this->entityManager->flush();
    }
}
