<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\InterfaceLocation;

use App\Service\InterfaceLocation\AppInterfaceLocationComposeService;
use PHPUnit\Framework\TestCase;

final class AppInterfaceLocationComposeServiceTest extends TestCase
{
    public function testEmptyCanonicalLocationsDoNotCrossCompositionBoundary(): void
    {
        $service = new AppInterfaceLocationComposeService();
        $method = new \ReflectionMethod($service, 'extractLocations');
        $method->setAccessible(true);

        $locations = $method->invoke($service, [
            'shell.left.top' => [
                ['key' => 'dashboard', 'href' => '/app'],
            ],
            'shell.main.toolbar' => [],
            'shell.context.middle' => [null, 'invalid'],
        ]);

        self::assertSame([
            'shell.left.top' => [
                ['key' => 'dashboard', 'href' => '/app'],
            ],
        ], $locations);
    }
}
