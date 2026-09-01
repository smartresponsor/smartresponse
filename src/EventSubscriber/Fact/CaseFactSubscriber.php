<?php

declare(strict_types=1);

namespace App\EventSubscriber\Fact;

use App\Accessing\Entity\AccessEntity;
use App\Casing\Event\Domain\CaseOpenedEvent;
use App\Casing\Event\Domain\CaseResolvedEvent;
use App\Facting\Fact\FactStream;
use App\ServiceInterface\Fact\FactSubjectCommitterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class CaseFactSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FactSubjectCommitterInterface $factCommitter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CaseOpenedEvent::class => 'onOpened',
            CaseResolvedEvent::class => 'onResolved',
        ];
    }

    public function onOpened(CaseOpenedEvent $event): void
    {
        $this->commit($event, 'case.opened', 'opened');
    }

    public function onResolved(CaseResolvedEvent $event): void
    {
        $this->commit($event, 'case.resolved', 'resolved');
    }

    private function commit(CaseOpenedEvent|CaseResolvedEvent $event, string $type, string $suffix): void
    {
        $subjectIdentifier = $this->resolveSubjectIdentifier($event->actorId);
        if (null === $subjectIdentifier) {
            return;
        }

        try {
            $occurredAt = new \DateTimeImmutable(trim($event->occurredAt));
        } catch (\Exception) {
            return;
        }

        $this->factCommitter->commit(
            new FactStream(trim($event->caseReference), 'case'),
            $type,
            [
                'caseReference' => trim($event->caseReference),
                'businessContext' => trim($event->businessContext),
                'categoryPath' => trim($event->categoryPath),
            ],
            $subjectIdentifier,
            sprintf('Case %s.', $suffix),
            ['source' => 'casing'],
            sprintf('casing:case:%s:%s', trim($event->caseReference), $suffix),
            occurredAt: $occurredAt,
            actor: 'casing:service',
        );
    }

    private function resolveSubjectIdentifier(string $actorId): ?string
    {
        $actorId = trim($actorId);
        if (1 === preg_match('/^accessing:user:\d+$/', $actorId)) {
            return $actorId;
        }
        if (!filter_var($actorId, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $account = $this->entityManager->getRepository(AccessEntity::class)->findOneBy(['email' => mb_strtolower($actorId)]);
        if (!$account instanceof AccessEntity || null === $account->getId()) {
            return null;
        }

        return 'accessing:user:'.$account->getId();
    }
}
