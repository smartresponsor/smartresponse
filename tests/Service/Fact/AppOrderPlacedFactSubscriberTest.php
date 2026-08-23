<?php

declare(strict_types=1);

namespace App\Tests\Service\Fact;

use App\Facting\Fact\FactEnvelope;
use App\Facting\Fact\FactRecord;
use App\Facting\Fact\FactStream;
use App\Ordering\Entity\Order\OrderEntity;
use App\Ordering\Entity\Order\OrderOutboxMessageEntity;
use App\Ordering\Event\Domain\Order\OrderPlacedEvent;
use App\Ordering\Service\Outbox\OutboxProcessor;
use App\Service\Fact\AppOrderPlacedFactSubscriber;
use App\ServiceInterface\Fact\FactSubjectCommitterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class AppOrderPlacedFactSubscriberTest extends TestCase
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

        (new AppOrderPlacedFactSubscriber($entityManager, $factCommitter))
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
        $dispatcher->addSubscriber(new AppOrderPlacedFactSubscriber($entityManager, $factCommitter));

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

        (new AppOrderPlacedFactSubscriber($entityManager, $factCommitter))
            ->onOrderPlaced(new OrderPlacedEvent($order->slug()));
    }
}
