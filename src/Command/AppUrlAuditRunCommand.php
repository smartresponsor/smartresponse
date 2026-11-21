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
        $samples = $this->positiveInteger($input->getOption('samples'), 1);
        $slowMs = $this->positiveInteger($input->getOption('slow-ms'), 250);
        $path = $this->nonEmptyString($input->getOption('path'));

        return null !== $path
            ? $this->runPathAudit($input, $output, $path, $samples, $slowMs)
            : $this->runFullAudit($input, $output, $samples, $slowMs);
    }

    private function runPathAudit(InputInterface $input, OutputInterface $output, string $path, int $samples, int $slowMs): int
    {
        $result = $this->audit->probePath(
            $path,
            $samples,
            $slowMs,
            $this->nonEmptyString($input->getOption('warmup-path')),
        );
        $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $probe = $result['probe'] ?? null;
        $findings = is_array($probe) && is_array($probe['findings'] ?? null) ? $probe['findings'] : [];

        return [] === $findings ? Command::SUCCESS : Command::FAILURE;
    }

    private function runFullAudit(InputInterface $input, OutputInterface $output, int $samples, int $slowMs): int
    {
        $report = $this->audit->run(
            $this->stringOrDefault($input->getOption('base-url'), 'http://127.0.0.1:8000'),
            $this->positiveInteger($input->getOption('timeout'), 15),
            $samples,
            $slowMs,
        );
        $runDirectory = $report['runDirectory'] ?? null;
        $summary = $report['summary'] ?? null;
        if (!is_string($runDirectory) || !is_array($summary)) {
            $output->writeln('<error>URL audit returned an invalid report shape.</error>');

            return Command::FAILURE;
        }

        $output->writeln(json_encode([
            'runDirectory' => $runDirectory,
            'summary' => $summary,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        $rootCauses = $summary['rootCauses'] ?? 0;

        return true === $input->getOption('fail-on-findings') && is_numeric($rootCauses) && (int) $rootCauses > 0
            ? Command::FAILURE
            : Command::SUCCESS;
    }

    private function positiveInteger(mixed $value, int $default): int
    {
        return is_numeric($value) ? max(1, (int) $value) : $default;
    }

    private function nonEmptyString(mixed $value): ?string
    {
        return is_string($value) && '' !== $value ? $value : null;
    }

    private function stringOrDefault(mixed $value, string $default): string
    {
        return is_string($value) ? $value : $default;
    }
}
