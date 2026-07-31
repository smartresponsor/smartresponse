<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Diagnostics\AppUrlAuditService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:url-audit:run', description: 'Run the maximum-coverage App platform URL audit.')]
final class AppUrlAuditRunCommand extends Command
{
    public function __construct(private readonly AppUrlAuditService $audit)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('base-url', null, InputOption::VALUE_REQUIRED, 'HTTP origin to probe.', 'http://127.0.0.1:8000');
        $this->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'Per-request timeout in seconds.', '15');
        $this->addOption('fail-on-findings', null, InputOption::VALUE_NONE, 'Return failure when root causes exist.');
        $this->addOption('path', null, InputOption::VALUE_REQUIRED, 'Probe one path through the Symfony kernel.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getOption('path');
        if (is_string($path) && '' !== $path) {
            $probe = $this->audit->probePath($path);
            $output->writeln(json_encode($probe, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return [] === $probe['findings'] ? Command::SUCCESS : Command::FAILURE;
        }

        $report = $this->audit->run((string) $input->getOption('base-url'), (int) $input->getOption('timeout'));
        $output->writeln(json_encode([
            'runDirectory' => $report['runDirectory'],
            'summary' => $report['summary'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return $input->getOption('fail-on-findings') && $report['summary']['rootCauses'] > 0
            ? Command::FAILURE
            : Command::SUCCESS;
    }
}
