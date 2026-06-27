<?php

declare(strict_types=1);

namespace App\Tests\Unit\Fixtures;

use App\DependencyInjection\Compiler\RegisterSiblingFixturesPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class SiblingFixturesRegistrationTest extends TestCase
{
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
            'App\\Analysing\\DataFixtures\\AnalysingDemoFixtures',
            'App\\Billing\\DataFixtures\\BillingDemoFixtures',
            'App\\Commissioning\\DataFixtures\\CommissioningDemoFixtures',
            'App\\Exchanging\\DataFixtures\\ExchangingDemoFixtures',
            'App\\Localizing\\DataFixtures\\LocaleDemoFixtures',
            'App\\Messaging\\DataFixtures\\MessagingDemoFixtures',
            'App\\DataFixtures\\OrderDemoFixtures',
        ] as $fixtureClass) {
            self::assertTrue($container->hasDefinition($fixtureClass), $fixtureClass);
        }

        self::assertFalse($container->hasDefinition('App\\Paging\\DataFixtures\\PagingDemoFixtures'));
        self::assertTrue($container->hasDefinition('App\\Shipping\\DataFixtures\\ShippingDemoFixtures'));
        self::assertFalse($container->hasDefinition('App\\DataFixtures\\GovernanceDemoFixture'));
        self::assertFalse($container->hasDefinition('App\\DataFixtures\\FacetingFixture'));
    }
}
