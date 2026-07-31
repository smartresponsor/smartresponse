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
        $this->addOption('warmup-path', null, InputOption::VALUE_REQUIRED, 'Probe this path once before measuring the target path.');
        $this->addOption('samples', null, InputOption::VALUE_REQUIRED, 'Number of latency samples per route.', '1');
        $this->addOption('slow-ms', null, InputOption::VALUE_REQUIRED, 'Warm-route latency threshold in milliseconds.', '250');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getOption('path');
        if (is_string($path) && '' !== $path) {
            $warmupPath = $input->getOption('warmup-path');
            $result = $this->audit->probePath(
                $path,
                max(1, (int) $input->getOption('samples')),
                max(1, (int) $input->getOption('slow-ms')),
                is_string($warmupPath) && '' !== $warmupPath ? $warmupPath : null,
            );
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return [] === ($result['probe']['findings'] ?? []) ? Command::SUCCESS : Command::FAILURE;
        }

        $report = $this->audit->run(
            (string) $input->getOption('base-url'),
            (int) $input->getOption('timeout'),
            max(1, (int) $input->getOption('samples')),
            max(1, (int) $input->getOption('slow-ms')),
        );
        $output->writeln(json_encode([
            'runDirectory' => $report['runDirectory'],
            'summary' => $report['summary'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return $input->getOption('fail-on-findings') && $report['summary']['rootCauses'] > 0
            ? Command::FAILURE
            : Command::SUCCESS;
    }
}
