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
