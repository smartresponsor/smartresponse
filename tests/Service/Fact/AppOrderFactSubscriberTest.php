<?php

declare(strict_types=1);

namespace App\Tests\Service\Fact;

use App\Facting\Fact\FactEnvelope;
use App\Facting\Fact\FactRecord;
use App\Facting\Fact\FactStream;
use App\Ordering\Entity\Order\OrderEntity;
use App\Ordering\Entity\Order\OrderOutboxMessageEntity;
use App\Ordering\Event\Domain\Order\OrderPaidEvent;
use App\Ordering\Event\Domain\Order\OrderPlacedEvent;
use App\Ordering\Event\Domain\Order\OrderShippedEvent;
use App\Ordering\Service\Outbox\OutboxProcessor;
use App\Service\Fact\AppOrderFactSubscriber;
use App\ServiceInterface\Fact\FactSubjectCommitterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class AppOrderFactSubscriberTest extends TestCase
{
    public function testCanonicalCustomerSubjectCreatesUserFact(): void
    {
        $order = new OrderEntity('USD', '25.00');
        $order->setCustomerId('accessing:user:42');
        $order->setVendorId('vendor-7');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($order);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        $factCommitter = $this->createMock(FactSubjectCommitterInterface::class);
        $factCommitter->expects(self::once())
            ->method('commit')
            ->with(
                self::callback(static fn (FactStream $stream): bool => 'order' === $stream->getStreamKey() && $stream->getAggregateId() === $order->slug()),
                'order.created',
                self::callback(static fn (array $payload): bool => $payload['orderId'] === $order->slug()),
                'accessing:user:42',
                self::stringContains($order->getNumber()),
                ['source' => 'ordering'],
                'ordering:order:'.$order->slug().':placed',
                1,
                $order->getCreatedAt(),
                'ordering:service',
            )
            ->willReturn(new FactRecord(
                FactEnvelope::dynamic('order.created', [], $order->slug(), 'order'),
                0,
            ));

        (new AppOrderFactSubscriber($entityManager, $factCommitter))
            ->onOrderPlaced(new OrderPlacedEvent($order->slug()));
    }

    public function testDurableOrderingOutboxReachesFactCommitter(): void
    {
        $order = new OrderEntity('USD', '25.00');
        $order->setCustomerId('accessing:user:42');

        $outboxMessage = new OrderOutboxMessageEntity(
            $order->slug(),
            OrderPlacedEvent::class,
            ['orderId' => $order->slug()],
        );

        $outboxRepository = $this->createMock(EntityRepository::class);
        $outboxRepository->method('findBy')->willReturn([$outboxMessage]);

        $orderRepository = $this->createMock(EntityRepository::class);
        $orderRepository->method('findOneBy')->willReturn($order);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnCallback(
            static fn (string $class): EntityRepository => OrderOutboxMessageEntity::class === $class
                ? $outboxRepository
                : $orderRepository,
        );
        $entityManager->expects(self::once())->method('flush');

        $factCommitter = $this->createMock(FactSubjectCommitterInterface::class);
        $factCommitter->expects(self::once())
            ->method('commit')
            ->with(
                self::isInstanceOf(FactStream::class),
                'order.created',
                self::isType('array'),
                'accessing:user:42',
                self::isType('string'),
                ['source' => 'ordering'],
                'ordering:order:'.$order->slug().':placed',
                1,
                $order->getCreatedAt(),
                'ordering:service',
            )
            ->willReturn(new FactRecord(
                FactEnvelope::dynamic('order.created', [], $order->slug(), 'order'),
                0,
            ));

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new AppOrderFactSubscriber($entityManager, $factCommitter));

        self::assertSame(1, (new OutboxProcessor($entityManager, $dispatcher))->process());
        self::assertTrue($outboxMessage->isDispatched());
    }

    public function testDurablePaidOutboxCreatesPaymentFact(): void
    {
        $order = new OrderEntity('USD', '100.00');
        $order->setCustomerId('accessing:user:42');

        $outboxMessage = new OrderOutboxMessageEntity(
            $order->slug(),
            OrderPaidEvent::class,
            [
                'orderId' => $order->slug(),
                'amount' => '100.00',
                'currency' => 'USD',
                'externalRef' => 'pay-ref-100',
            ],
        );

        $outboxRepository = $this->createMock(EntityRepository::class);
        $outboxRepository->method('findBy')->willReturn([$outboxMessage]);

        $orderRepository = $this->createMock(EntityRepository::class);
        $orderRepository->method('findOneBy')->willReturn($order);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnCallback(
            static fn (string $class): EntityRepository => OrderOutboxMessageEntity::class === $class
                ? $outboxRepository
                : $orderRepository,
        );
        $entityManager->expects(self::once())->method('flush');

        $factCommitter = $this->createMock(FactSubjectCommitterInterface::class);
        $factCommitter->expects(self::once())
            ->method('commit')
            ->with(
                self::isInstanceOf(FactStream::class),
                'order.paid',
                self::callback(static fn (array $payload): bool => '100.00' === $payload['amount']
                    && 'USD' === $payload['currency']
                    && 'pay-ref-100' === $payload['externalRef']),
                'accessing:user:42',
                self::stringContains('100.00 USD'),
                ['source' => 'ordering'],
                'ordering:order:'.$order->slug().':payment:pay-ref-100',
                1,
                $order->getUpdatedAt(),
                'ordering:service',
            )
            ->willReturn(new FactRecord(
                FactEnvelope::dynamic('order.paid', [], $order->slug(), 'order'),
                0,
            ));

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new AppOrderFactSubscriber($entityManager, $factCommitter));

        self::assertSame(1, (new OutboxProcessor($entityManager, $dispatcher))->process());
        self::assertTrue($outboxMessage->isDispatched());
    }

    public function testDurableShippedOutboxCreatesShippingFact(): void
    {
        $order = new OrderEntity('USD', '100.00');
        $order->setCustomerId('accessing:user:42');

        $outboxMessage = new OrderOutboxMessageEntity(
            $order->slug(),
            OrderShippedEvent::class,
            [
                'orderId' => $order->slug(),
                'carrier' => 'UPS',
                'trackingCode' => '1ZTRACK100',
            ],
        );

        $outboxRepository = $this->createMock(EntityRepository::class);
        $outboxRepository->method('findBy')->willReturn([$outboxMessage]);

        $orderRepository = $this->createMock(EntityRepository::class);
        $orderRepository->method('findOneBy')->willReturn($order);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnCallback(
            static fn (string $class): EntityRepository => OrderOutboxMessageEntity::class === $class
                ? $outboxRepository
                : $orderRepository,
        );
        $entityManager->expects(self::once())->method('flush');

        $factCommitter = $this->createMock(FactSubjectCommitterInterface::class);
        $factCommitter->expects(self::once())
            ->method('commit')
            ->with(
                self::isInstanceOf(FactStream::class),
                'order.shipped',
                self::callback(static fn (array $payload): bool => 'UPS' === $payload['carrier']
                    && '1ZTRACK100' === $payload['trackingCode']),
                'accessing:user:42',
                self::stringContains('Tracking: 1ZTRACK100'),
                ['source' => 'ordering'],
                'ordering:order:'.$order->slug().':shipment:1ZTRACK100',
                1,
                $order->getUpdatedAt(),
                'ordering:service',
            )
            ->willReturn(new FactRecord(
                FactEnvelope::dynamic('order.shipped', [], $order->slug(), 'order'),
                0,
            ));

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new AppOrderFactSubscriber($entityManager, $factCommitter));

        self::assertSame(1, (new OutboxProcessor($entityManager, $dispatcher))->process());
        self::assertTrue($outboxMessage->isDispatched());
    }

    public function testUnknownCustomerTokenDoesNotCreateFact(): void
    {
        $order = new OrderEntity('USD', '25.00');
        $order->setCustomerId('customer-legacy-1');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($order);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        $factCommitter = $this->createMock(FactSubjectCommitterInterface::class);
        $factCommitter->expects(self::never())->method('commit');

        (new AppOrderFactSubscriber($entityManager, $factCommitter))
            ->onOrderPlaced(new OrderPlacedEvent($order->slug()));
    }
}
