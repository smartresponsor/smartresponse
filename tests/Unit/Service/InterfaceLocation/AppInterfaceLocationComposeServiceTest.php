<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\InterfaceLocation;

use App\Service\InterfaceLocation\AppInterfaceLocationComposeService;
use App\ServiceInterface\InterfaceLocation\AppInterfaceLocationComposeServiceInterface;
use App\Viewing\Contract\ViewInterfaceLocationComposerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

final class AppInterfaceLocationComposeServiceTest extends TestCase
{
    public function testViewingContractAliasResolvesHostCompositionService(): void
    {
        $config = Yaml::parseFile(dirname(__DIR__, 4).'/config/services.yaml', Yaml::PARSE_CUSTOM_TAGS);
        self::assertIsArray($config);
        $services = $config['services'] ?? null;
        self::assertIsArray($services);

        self::assertArrayNotHasKey('App\\Viewing\\ServiceInterface\\ViewInterfaceLocationComposeServiceInterface', $services);
        $container = new ContainerBuilder();
        $container->register(AppInterfaceLocationComposeService::class)->setPublic(true);

        foreach ([ViewInterfaceLocationComposerInterface::class, AppInterfaceLocationComposeServiceInterface::class] as $contract) {
            $definition = $services[$contract] ?? null;
            self::assertIsArray($definition);
            $alias = $definition['alias'] ?? null;
            self::assertSame(AppInterfaceLocationComposeService::class, $alias);
            $container->setAlias($contract, AppInterfaceLocationComposeService::class)->setPublic(true);
        }

        $container->compile();

        $service = $container->get(ViewInterfaceLocationComposerInterface::class);
        self::assertInstanceOf(ViewInterfaceLocationComposerInterface::class, $service);
        self::assertSame($service, $container->get(AppInterfaceLocationComposeServiceInterface::class));
        self::assertSame([], $service->composeLocations(Request::create('/')));
    }

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
