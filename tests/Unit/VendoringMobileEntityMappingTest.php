<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Objecting\EntityInterface\ObjectAuditedInterface;
use App\Objecting\EntityInterface\ObjectIdentifiedInterface;
use App\Objecting\EntityInterface\ObjectStatefulInterface;
use App\Vendoring\Entity\Vendor\VendorAbstractEntity;
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

    public function testVendoringMobileSystemFieldsAreOwnedByObjecting(): void
    {
        $reflection = new \ReflectionClass(VendorAbstractEntity::class);
        self::assertTrue($reflection->implementsInterface(ObjectIdentifiedInterface::class));
        self::assertTrue($reflection->implementsInterface(ObjectAuditedInterface::class));
        self::assertTrue($reflection->implementsInterface(ObjectStatefulInterface::class));

        self::assertFalse((new \ReflectionClass(VendorPayoutEntity::class))->hasProperty('createdAt'));
        self::assertFalse((new \ReflectionClass(VendorPayoutAccountEntity::class))->hasProperty('createdAt'));

        $payoutMigrationSql = $this->migrationSql('Version20260628175000.php');
        self::assertStringContainsString('object_created_at', $payoutMigrationSql);
        self::assertStringNotContainsString('created_at VARCHAR(32)', $payoutMigrationSql);
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

    public function testVendoringMobileIndexesAreDrivenByEntityMapping(): void
    {
        $this->assertTableIndexMapped(VendorEntity::class, 'idx_vendor_owner_user_id');
        $this->assertTableIndexMapped(VendorProfileEntity::class, 'idx_vendor_profile_public_status');
        $this->assertTableIndexMapped(VendorTransactionEntity::class, 'idx_vendor_transaction_vendor_created');
        $this->assertTableIndexMapped(VendorPayoutEntity::class, 'idx_vendor_payout_vendor_id');
        $this->assertTableIndexMapped(VendorPayoutEntity::class, 'idx_vendor_payout_status');
        $this->assertTableIndexMapped(VendorPayoutAccountEntity::class, 'idx_vendor_payout_account_vendor_id');
        $this->assertTableIndexMapped(VendorPayoutItemEntity::class, 'idx_vendor_payout_item_payout_id');
    }

    public function testVendoringMobileUniqueConstraintsAreDrivenByEntityMapping(): void
    {
        $this->assertTableUniqueConstraintMapped(VendorProfileEntity::class, 'uniq_vendor_profile_vendor_id');
        self::assertSame([], $this->tableMappingNames(VendorTransactionEntity::class, 'uniqueConstraints'));

        $transactionMigration = file_get_contents(dirname(__DIR__, 3).'/Vendoring/migrations/MigrationPg/20260321_000001_create_vendor_transaction.sql');
        self::assertIsString($transactionMigration);
        self::assertStringContainsString('uniq_vendor_transaction_vendor_order_project_nonnull', $transactionMigration);
        self::assertStringContainsString('uniq_vendor_transaction_vendor_order_nullproject', $transactionMigration);
    }

    public function testVendoringMobileAlignmentMigrationProjectsEntityIndexes(): void
    {
        $migrationSql = $this->migrationSql('Version20260628190000.php');

        self::assertStringContainsString('CREATE UNIQUE INDEX IF NOT EXISTS uniq_%s_object_uuid', $migrationSql);
        self::assertStringContainsString('object_created_at', $migrationSql);
        self::assertStringContainsString('CREATE INDEX IF NOT EXISTS idx_vendor_payout_item_payout_id', $migrationSql);
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

    /** @param class-string $entityClass */
    private function tableName(string $entityClass): string
    {
        $attributes = (new \ReflectionClass($entityClass))->getAttributes(Table::class);
        self::assertNotSame([], $attributes, $entityClass.' must declare ORM table mapping.');

        $name = $attributes[0]->newInstance()->name;
        self::assertIsString($name);

        return $name;
    }

    /** @param class-string $entityClass */
    private function assertTableIndexMapped(string $entityClass, string $indexName): void
    {
        self::assertContains($indexName, $this->tableMappingNames($entityClass, 'indexes'), $entityClass.' must declare '.$indexName.' in ORM table indexes.');
    }

    /** @param class-string $entityClass */
    private function assertTableUniqueConstraintMapped(string $entityClass, string $constraintName): void
    {
        self::assertContains($constraintName, $this->tableMappingNames($entityClass, 'uniqueConstraints'), $entityClass.' must declare '.$constraintName.' in ORM table unique constraints.');
    }

    /**
     * @param class-string                  $entityClass
     * @param 'indexes'|'uniqueConstraints' $argumentName
     *
     * @return list<string>
     */
    private function tableMappingNames(string $entityClass, string $argumentName): array
    {
        $attributes = (new \ReflectionClass($entityClass))->getAttributes(Table::class);
        self::assertNotSame([], $attributes, $entityClass.' must declare ORM table mapping.');

        $table = $attributes[0]->newInstance();
        $names = [];
        foreach ($table->{$argumentName} ?? [] as $mapping) {
            self::assertIsString($mapping->name);
            $names[] = $mapping->name;
        }

        return $names;
    }

    /** @param class-string $entityClass */
    private function assertColumnMapped(string $entityClass, string $propertyName): void
    {
        $attributes = (new \ReflectionClass($entityClass))->getProperty($propertyName)->getAttributes(Column::class);
        self::assertNotSame([], $attributes, $entityClass.'::$'.$propertyName.' must remain ORM column mapped.');
    }

    /** @param class-string $entityClass */
    private function assertJoinColumnMapped(string $entityClass, string $propertyName): void
    {
        $attributes = (new \ReflectionClass($entityClass))->getProperty($propertyName)->getAttributes(JoinColumn::class);
        self::assertNotSame([], $attributes, $entityClass.'::$'.$propertyName.' must remain ORM join-column mapped.');
    }

    private function migrationSql(string $migrationFile): string
    {
        $sql = file_get_contents(dirname(__DIR__, 2).'/migrations/'.$migrationFile);
        self::assertIsString($sql);

        return $sql;
    }

    public function testVendoringPayoutUsesObjectingSystemFieldsCanon(): void
    {
        $vendoringRoot = dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'Vendoring';
        $appRoot = dirname(__DIR__, 2);
        $abstract = file_get_contents($vendoringRoot.'/src/Entity/Vendor/VendorAbstractEntity.php');
        self::assertIsString($abstract);
        self::assertStringContainsString('ObjectIdentifiedInterface', $abstract);
        self::assertStringContainsString('ObjectAuditedInterface', $abstract);
        self::assertStringContainsString('ObjectStatefulInterface', $abstract);
        self::assertStringContainsString('ObjectIdentityEmbeddableTrait', $abstract);
        self::assertStringContainsString('ObjectAuditEmbeddableTrait', $abstract);
        self::assertStringContainsString('ObjectStateEmbeddableTrait', $abstract);
        foreach (['VendorPayoutEntity', 'VendorPayoutAccountEntity'] as $entityName) {
            $entity = file_get_contents($vendoringRoot.'/src/Entity/Vendor/'.$entityName.'.php');
            self::assertIsString($entity);
            self::assertStringNotContainsString("#[ORM\\Column(name: 'created_at'", $entity);
            self::assertStringNotContainsString(' createdAt;', $entity);
        }
        $migrations = file_get_contents($appRoot.'/migrations/Version20260628175000.php')."\n".file_get_contents($appRoot.'/migrations/Version20260628190000.php');
        self::assertStringContainsString('object_uuid', $migrations);
        self::assertStringContainsString('object_created_at', $migrations);
        self::assertStringContainsString('object_status', $migrations);
        self::assertDoesNotMatchRegularExpression('/vendor_payout[^;]*\bcreated_at\b/i', $migrations);
        self::assertDoesNotMatchRegularExpression('/vendor_payout_account[^;]*\bcreated_at\b/i', $migrations);
        self::assertStringContainsString('idx_vendor_payout_vendor_id', $migrations);
        self::assertStringContainsString('idx_vendor_payout_status', $migrations);
        self::assertStringContainsString('idx_vendor_payout_account_vendor_id', $migrations);
        self::assertStringContainsString('idx_vendor_payout_item_payout_id', $migrations);
    }
}
