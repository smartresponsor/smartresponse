<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\EventSubscriber\LocaleRequestSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class LocaleRequestSubscriberTest extends TestCase
{
    public function testSubscriberSetsLocaleFromQueryAndStoresItInSession(): void
    {
        $request = Request::create('/interfacing/screens', 'GET', ['locale' => 'uk']);
        $request->setSession(new Session(new MockArraySessionStorage()));
        $event = new RequestEvent($this->kernel(), $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber = new LocaleRequestSubscriber();
        $subscriber->onKernelRequest($event);

        self::assertSame('uk', $request->getLocale());
        self::assertSame('uk', $request->getSession()->get('_app_locale'));
    }

    private function kernel(): HttpKernelInterface
    {
        return new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST, bool $catch = true): \Symfony\Component\HttpFoundation\Response
            {
                throw new \RuntimeException('Not implemented.');
            }
        };
    }
}
