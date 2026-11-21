<?php

declare(strict_types=1);

use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$prefix = $argv[1] ?? null;
$outputPath = null;
$summaryOnly = false;
$environment = 'prod';
foreach (array_slice($argv, 2) as $argument) {
    if ('--summary-only' === $argument) {
        $summaryOnly = true;
        continue;
    }
    if (str_starts_with($argument, '--env=')) {
        $environment = substr($argument, 6);
        if (!in_array($environment, ['dev', 'prod', 'test'], true)) {
            throw new InvalidArgumentException(sprintf('Unsupported environment %s.', $environment));
        }
        continue;
    }

    if (null === $outputPath) {
        $outputPath = $argument;
    }
}
if (!is_string($prefix) || '' === trim($prefix)) {
    fwrite(STDERR, "Usage: php tool/database-component-schema.php <entity-class-prefix> [output-path]\n");
    exit(2);
}

$root = dirname(__DIR__);
if (class_exists(Dotenv::class) && is_file($root . '/.env')) {
    (new Dotenv())->usePutenv()->bootEnv($root . '/.env');
}

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $environment;
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0';

$kernel = new Kernel($environment, false);
$kernel->boot();

try {
    $container = $kernel->getContainer();
    $registry = $container->get('doctrine');
    $manager = $registry->getManager('postgres');
    if (!$manager instanceof EntityManagerInterface) {
        throw new RuntimeException('Postgres Doctrine manager is not an ORM EntityManager.');
    }

    $metadata = array_values(array_filter(
        $manager->getMetadataFactory()->getAllMetadata(),
        static fn ($class): bool => str_starts_with($class->getName(), $prefix),
    ));

    if ([] === $metadata) {
        throw new RuntimeException(sprintf('No Doctrine metadata matched prefix %s.', $prefix));
    }

    fwrite(STDOUT, sprintf("Matched entities: %d\n", count($metadata)));
    foreach ($metadata as $class) {
        fwrite(STDOUT, sprintf("ENTITY %s TABLE %s\n", $class->getName(), $class->getTableName()));
    }

    $tool = new SchemaTool($manager);
    $expectedSchema = $tool->getSchemaFromMetadata($metadata);
    $sql = $tool->getCreateSchemaSql($metadata);
    $projection = implode(";\n", $sql) . ";\n";

    $expectedTables = array_values(array_map(static fn ($table): string => $table->getName(), $expectedSchema->getTables()));
    $schemaManager = $manager->getConnection()->createSchemaManager();
    $existingTables = array_map('strtolower', $schemaManager->listTableNames());
    $missingTables = array_values(array_filter($expectedTables, static fn (string $table): bool => !in_array(strtolower($table), $existingTables, true)));
    fwrite(STDOUT, sprintf("Expected tables: %d; missing in current DB: %d\n", count($expectedTables), count($missingTables)));
    if ([] !== $missingTables) {
        fwrite(STDOUT, 'MISSING ' . implode(', ', $missingTables) . "\n");
    }

    $missingColumns = [];
    foreach ($expectedTables as $inspectTable) {
        if (!in_array(strtolower($inspectTable), $existingTables, true)) {
            continue;
        }
        $actualColumns = array_map(static fn ($column): string => strtolower($column->getName()), $schemaManager->listTableColumns($inspectTable));
        $expectedColumns = array_map(static fn ($column): string => strtolower($column->getName()), $expectedSchema->getTable($inspectTable)->getColumns());
        $missingForTable = array_values(array_diff($expectedColumns, $actualColumns));
        if ([] !== $missingForTable) {
            $missingColumns[$inspectTable] = $missingForTable;
        }
        if (!$summaryOnly) {
            fwrite(STDOUT, sprintf("COLUMNS %s %s\n", $inspectTable, implode(',', $actualColumns)));
            $indexes = array_map(static fn ($index): string => strtolower($index->getName()), $schemaManager->listTableIndexes($inspectTable));
            fwrite(STDOUT, sprintf("INDEXES %s %s\n", $inspectTable, implode(',', $indexes)));
        }
    }
    $missingColumnCount = array_sum(array_map('count', $missingColumns));
    fwrite(STDOUT, sprintf("Missing columns in current DB: %d\n", $missingColumnCount));
    foreach ($missingColumns as $table => $columns) {
        fwrite(STDOUT, sprintf("MISSING_COLUMNS %s %s\n", $table, implode(',', $columns)));
    }

    if (is_string($outputPath) && '' !== trim($outputPath)) {
        $resolvedOutput = str_starts_with($outputPath, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $outputPath)
            ? $outputPath
            : $root . DIRECTORY_SEPARATOR . $outputPath;
        $directory = dirname($resolvedOutput);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create output directory %s.', $directory));
        }
        if (false === file_put_contents($resolvedOutput, $projection)) {
            throw new RuntimeException(sprintf('Unable to write schema projection %s.', $resolvedOutput));
        }
        fwrite(STDOUT, sprintf("Schema projection written: %s\n", $resolvedOutput));
    } else {
        if (!$summaryOnly) {
            fwrite(STDOUT, "=== CREATE SCHEMA SQL ===\n");
            fwrite(STDOUT, $projection);
        }
    }
} finally {
    $kernel->shutdown();
}
