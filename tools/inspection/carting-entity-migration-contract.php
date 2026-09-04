<?php

declare(strict_types=1);

use App\Carting\Entity\Cart;
use App\Carting\Entity\CartAdjustmentEntity;
use App\Carting\Entity\CartCheckoutHandoffEntity;
use App\Carting\Entity\CartItem;
use App\Kernel;
use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__, 2).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__, 2).'/.env');
$kernel = new Kernel('prod', false);
$kernel->boot();

try {
    $container = $kernel->getContainer();
    $doctrine = $container->get('doctrine');
    $entityManager = $doctrine->getManager('postgres');

    if (!$entityManager instanceof EntityManagerInterface) {
        throw new RuntimeException('Postgres Doctrine entity manager is unavailable.');
    }

    $classes = [
        Cart::class,
        CartItem::class,
        CartAdjustmentEntity::class,
        CartCheckoutHandoffEntity::class,
    ];
    $metadata = array_map(
        static fn (string $class): object => $entityManager->getClassMetadata($class),
        $classes,
    );
    $schema = (new SchemaTool($entityManager))->getSchemaFromMetadata($metadata);
    $migrationPath = dirname(__DIR__, 2).'/migrations/Version20260712012000.php';
    $migration = file_get_contents($migrationPath);
    $componentMigrationPath = dirname(__DIR__, 2).'/vendor/carting/cart/migrations/Version20260904223100.php';
    $componentMigration = file_get_contents($componentMigrationPath);

    if (false === $migration || false === $componentMigration) {
        throw new RuntimeException('Unable to read Carting host baseline and component forward migrations.');
    }

    $migration .= "\n".$componentMigration;

    $failures = [];

    foreach (['cart_cart', 'cart_item', 'cart_adjustment', 'cart_checkout_handoff'] as $tableName) {
        if (!$schema->hasTable($tableName)) {
            $failures[] = sprintf('Entity metadata does not define table %s.', $tableName);
            continue;
        }

        $table = $schema->getTable($tableName);
        $createLine = migrationLine($migration, sprintf('CREATE TABLE IF NOT EXISTS %s ', $tableName));

        if (null === $createLine) {
            $failures[] = sprintf('Migration does not create table %s.', $tableName);
            continue;
        }

        foreach ($table->getColumns() as $column) {
            $fragment = columnFragment($column, $table->getPrimaryKey()?->getColumns() ?? []);
            if (!str_contains($createLine, $fragment) && !migrationHasAddedColumn($migration, $tableName, $fragment)) {
                $failures[] = sprintf('%s.%s mismatch: expected migration fragment "%s".', $tableName, $column->getName(), $fragment);
            }
        }

        foreach ($table->getIndexes() as $index) {
            if ($index->isPrimary()) {
                continue;
            }

            if (!migrationHasIndex($migration, $tableName, $index->getColumns(), $index->isUnique())) {
                $failures[] = sprintf(
                    '%s index mismatch: expected %sindex on (%s).',
                    $tableName,
                    $index->isUnique() ? 'unique ' : '',
                    implode(', ', $index->getColumns()),
                );
            }
        }

        foreach ($table->getForeignKeys() as $foreignKey) {
            $fragment = sprintf(
                'FOREIGN KEY (%s) REFERENCES %s (%s)%s',
                implode(', ', $foreignKey->getLocalColumns()),
                $foreignKey->getForeignTableName(),
                implode(', ', $foreignKey->getForeignColumns()),
                'CASCADE' === strtoupper((string) $foreignKey->getOption('onDelete')) ? ' ON DELETE CASCADE' : '',
            );
            if (!str_contains($createLine, $fragment)) {
                $failures[] = sprintf('%s foreign-key mismatch: expected "%s".', $tableName, $fragment);
            }
        }
    }

    if ([] !== $failures) {
        fwrite(STDERR, "Carting entity/migration contract failed:\n- ".implode("\n- ", $failures)."\n");
        exit(1);
    }

    fwrite(STDOUT, "Carting entity/migration contract passed: 4 tables match entity metadata.\n");
} finally {
    $kernel->shutdown();
}

/** @param list<string> $primaryColumns */
function columnFragment(Column $column, array $primaryColumns): string
{
    $name = $column->getName();
    $typeClass = $column->getType()::class;
    $isPrimary = in_array($name, $primaryColumns, true);

    if ($column->getAutoincrement() && $isPrimary) {
        return sprintf('%s SERIAL PRIMARY KEY', $name);
    }

    $sqlType = match (true) {
        str_ends_with($typeClass, '\\IntegerType') => 'INT',
        str_ends_with($typeClass, '\\StringType') => sprintf('VARCHAR(%d)', $column->getLength()),
        str_ends_with($typeClass, '\\JsonType') => 'JSON',
        str_ends_with($typeClass, '\\DateTimeImmutableType') => 'TIMESTAMP(0) WITHOUT TIME ZONE',
        default => throw new RuntimeException(sprintf('Unsupported Carting DBAL type %s for %s.', $typeClass, $name)),
    };

    $fragment = sprintf('%s %s', $name, $sqlType);
    $fragment .= $column->getNotnull() ? ' NOT NULL' : ' DEFAULT NULL';

    if (null !== $column->getDefault()) {
        $fragment = preg_replace('/ DEFAULT NULL$/', '', $fragment) ?? $fragment;
        $fragment .= sprintf(' DEFAULT %s', is_string($column->getDefault()) ? $column->getDefault() : (string) $column->getDefault());
    }

    return $fragment;
}

function migrationLine(string $migration, string $needle): ?string
{
    foreach (preg_split('/\R/', $migration) ?: [] as $line) {
        if (str_contains($line, $needle)) {
            return $line;
        }
    }

    return null;
}

function migrationHasAddedColumn(string $migration, string $tableName, string $fragment): bool
{
    $needle = sprintf('ALTER TABLE %s ADD COLUMN IF NOT EXISTS %s', $tableName, $fragment);

    return str_contains($migration, $needle);
}

/** @param list<string> $columns */
function migrationHasIndex(string $migration, string $tableName, array $columns, bool $unique): bool
{
    $columnList = implode(', ', $columns);
    $prefix = $unique ? 'CREATE UNIQUE INDEX IF NOT EXISTS ' : 'CREATE INDEX IF NOT EXISTS ';

    foreach (preg_split('/\R/', $migration) ?: [] as $line) {
        if (str_contains($line, $prefix) && str_contains($line, sprintf(' ON %s (%s)', $tableName, $columnList))) {
            return true;
        }
    }

    return false;
}
