<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class NavigationMobileShellRouteTest extends KernelTestCase
{
    public function testMobileNavigationShellRouteIsResolvedBeforeGenericCrudRoutes(): void
    {
        self::bootKernel();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get('router');

        $parameters = $router->match('/api/navigation/mobile/shell');

        self::assertSame('navigation_mobile_shell', $parameters['_route'] ?? null);
    }
}
