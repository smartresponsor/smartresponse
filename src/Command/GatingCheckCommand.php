<?php

// Copyright (c) 2026 Oleksandr Tishchenko / Marketing America Corp

declare(strict_types=1);

namespace App\Command;

use Gating\Gate\Service\GatingRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'gating:check', description: 'Run the packaged Gating runner against the App workspace.')]
final class GatingCheckCommand extends Command
{
    public function __construct(private readonly KernelInterface $kernel)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOptionOnce('target', InputOption::VALUE_REQUIRED, 'Target path to inspect.', '.');
        $this->addOptionOnce('format', InputOption::VALUE_REQUIRED, 'Output format.', 'text');
        $this->addOptionOnce('json', InputOption::VALUE_NONE, 'Emit JSON report.');
        $this->addOptionOnce('report-file', InputOption::VALUE_REQUIRED, 'Write the JSON report to this file.');
        $this->addOptionOnce('rule-set', InputOption::VALUE_REQUIRED, 'Rule-set profile path.');
        $this->addOptionOnce('severity-config', InputOption::VALUE_REQUIRED, 'Severity configuration file path.');
        $this->addOptionOnce('policy-root', InputOption::VALUE_REQUIRED, 'Explicit .gating policy root.');
        $this->addOptionOnce('root', InputOption::VALUE_REQUIRED, 'Workspace root for discovery and fallback resolution.', '.');
        $this->addOptionOnce('max-depth', InputOption::VALUE_REQUIRED, 'Maximum discovery depth.', 2);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $runner = new GatingRunner($this->kernel->getProjectDir());

        return $runner->runCheck($input, $output, 'check');
    }

    private function addOptionOnce(string $name, int $mode, string $description, mixed $default = null): void
    {
        if ($this->getDefinition()->hasOption($name)) {
            return;
        }

        if (null === $default) {
            $this->addOption($name, null, $mode, $description);

            return;
        }

        $this->addOption($name, null, $mode, $description, $default);
    }
}
