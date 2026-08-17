<?php

declare(strict_types=1);

namespace App\Service\Notification;

use App\Delivering\Exception\DeliveringPermanentTransportException;
use App\Delivering\ServiceInterface\Command\Delivery\DeliveringPushTokenResolverInterface;
use App\Notifying\Service\NotificationSubscriptionService;

final readonly class AppNotifyingPushTokenResolver implements DeliveringPushTokenResolverInterface
{
    public function __construct(private NotificationSubscriptionService $subscriptionService)
    {
    }

    public function resolve(string $tokenHash, string $platform, string $appKey): string
    {
        $token = $this->subscriptionService->resolveActiveToken($tokenHash, $platform, $appKey);
        if (null === $token) {
            throw new DeliveringPermanentTransportException('Active push subscription token cannot be resolved.');
        }

        return $token;
    }
}
