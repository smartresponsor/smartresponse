<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Intl\Locales;

final class LocaleRequestSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $locale = $this->requestedLocale($request);

        if (null !== $locale) {
            $request->setLocale($locale);
            return;
        }

        $storedLocale = $this->storedLocale($request);
        if (null !== $storedLocale) {
            $request->setLocale($storedLocale);
        }
    }

    private function requestedLocale(Request $request): ?string
    {
        $locale = trim((string) $request->query->get('locale', ''));
        if ('' === $locale || !Locales::exists($locale)) {
            return null;
        }

        return $locale;
    }

    private function storedLocale(Request $request): ?string
    {
        return null;
    }
}
