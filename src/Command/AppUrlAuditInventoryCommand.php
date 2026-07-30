<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Diagnostics\AppUrlAuditService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:url-audit:inventory', description: 'Inventory the App platform URL-audit surface.')]
final class AppUrlAuditInventoryCommand extends Command
{
    public function __construct(private readonly AppUrlAuditService $audit)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('output', null, InputOption::VALUE_REQUIRED, 'Optional JSON output file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $json = json_encode($this->audit->inventory(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
        $outputFile = $input->getOption('output');
        if (is_string($outputFile) && '' !== $outputFile) {
            $directory = dirname($outputFile);
            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
            file_put_contents($outputFile, $json);
        }
        $output->write($json);

        return Command::SUCCESS;
    }
}
