<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

final class NavigationMobileShellRouteTest extends TestCase
{
    public function testMobileNavigationShellRouteIsResolvedBeforeGenericCrudRoutes(): void
    {
        $parameters = $this->matcher()->match('/api/navigation/mobile/shell');

        self::assertSame('navigation_mobile_shell', $parameters['_route'] ?? null);
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

        $routes->addCollection($loader->load($root.'/config/routes/00_navigating_mobile.yaml'));
        $routes->addCollection($loader->load(dirname($root).'/Cruding/config/routes/cruding_api_crud.yaml'));

        return $routes;
    }
}
