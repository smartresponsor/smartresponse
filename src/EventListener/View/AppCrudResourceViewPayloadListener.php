<?php

declare(strict_types=1);

namespace App\EventListener\View;

use App\Cruding\Value\Resource\CrudResourceContract;
use App\Service\View\AppCrudResourceViewPayloadNormalizer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class AppCrudResourceViewPayloadListener implements EventSubscriberInterface
{
    public function __construct(private AppCrudResourceViewPayloadNormalizer $payloadNormalizer)
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
