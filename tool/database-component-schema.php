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
foreach (array_slice($argv, 2) as $argument) {
    if ('--summary-only' === $argument) {
        $summaryOnly = true;
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

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'prod';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0';

$kernel = new Kernel('prod', false);
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
    $sql = $tool->getCreateSchemaSql($metadata);
    $projection = implode(";\n", $sql) . ";\n";

    preg_match_all('/CREATE TABLE ([a-zA-Z0-9_]+)/', $projection, $tableMatches);
    $expectedTables = array_values(array_unique($tableMatches[1] ?? []));
    $schemaManager = $manager->getConnection()->createSchemaManager();
    $existingTables = array_map('strtolower', $schemaManager->listTableNames());
    $missingTables = array_values(array_filter($expectedTables, static fn (string $table): bool => !in_array(strtolower($table), $existingTables, true)));
    fwrite(STDOUT, sprintf("Expected tables: %d; missing in current DB: %d\n", count($expectedTables), count($missingTables)));
    if ([] !== $missingTables) {
        fwrite(STDOUT, 'MISSING ' . implode(', ', $missingTables) . "\n");
    }
    if (!$summaryOnly) {
        foreach ($expectedTables as $inspectTable) {
        if (in_array($inspectTable, $existingTables, true)) {
            $columns = array_map('strtolower', array_keys($schemaManager->listTableColumns($inspectTable)));
            fwrite(STDOUT, sprintf("COLUMNS %s %s\n", $inspectTable, implode(',', $columns)));
            $indexes = array_map(static fn ($index): string => strtolower($index->getName()), $schemaManager->listTableIndexes($inspectTable));
            fwrite(STDOUT, sprintf("INDEXES %s %s\n", $inspectTable, implode(',', $indexes)));
        }
    }

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
