<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
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
            $this->storeLocale($request, $locale);

            return;
        }

        $storedLocale = $this->storedLocale($request);
        if (null !== $storedLocale) {
            $request->setLocale($storedLocale);
        }
    }

    private function requestedLocale(Request $request): ?string
    {
        $value = $request->query->get('locale', '');
        if (!is_string($value)) {
            return null;
        }

        $locale = trim($value);
        if ('' === $locale || !Locales::exists($locale)) {
            return null;
        }

        return $locale;
    }

    private function storedLocale(Request $request): ?string
    {
        if (!$request->hasSession()) {
            return null;
        }

        $value = $request->getSession()->get('_app_locale', '');
        if (!is_string($value)) {
            return null;
        }

        $locale = trim($value);
        if ('' === $locale || !Locales::exists($locale)) {
            return null;
        }

        return $locale;
    }

    private function storeLocale(Request $request, string $locale): void
    {
        if (!$request->hasSession()) {
            return;
        }

        $request->getSession()->set('_app_locale', $locale);
    }
}
