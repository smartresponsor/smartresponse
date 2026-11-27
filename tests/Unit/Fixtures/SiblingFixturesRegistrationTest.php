<?php

declare(strict_types=1);

namespace App\Tests\Unit\Fixtures;

use App\DependencyInjection\Compiler\RegisterSiblingFixturesPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class SiblingFixturesRegistrationTest extends TestCase
{
    public function testExcludedFixtureDefinitionsAreNotReferencedByLoader(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', dirname(__DIR__, 3));
        $container->setParameter('app.sibling_fixtures_allowlist', ['Cataloging']);
        $container->setDefinition('doctrine.fixtures.loader', new Definition());
        $excluded = 'App\\Cataloging\\DataFixtures\\BrokenTreeFixtures';
        $container->setDefinition($excluded, (new Definition($excluded))->setAbstract(true)->addTag('container.excluded'));

        (new RegisterSiblingFixturesPass())->process($container);

        self::assertTrue($container->getDefinition($excluded)->isAbstract());
        $registered = [];
        foreach ($container->getDefinition('doctrine.fixtures.loader')->getMethodCalls() as [$method, $arguments]) {
            if ('addFixtures' === $method) {
                foreach ($arguments[0] as $fixture) {
                    $registered[] = (string) $fixture['fixture'];
                }
            }
        }
        self::assertNotContains($excluded, $registered);
        self::assertContains('App\\Cataloging\\DataFixtures\\MultiCatalogFixtures', $registered);
    }

    public function testDoctrineFixturesLoadDryRunSeesSiblingFixtures(): void
    {
        $allowlist = [
            'Accessing',
            'Analysing',
            'Applicating',
            'Attaching',
            'Billing',
            'Bridging',
            'Cataloging',
            'Commissioning',
            'Cruding',
            'Currencing',
            'Exchanging',
            'Indexing',
            'Interfacing',
            'Localizing',
            'Managing',
            'Messaging',
            'Ordering',
            'Paging',
            'Paying',
            'Rolling',
            'Shipping',
            'Subscripting',
            'Tagging',
            'Taxating',
            'Vendoring',
        ];

        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', dirname(__DIR__, 3));
        $container->setParameter('app.sibling_fixtures_allowlist', $allowlist);
        $container->setDefinition('doctrine.fixtures.loader', new Definition());

        (new RegisterSiblingFixturesPass())->process($container);

        foreach ([
            'App\\Analysing\\DataFixtures\\AnalyticsAnalysingDemoFixtures',
            'App\\Billing\\DataFixtures\\BillingDemoFixtures',
            // Commissioning owns a deterministic fixture helper, not a Doctrine FixtureInterface implementation.
            // Exchanging now owns a deterministic helper outside Doctrine FixturesBundle registration.
            'App\\Localizing\\DataFixtures\\LocaleDemoFixtures',
            'App\\Messaging\\DataFixtures\\MessagingDemoFixtures',
            'App\\Ordering\\DataFixtures\\OrderDemoFixtures',
        ] as $fixtureClass) {
            self::assertTrue($container->hasDefinition($fixtureClass), $fixtureClass);
        }

        self::assertFalse($container->hasDefinition('App\\Commissioning\\DataFixtures\\CommissioningDemoFixtures'));
        self::assertFalse($container->hasDefinition('App\\Exchanging\\DataFixtures\\ExchangeDemoFixtures'));
        self::assertFalse($container->hasDefinition('App\\Paging\\DataFixtures\\PagingDemoFixtures'));
        self::assertTrue($container->hasDefinition('App\\Shipping\\DataFixtures\\ShipmentShippingFixtures'));
        self::assertFalse($container->hasDefinition('App\\DataFixtures\\GovernanceDemoFixture'));
        self::assertFalse($container->hasDefinition('App\\DataFixtures\\FacetingFixture'));
    }
}
