<?php

declare(strict_types=1);

namespace App\Tests\Service\Fact;

use App\Casing\Event\Domain\CaseOpenedEvent;
use App\Casing\Event\Domain\CaseResolvedEvent;
use App\Facting\Fact\FactEnvelope;
use App\Facting\Fact\FactRecord;
use App\Facting\Fact\FactStream;
use App\Service\Fact\AppCaseFactSubscriber;
use App\ServiceInterface\Fact\FactSubjectCommitterInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class AppCaseFactSubscriberTest extends TestCase
{
    public function testOpenedAndResolvedFactsUseCanonicalActorSubject(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $committer = $this->createMock(FactSubjectCommitterInterface::class);
        $committer->expects(self::exactly(2))
            ->method('commit')
            ->with(
                self::isInstanceOf(FactStream::class),
                self::logicalOr(self::equalTo('case.opened'), self::equalTo('case.resolved')),
                self::isType('array'),
                'accessing:user:42',
                self::isType('string'),
                ['source' => 'casing'],
                self::isType('string'),
                1,
                self::isInstanceOf(\DateTimeImmutable::class),
                'casing:service',
            )
            ->willReturnCallback(static fn (FactStream $stream, string $type): FactRecord => new FactRecord(
                FactEnvelope::dynamic($type, [], '01CASE', 'case'),
                0,
            ));

        $subscriber = new AppCaseFactSubscriber($entityManager, $committer);
        $subscriber->onOpened(new CaseOpenedEvent('01CASE', 'accessing:user:42', 'retailing.product', 'retailing.product.return', '2026-08-24T09:00:00-05:00'));
        $subscriber->onResolved(new CaseResolvedEvent('01CASE', 'accessing:user:42', 'retailing.product', 'retailing.product.return', '2026-08-24T09:30:00-05:00'));
    }

    public function testEmailActorResolvesToCanonicalSubject(): void
    {
        $account = new \App\Accessing\Entity\AccessEntity('user@example.com');
        $id = new \ReflectionProperty($account, 'id');
        $id->setValue($account, 42);

        $repository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'user@example.com'])
            ->willReturn($account);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with(\App\Accessing\Entity\AccessEntity::class)
            ->willReturn($repository);

        $committer = $this->createMock(FactSubjectCommitterInterface::class);
        $committer->expects(self::once())
            ->method('commit')
            ->with(
                self::isInstanceOf(FactStream::class),
                'case.opened',
                self::isType('array'),
                'accessing:user:42',
                self::isType('string'),
                ['source' => 'casing'],
                self::isType('string'),
                1,
                self::isInstanceOf(\DateTimeImmutable::class),
                'casing:service',
            )
            ->willReturn(new FactRecord(FactEnvelope::dynamic('case.opened', [], '01CASE', 'case'), 0));

        (new AppCaseFactSubscriber($entityManager, $committer))->onOpened(
            new CaseOpenedEvent('01CASE', 'user@example.com', 'retailing.product', 'retailing.product.return', '2026-08-24T09:00:00-05:00'),
        );
    }

    public function testOpaqueActorIsIgnored(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $committer = $this->createMock(FactSubjectCommitterInterface::class);
        $committer->expects(self::never())->method('commit');

        (new AppCaseFactSubscriber($entityManager, $committer))->onOpened(
            new CaseOpenedEvent('01CASE', 'opaque-actor', 'retailing.product', 'retailing.product.return', '2026-08-24T09:00:00-05:00'),
        );
    }
}
