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

        $parameters = $router->match('/api/vendor/profile/show/42');

        self::assertSame('vendoring_mobile_vendor_profile', $parameters['_route'] ?? null);

        $parameters = $router->match('/api/vendor/summary/show/vendor-demo-001');
        self::assertSame('vendoring_mobile_vendor_summary', $parameters['_route'] ?? null);

        $parameters = $router->match('/api/vendor/statement/show/vendor-demo-001');
        self::assertSame('vendoring_mobile_vendor_statement', $parameters['_route'] ?? null);

        $parameters = $router->match('/api/vendor/payout/show/vendor-demo-001');
        self::assertSame('vendoring_mobile_vendor_payout', $parameters['_route'] ?? null);

        $parameters = $router->match('/api/vendor/transaction/list/vendor-demo-001');
        self::assertSame('vendoring_mobile_vendor_transaction', $parameters['_route'] ?? null);
    }
}
