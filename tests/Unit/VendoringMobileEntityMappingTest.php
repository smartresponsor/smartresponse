<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Vendoring\Entity\Vendor\VendorEntity;
use App\Vendoring\Entity\Vendor\VendorPayoutAccountEntity;
use App\Vendoring\Entity\Vendor\VendorPayoutEntity;
use App\Vendoring\Entity\Vendor\VendorPayoutItemEntity;
use App\Vendoring\Entity\Vendor\VendorProfileEntity;
use App\Vendoring\Entity\Vendor\VendorTransactionEntity;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\Table;
use PHPUnit\Framework\TestCase;

final class VendoringMobileEntityMappingTest extends TestCase
{
    public function testVendoringMobileTablesAreDrivenByEntityMapping(): void
    {
        $entityTables = [
            VendorEntity::class => 'vendor',
            VendorProfileEntity::class => 'vendor_profile',
            VendorTransactionEntity::class => 'vendor_transaction',
            VendorPayoutEntity::class => 'vendor_payout',
            VendorPayoutAccountEntity::class => 'vendor_payout_account',
            VendorPayoutItemEntity::class => 'vendor_payout_item',
        ];

        foreach ($entityTables as $entityClass => $tableName) {
            self::assertSame($tableName, $this->tableName($entityClass), $entityClass);
        }
    }

    public function testCoreVendoringMobileMigrationMaterializesMappedEntityTables(): void
    {
        $migrationSql = $this->migrationSql('Version20260628182000.php');

        foreach ([VendorEntity::class, VendorProfileEntity::class, VendorTransactionEntity::class] as $entityClass) {
            $tableName = $this->tableName($entityClass);

            self::assertStringContainsString('CREATE TABLE IF NOT EXISTS '.$tableName, $migrationSql);
        }
    }

    public function testPayoutVendoringMobileMigrationMaterializesMappedEntityTables(): void
    {
        $migrationSql = $this->migrationSql('Version20260628175000.php');

        foreach ([VendorPayoutEntity::class, VendorPayoutAccountEntity::class, VendorPayoutItemEntity::class] as $entityClass) {
            $tableName = $this->tableName($entityClass);

            self::assertStringContainsString('CREATE TABLE IF NOT EXISTS '.$tableName, $migrationSql);
        }
    }

    public function testVendoringMobileKeyFieldsRemainMappedOnEntities(): void
    {
        $this->assertColumnMapped(VendorEntity::class, 'brandName');
        $this->assertColumnMapped(VendorEntity::class, 'ownerUserId');

        $this->assertJoinColumnMapped(VendorProfileEntity::class, 'vendor');
        $this->assertColumnMapped(VendorProfileEntity::class, 'displayName');
        $this->assertColumnMapped(VendorProfileEntity::class, 'publicProfileStatus');
        $this->assertColumnMapped(VendorProfileEntity::class, 'publicProfilePublishedAt');

        $this->assertColumnMapped(VendorTransactionEntity::class, 'vendorId');
        $this->assertColumnMapped(VendorTransactionEntity::class, 'orderId');
        $this->assertColumnMapped(VendorTransactionEntity::class, 'projectId');
        $this->assertColumnMapped(VendorTransactionEntity::class, 'amount');
        $this->assertColumnMapped(VendorTransactionEntity::class, 'status');
        $this->assertColumnMapped(VendorTransactionEntity::class, 'createdAt');

        $this->assertColumnMapped(VendorPayoutEntity::class, 'payoutId');
        $this->assertColumnMapped(VendorPayoutEntity::class, 'vendorId');
        $this->assertColumnMapped(VendorPayoutEntity::class, 'netCents');
        $this->assertColumnMapped(VendorPayoutAccountEntity::class, 'accountRef');
        $this->assertJoinColumnMapped(VendorPayoutItemEntity::class, 'payout');
    }

    private function tableName(string $entityClass): string
    {
        $attributes = (new \ReflectionClass($entityClass))->getAttributes(Table::class);
        self::assertNotSame([], $attributes, $entityClass.' must declare ORM table mapping.');

        return (string) ($attributes[0]->getArguments()['name'] ?? '');
    }

    private function assertColumnMapped(string $entityClass, string $propertyName): void
    {
        $attributes = (new \ReflectionClass($entityClass))->getProperty($propertyName)->getAttributes(Column::class);
        self::assertNotSame([], $attributes, $entityClass.'::$'.$propertyName.' must remain ORM column mapped.');
    }

    private function assertJoinColumnMapped(string $entityClass, string $propertyName): void
    {
        $attributes = (new \ReflectionClass($entityClass))->getProperty($propertyName)->getAttributes(JoinColumn::class);
        self::assertNotSame([], $attributes, $entityClass.'::$'.$propertyName.' must remain ORM join-column mapped.');
    }

    private function migrationSql(string $migrationFile): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2).'/migrations/'.$migrationFile);
    }
}
