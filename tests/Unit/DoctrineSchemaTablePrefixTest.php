<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineSchemaTablePrefixTest extends KernelTestCase
{
    /** @return iterable<string, array{non-empty-string, non-empty-string}> */
    public static function componentEntityTablePrefixesProvider(): iterable
    {
        yield 'Commissioning' => ['App\\Commissioning\\Entity\\', 'commission_'];
        yield 'Subscripting' => ['App\\Subscripting\\Entity\\', 'subscription_'];
        yield 'Currencing' => ['App\\Currencing\\Entity\\Currency\\', 'currency_'];
        yield 'Exchanging' => ['App\\Exchanging\\Entity\\', 'exchange_'];
    }

    /** @param non-empty-string $tablePrefix */
    #[DataProvider('componentEntityTablePrefixesProvider')]
    public function testComponentEntityTablesUseCanonicalPrefixes(string $namespacePrefix, string $tablePrefix): void
    {
        self::bootKernel();

        $registry = self::getContainer()->get(ManagerRegistry::class);
        self::assertInstanceOf(ManagerRegistry::class, $registry);
        $entityManager = $registry->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        foreach ($entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
            $className = $metadata->getName();

            if (!str_starts_with($className, $namespacePrefix)) {
                continue;
            }

            self::assertNotSame('', $tablePrefix);
            self::assertStringStartsWith(
                $tablePrefix,
                $metadata->getTableName(),
                sprintf('%s must map to a table starting with "%s".', $className, $tablePrefix),
            );
        }

        $this->addToAssertionCount(1);
    }
}
