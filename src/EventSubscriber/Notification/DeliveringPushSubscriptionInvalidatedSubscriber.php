<?php

declare(strict_types=1);

namespace App\EventSubscriber\Notification;

use App\Delivering\Event\DeliveryPushSubscriptionInvalidated;
use App\Notifying\Service\NotificationSubscriptionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class DeliveringPushSubscriptionInvalidatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationSubscriptionService $subscriptionService,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DeliveryPushSubscriptionInvalidated::class => 'onInvalidated',
        ];
    }

    public function onInvalidated(DeliveryPushSubscriptionInvalidated $event): void
    {
        $result = $this->subscriptionService->disableInvalidSubscription(
            tokenHash: $event->tokenHash,
            reasonCode: $event->reasonCode,
            modifiedBy: 'delivering-invalid-subscription',
        );

        $this->logger->warning('app.notification.push_subscription_invalidated', [
            'platform' => $event->platform,
            'app_key' => $event->appKey,
            'token_hash' => $event->tokenHash,
            'reason_code' => $event->reasonCode,
            'correlation_id' => $event->correlationId,
            'idempotency_key' => $event->idempotencyKey,
            'subscription_found' => (bool) $result['disabled'],
            'suppressed_dispatch_plan_count' => count($result['suppressedDispatchPlans']),
        ]);
    }
}
