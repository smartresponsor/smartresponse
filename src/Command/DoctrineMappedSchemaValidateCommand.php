<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:doctrine:mapped-schema:validate', description: 'Validate ORM-owned tables while ignoring unrelated shared-database tables.')]
final class DoctrineMappedSchemaValidateCommand extends Command
{
    public function __construct(private readonly ManagerRegistry $doctrine)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('em', null, InputOption::VALUE_REQUIRED, 'Doctrine entity manager name.', 'postgres');
        $this->addOption('summary', null, InputOption::VALUE_NONE, 'Print only aggregate mapped-schema drift counts.');
        $this->addOption('namespace-prefix', null, InputOption::VALUE_REQUIRED, 'Limit metadata to classes under the given namespace prefix.');
        $this->addOption('table-prefix', null, InputOption::VALUE_REQUIRED, 'Limit metadata to tables whose names start with the given prefix.');
        $this->addOption('dump-sql', null, InputOption::VALUE_NONE, 'Print scoped SchemaTool update SQL and exit.');
        $this->addOption('dump-create-sql', null, InputOption::VALUE_NONE, 'Print scoped SchemaTool create SQL and exit.');
        $this->addOption('dump-missing-create-sql', null, InputOption::VALUE_NONE, 'Print create SQL only for physically missing scoped tables.');
        $this->addOption('ignore-index-names', null, InputOption::VALUE_NONE, 'Ignore index-name-only drift when index semantics are otherwise equivalent.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $managerName = $input->getOption('em');
        if (!is_string($managerName) || '' === $managerName) {
            $output->writeln('<error>Entity manager name is required.</error>');

            return Command::INVALID;
        }

        $manager = $this->doctrine->getManager($managerName);
        if (!$manager instanceof EntityManagerInterface) {
            $output->writeln(sprintf('<error>Manager "%s" is not an ORM entity manager.</error>', $managerName));

            return Command::FAILURE;
        }

        $metadata = $this->filteredMetadata($manager, $input);
        $schemaTool = new SchemaTool($manager);
        $schemaManager = $manager->getConnection()->createSchemaManager();
        $dumpResult = $this->dumpSchemaSql($input, $output, $schemaTool, $schemaManager, $metadata);
        if (null !== $dumpResult) {
            return $dumpResult;
        }

        $tableNames = $this->mappedTableNames($metadata);
        [$failures, $missingTables, $missingByPrefix, $driftedByPrefix] = $this->collectDrift(
            $input,
            $schemaTool,
            $schemaManager,
            $metadata,
            $tableNames,
        );

        return $this->writeValidationResult(
            $input,
            $output,
            $tableNames,
            $failures,
            $missingTables,
            $missingByPrefix,
            $driftedByPrefix,
        );
    }

    /** @return list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private function filteredMetadata(EntityManagerInterface $manager, InputInterface $input): array
    {
        $metadata = $manager->getMetadataFactory()->getAllMetadata();
        $namespacePrefix = $input->getOption('namespace-prefix');
        if (is_string($namespacePrefix) && '' !== $namespacePrefix) {
            $metadata = array_values(array_filter(
                $metadata,
                static fn ($classMetadata): bool => str_starts_with($classMetadata->getName(), $namespacePrefix),
            ));
        }

        $tablePrefix = $input->getOption('table-prefix');
        if (is_string($tablePrefix) && '' !== $tablePrefix) {
            $metadata = array_values(array_filter(
                $metadata,
                static fn ($classMetadata): bool => str_starts_with($classMetadata->getTableName(), $tablePrefix),
            ));
        }

        return $metadata;
    }

    /**
     * @param \Doctrine\DBAL\Schema\AbstractSchemaManager<\Doctrine\DBAL\Platforms\AbstractPlatform> $schemaManager
     * @param list<\Doctrine\ORM\Mapping\ClassMetadata<object>>                                      $metadata
     */
    private function dumpSchemaSql(
        InputInterface $input,
        OutputInterface $output,
        SchemaTool $schemaTool,
        \Doctrine\DBAL\Schema\AbstractSchemaManager $schemaManager,
        array $metadata,
    ): ?int {
        if ($input->getOption('dump-missing-create-sql')) {
            $metadata = array_values(array_filter(
                $metadata,
                static fn ($classMetadata): bool => !$classMetadata->isEmbeddedClass
                    && !$classMetadata->isMappedSuperclass
                    && !$schemaManager->tableExists($classMetadata->getTableName()),
            ));

            return $this->writeSql($output, $schemaTool->getCreateSchemaSql($metadata));
        }

        if ($input->getOption('dump-create-sql')) {
            return $this->writeSql($output, $schemaTool->getCreateSchemaSql($metadata));
        }

        if ($input->getOption('dump-sql')) {
            return $this->writeSql($output, $schemaTool->getUpdateSchemaSql($metadata));
        }

        return null;
    }

    /** @param list<string> $statements */
    private function writeSql(OutputInterface $output, array $statements): int
    {
        foreach ($statements as $sql) {
            $output->writeln($sql.';');
        }

        return Command::SUCCESS;
    }

    /**
     * @param list<\Doctrine\ORM\Mapping\ClassMetadata<object>> $metadata
     *
     * @return list<string>
     */
    private function mappedTableNames(array $metadata): array
    {
        $tableNames = [];
        foreach ($metadata as $classMetadata) {
            if ($classMetadata->isEmbeddedClass || $classMetadata->isMappedSuperclass) {
                continue;
            }

            $tableNames[$classMetadata->getTableName()] = true;
        }

        ksort($tableNames);

        return array_keys($tableNames);
    }

