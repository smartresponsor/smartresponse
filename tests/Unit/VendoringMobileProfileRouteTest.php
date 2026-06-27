<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class VendoringMobileProfileRouteTest extends KernelTestCase
{
    public function testVendorProfileRouteIsResolvedBeforeGenericCrudApiRoute(): void
    {
        self::bootKernel();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get('router');

        $parameters = $router->match('/api/vendor/profile/42');

        self::assertSame('vendoring_mobile_vendor_profile', $parameters['_route'] ?? null);
    }
}
