<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\CodeMemory\CodeMemoryScopeResolver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'memory:scope:resolve', description: 'Resolve Code Memory active/read/edit scope for a workspace path.')]
final class CodeMemoryScopeResolveCommand extends Command
{
    public function __construct(private readonly CodeMemoryScopeResolver $resolver)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('cwd', null, InputOption::VALUE_REQUIRED, 'Working directory used to resolve the active Code Memory scope.', getcwd() ?: '.');
        $this->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON output. This is the default stable interface.');
        $this->addOption('write-cache', null, InputOption::VALUE_NONE, 'Write generated .codebase-memory/resolved-scope.json under the active root.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cwd = $input->getOption('cwd');
        if (!is_string($cwd) || '' === trim($cwd)) {
            $output->writeln('<error>Option --cwd must be a non-empty path.</error>');

            return Command::FAILURE;
        }

        $scope = $this->resolver->resolve($cwd);

        try {
            $json = json_encode($scope, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $output->writeln('<error>Unable to encode Code Memory scope: '.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        $output->writeln($json);
        if (true === $input->getOption('write-cache')) {
            $cacheFile = $this->writeScopeCache($scope['activeRoot'], $json);
            $output->writeln('<info>Wrote generated Code Memory scope cache: '.$cacheFile.'</info>');
        }

        return Command::SUCCESS;
    }

    private function writeScopeCache(string $activeRoot, string $json): string
    {
        $root = realpath($activeRoot) ?: $activeRoot;
        $cacheDir = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.codebase-memory';
        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
            throw new \RuntimeException('Unable to create Code Memory cache directory: '.$cacheDir);
        }

        $cacheFile = $cacheDir.DIRECTORY_SEPARATOR.'resolved-scope.json';
        if (false === file_put_contents($cacheFile, $json.PHP_EOL)) {
            throw new \RuntimeException('Unable to write Code Memory scope cache: '.$cacheFile);
        }

        return $cacheFile;
    }
}
