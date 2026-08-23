<?php

declare(strict_types=1);

namespace App\Service\Fact;

use App\Accessing\Entity\AccessEntity;
use App\Facting\Fact\FactStream;
use App\Ordering\Entity\Order\OrderEntity;
use App\Ordering\Event\Domain\Order\OrderPaidEvent;
use App\Ordering\Event\Domain\Order\OrderPlacedEvent;
use App\ServiceInterface\Fact\FactSubjectCommitterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class AppOrderFactSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FactSubjectCommitterInterface $factCommitter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderPlacedEvent::class => 'onOrderPlaced',
            OrderPaidEvent::class => 'onOrderPaid',
        ];
    }

    public function onOrderPlaced(OrderPlacedEvent $event): void
    {
        $order = $this->findOrder($event->orderId);
        if (!$order instanceof OrderEntity) {
            return;
        }

        $subjectIdentifier = $this->resolveSubjectIdentifier($order->getCustomerId());
        if (null === $subjectIdentifier) {
            return;
        }

        $this->factCommitter->commit(
            new FactStream($order->slug(), 'order'),
            'order.created',
            [
                'orderId' => $order->slug(),
                'number' => $order->getNumber(),
                'currency' => $order->getCurrency(),
                'grandTotal' => $order->getGrandTotal(),
                'vendorId' => $order->getVendorId(),
            ],
            $subjectIdentifier,
            sprintf('Order %s was created.', $order->getNumber()),
            ['source' => 'ordering'],
            sprintf('ordering:order:%s:placed', $order->slug()),
            occurredAt: $order->getCreatedAt(),
            actor: 'ordering:service',
        );
    }

    public function onOrderPaid(OrderPaidEvent $event): void
    {
        $externalRef = trim($event->externalRef);
        if ('' === $externalRef) {
            return;
        }

        $order = $this->findOrder($event->orderId);
        if (!$order instanceof OrderEntity) {
            return;
        }

        $subjectIdentifier = $this->resolveSubjectIdentifier($order->getCustomerId());
        if (null === $subjectIdentifier) {
            return;
        }

        $this->factCommitter->commit(
            new FactStream($order->slug(), 'order'),
            'order.paid',
            [
                'orderId' => $order->slug(),
                'number' => $order->getNumber(),
                'amount' => $event->amount,
                'currency' => $event->currency,
                'externalRef' => $externalRef,
            ],
            $subjectIdentifier,
            sprintf('Payment of %s %s recorded for order %s.', $event->amount, $event->currency, $order->getNumber()),
            ['source' => 'ordering'],
            sprintf('ordering:order:%s:payment:%s', $order->slug(), $externalRef),
            occurredAt: $order->getUpdatedAt(),
            actor: 'ordering:service',
        );
    }

    private function findOrder(string|int $orderIdentifier): ?OrderEntity
    {
        $repository = $this->entityManager->getRepository(OrderEntity::class);
        if (is_int($orderIdentifier) || ctype_digit((string) $orderIdentifier)) {
            $order = $repository->find((int) $orderIdentifier);
            if ($order instanceof OrderEntity) {
                return $order;
            }
        }

        $order = $repository->findOneBy(['slug' => (string) $orderIdentifier]);

        return $order instanceof OrderEntity ? $order : null;
    }

    private function resolveSubjectIdentifier(?string $customerId): ?string
    {
        $customerId = trim((string) $customerId);
        if ('' === $customerId) {
            return null;
        }

        if (1 === preg_match('/^accessing:user:\d+$/', $customerId)) {
            return $customerId;
        }

        if (!filter_var($customerId, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $account = $this->entityManager
            ->getRepository(AccessEntity::class)
            ->findOneBy(['email' => mb_strtolower($customerId)]);

        if (!$account instanceof AccessEntity || null === $account->getId()) {
            return null;
        }

        return 'accessing:user:'.$account->getId();
    }
}
