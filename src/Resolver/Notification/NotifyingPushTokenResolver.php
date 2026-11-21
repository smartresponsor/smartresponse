<?php

declare(strict_types=1);

namespace App\Resolver\Notification;

use App\Delivering\Exception\DeliveryPermanentTransportException;
use App\Delivering\ResolverInterface\Delivery\DeliveryPushTokenResolverInterface;
use App\Notifying\Service\NotificationSubscriptionService;

final readonly class NotifyingPushTokenResolver implements DeliveryPushTokenResolverInterface
{
    public function __construct(private NotificationSubscriptionService $subscriptionService)
    {
    }

    public function resolve(string $tokenHash, string $platform, string $appKey): string
    {
        $token = $this->subscriptionService->resolveActiveToken($tokenHash, $platform, $appKey);
        if (null === $token) {
            throw new DeliveryPermanentTransportException('Active push subscription token cannot be resolved.');
        }

        return $token;
    }
}