    /**
     * @param \Doctrine\DBAL\Schema\AbstractSchemaManager<\Doctrine\DBAL\Platforms\AbstractPlatform> $schemaManager
     * @param list<\Doctrine\ORM\Mapping\ClassMetadata<object>>                                      $metadata
     * @param list<string>                                                                           $tableNames
     *
     * @return array{0: array<string, list<string>>, 1: int, 2: array<string, int>, 3: array<string, int>}
     */
    private function collectDrift(
        InputInterface $input,
        SchemaTool $schemaTool,
        \Doctrine\DBAL\Schema\AbstractSchemaManager $schemaManager,
        array $metadata,
        array $tableNames,
    ): array {
        $expectedSchema = $schemaTool->getSchemaFromMetadata($metadata);
        $comparator = $schemaManager->createComparator();
        $failures = [];
        $missingTables = 0;
        $missingByPrefix = [];
        $driftedByPrefix = [];

        foreach ($tableNames as $tableName) {
            if ('' === $tableName) {
                continue;
            }

            $prefix = explode('_', $tableName, 2)[0];
            if (!$expectedSchema->hasTable($tableName)) {
                $failures[$tableName] = ['expected table missing from generated ORM schema'];
                continue;
            }

            if (!$schemaManager->tableExists($tableName)) {
                ++$missingTables;
                $missingByPrefix[$prefix] = ($missingByPrefix[$prefix] ?? 0) + 1;
                $failures[$tableName] = ['database table missing'];
                continue;
            }

            $diff = $comparator->compareTables(
                $schemaManager->introspectTableByUnquotedName($tableName),
                $expectedSchema->getTable($tableName),
            );
            if ($diff->isEmpty()) {
                continue;
            }

            $reasons = $this->tableDiffReasons($diff, (bool) $input->getOption('ignore-index-names'));
            if (null === $reasons) {
                continue;
            }

            $driftedByPrefix[$prefix] = ($driftedByPrefix[$prefix] ?? 0) + 1;
            $failures[$tableName] = $reasons;
        }

        return [$failures, $missingTables, $missingByPrefix, $driftedByPrefix];
    }

    /** @return list<string>|null */
    private function tableDiffReasons(\Doctrine\DBAL\Schema\TableDiff $diff, bool $ignoreIndexNames): ?array
    {
        $renamedIndexes = $diff->getRenamedIndexes();
        $reasons = array_values(array_filter([
            [] !== $diff->getAddedColumns() ? sprintf('+columns:%d', count($diff->getAddedColumns())) : null,
            [] !== $diff->getChangedColumns() ? sprintf('~columns:%d', count($diff->getChangedColumns())) : null,
            [] !== $diff->getDroppedColumns() ? sprintf('-columns:%d', count($diff->getDroppedColumns())) : null,
            [] !== $diff->getAddedIndexes() ? sprintf('+indexes:%d', count($diff->getAddedIndexes())) : null,
            [] !== $diff->getDroppedIndexes() ? sprintf('-indexes:%d', count($diff->getDroppedIndexes())) : null,
            [] !== $diff->getAddedForeignKeys() ? sprintf('+fk:%d', count($diff->getAddedForeignKeys())) : null,
            [] !== $diff->getDroppedForeignKeyConstraintNames() ? sprintf('-fk:%d', count($diff->getDroppedForeignKeyConstraintNames())) : null,
        ]));

        if ($ignoreIndexNames && [] === $reasons && [] !== $renamedIndexes) {
            return null;
        }

        if (!$ignoreIndexNames && [] !== $renamedIndexes) {
            $reasons[] = sprintf('~indexes:%d', count($renamedIndexes));
        }

        return [] === $reasons ? ['unclassified table diff'] : $reasons;
    }

    /**
     * @param list<string>                $tableNames
     * @param array<string, list<string>> $failures
     * @param array<string, int>          $missingByPrefix
     * @param array<string, int>          $driftedByPrefix
     */
    private function writeValidationResult(
        InputInterface $input,
        OutputInterface $output,
        array $tableNames,
        array $failures,
        int $missingTables,
        array $missingByPrefix,
        array $driftedByPrefix,
    ): int {
        if ([] === $failures) {
            $output->writeln(sprintf('<info>Mapped schema is in sync: %d ORM-owned tables validated; unrelated shared tables ignored.</info>', count($tableNames)));

            return Command::SUCCESS;
        }

        $output->writeln(sprintf(
            '<error>Mapped schema drift: %d of %d ORM-owned tables differ (%d missing, %d present-but-drifted).</error>',
            count($failures),
            count($tableNames),
            $missingTables,
            count($failures) - $missingTables,
        ));

        if ($input->getOption('summary')) {
            arsort($missingByPrefix);
            arsort($driftedByPrefix);
            $output->writeln('Missing by prefix: '.json_encode($missingByPrefix, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $output->writeln('Drifted by prefix: '.json_encode($driftedByPrefix, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return Command::FAILURE;
        }

        foreach ($failures as $tableName => $reasons) {
            $output->writeln(sprintf(' - %s: %s', $tableName, implode(', ', $reasons)));
        }

        return Command::FAILURE;
    }
}
