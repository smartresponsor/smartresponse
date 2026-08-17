<?php

declare(strict_types=1);

namespace App\Service\Projecting;

use App\Projecting\Http\Subscriber\VendorSlaRateLimitSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class AppProjectingVendorSlaRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(private VendorSlaRateLimitSubscriber $inner)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 1]];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $controller = $event->getRequest()->attributes->get('_controller');
        if (!is_string($controller) || !str_starts_with(ltrim($controller, '\\'), 'App\\Projecting\\')) {
            return;
        }

        $this->inner->onRequest($event);
    }
}
