<?php

declare(strict_types=1);

namespace App\EventSubscriber\View;

use App\Cruding\Value\Resource\CrudResourceContract;
use App\Normalizer\View\CrudResourceViewPayloadNormalizer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class CrudResourceViewPayloadSubscriber implements EventSubscriberInterface
{
    public function __construct(private CrudResourceViewPayloadNormalizer $payloadNormalizer)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::VIEW => ['onKernelView', 512]];
    }

    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if (!$result instanceof CrudResourceContract) {
            return;
        }

        $event->setControllerResult($this->payloadNormalizer->normalize($result));
    }
}
