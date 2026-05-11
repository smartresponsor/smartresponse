<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineSchemaTablePrefixTest extends KernelTestCase
{
    public static function componentEntityTablePrefixesProvider(): iterable
    {
        yield 'Commissioning' => ['App\\Commissioning\\Entity\\', 'commission_'];
        yield 'Subscripting' => ['App\\Subscripting\\Entity\\', 'subscription_'];
        yield 'Currencing' => ['App\\Entity\\Currency\\', 'currency_'];
        yield 'Exchanging' => ['App\\Exchanging\\Entity\\', 'exchange_'];
    }

    #[DataProvider('componentEntityTablePrefixesProvider')]
    public function testComponentEntityTablesUseCanonicalPrefixes(string $namespacePrefix, string $tablePrefix): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        foreach ($entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
            $className = $metadata->getName();

            if (!str_starts_with($className, $namespacePrefix)) {
                continue;
            }

            self::assertStringStartsWith(
                $tablePrefix,
                $metadata->getTableName(),
                sprintf('%s must map to a table starting with "%s".', $className, $tablePrefix),
            );
        }
    }
}
