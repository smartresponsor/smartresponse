<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

final class VendoringMobileProfileRouteTest extends TestCase
{
    public function testVendorProfileRouteIsResolvedBeforeGenericCrudApiRoute(): void
    {
        $matcher = $this->matcher();

        $parameters = $matcher->match('/api/vendor/profile/show/42');
        self::assertSame('vendoring_mobile_vendor_profile', $parameters['_route'] ?? null);

        $parameters = $matcher->match('/api/vendor/summary/show/vendor-demo-001');
        self::assertSame('vendoring_mobile_vendor_summary', $parameters['_route'] ?? null);

        $parameters = $matcher->match('/api/vendor/statement/show/vendor-demo-001');
        self::assertSame('vendoring_mobile_vendor_statement', $parameters['_route'] ?? null);

        $parameters = $matcher->match('/api/vendor/payout/show/vendor-demo-001');
        self::assertSame('vendoring_mobile_vendor_payout', $parameters['_route'] ?? null);

        $parameters = $matcher->match('/api/vendor/transaction/list/vendor-demo-001');
        self::assertSame('vendoring_mobile_vendor_transaction', $parameters['_route'] ?? null);
    }

    private function matcher(): UrlMatcher
    {
        return new UrlMatcher($this->routes(), new RequestContext('/'));
    }

    private function routes(): RouteCollection
    {
        $root = dirname(__DIR__, 2);
        $loader = new YamlFileLoader(new FileLocator([$root, dirname($root)]));
        $routes = new RouteCollection();

        $routes->addCollection($loader->load($root.'/config/routes/00_vendoring_mobile.yaml'));
        $routes->addCollection($loader->load(dirname($root).'/Cruding/config/routes/cruding_api_crud.yaml'));

        return $routes;
    }
}
