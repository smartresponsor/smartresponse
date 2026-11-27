<?php

declare(strict_types=1);

namespace App\Tests\Unit\Fixtures;

use App\DependencyInjection\Compiler\RegisterSiblingFixturesPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class SiblingFixturesRegistrationTest extends TestCase
{
    public function testFixtureDiscoveryUsesOnlyComposerInstalledComponents(): void
    {
        $workspace = sys_get_temp_dir().'/app-fixture-discovery-'.bin2hex(random_bytes(8));
        $project = $workspace.'/App';
        $installed = $project.'/vendor/probe/fixture/src/DataFixtures';
        $sibling = $workspace.'/Sandbox/src/DataFixtures';
        $filesystem = new \Symfony\Component\Filesystem\Filesystem();

        try {
            $filesystem->mkdir([$installed, $sibling]);
            foreach ([$installed => 'InstalledFixture', $sibling => 'SiblingOnlyFixture'] as $directory => $class) {
                $filesystem->dumpFile($directory.'/'.$class.'.php', '<?php namespace App\Tests\FixtureDiscoveryProbe; final class '.$class.' implements \Doctrine\Common\DataFixtures\FixtureInterface { public function load(\Doctrine\Persistence\ObjectManager $manager): void {} }');
            }

            $container = new ContainerBuilder();
            $container->setParameter('kernel.project_dir', $project);
            $container->setParameter('app.sibling_fixtures_allowlist', []);
            $container->setDefinition('doctrine.fixtures.loader', new Definition());

            (new RegisterSiblingFixturesPass())->process($container);

            self::assertTrue($container->hasDefinition('App\\Tests\\FixtureDiscoveryProbe\\InstalledFixture'));
            self::assertFalse($container->hasDefinition('App\\Tests\\FixtureDiscoveryProbe\\SiblingOnlyFixture'));
            $calls = $container->getDefinition('doctrine.fixtures.loader')->getMethodCalls();
            self::assertCount(1, $calls);
            self::assertSame(['App\\Tests\\FixtureDiscoveryProbe\\InstalledFixture'], $this->registeredFixtureIds($container));
        } finally {
            $filesystem->remove($workspace);
        }
    }

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
        $registered = $this->registeredFixtureIds($container);
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

    /**
     * @return list<string>
     */
    private function registeredFixtureIds(ContainerBuilder $container): array
    {
        $registered = [];
        foreach ($container->getDefinition('doctrine.fixtures.loader')->getMethodCalls() as $call) {
            if (!\is_array($call) || 'addFixtures' !== ($call[0] ?? null)) {
                continue;
            }

            $arguments = $call[1] ?? null;
            self::assertIsArray($arguments);
            $fixtures = $arguments[0] ?? null;
            self::assertIsArray($fixtures);

            foreach ($fixtures as $fixture) {
                self::assertIsArray($fixture);
                $reference = $fixture['fixture'] ?? null;
                self::assertInstanceOf(Reference::class, $reference);
                $registered[] = (string) $reference;
            }
        }

        return $registered;
    }
}
