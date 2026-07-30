<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:url-audit:publish-github', description: 'Publish fingerprinted URL-audit failures to GitHub.')]
final class AppUrlAuditPublishGithubCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('report', InputArgument::REQUIRED, 'Path to report.json.');
        $this->addOption('repository', null, InputOption::VALUE_REQUIRED, 'GitHub owner/repository.', 'smartresponsor/smartresponse');
        $this->addOption('date', null, InputOption::VALUE_REQUIRED, 'Milestone date.', date('Y-m-d'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $reportFile = (string) $input->getArgument('report');
        $report = json_decode((string) file_get_contents($reportFile), true, 512, JSON_THROW_ON_ERROR);
        $repository = (string) $input->getOption('repository');
        $milestone = 'Platform URL Audit - '.(string) $input->getOption('date');

        $existingMilestones = $this->runGh(['gh', 'api', 'repos/'.$repository.'/milestones', '--paginate']);
        $milestones = json_decode($existingMilestones, true, 512, JSON_THROW_ON_ERROR);
        if (!array_filter($milestones, static fn (array $item): bool => ($item['title'] ?? null) === $milestone)) {
            $this->runGh(['gh', 'api', 'repos/'.$repository.'/milestones', '-f', 'title='.$milestone]);
        }

        $urls = [];
        foreach (($report['failures'] ?? []) as $failure) {
            $fingerprint = (string) $failure['fingerprint'];
            $title = sprintf('[URL Audit] %s (%s)', $failure['type'], substr($fingerprint, 0, 12));
            $body = "URL-AUDIT-FINGERPRINT:$fingerprint\n\nRun: `{$report['runId']}`\n\nRoute: `{$failure['route']}`\nPath: `{$failure['path']}`\nStatus: `{$failure['status']}`\nOccurrences: {$failure['occurrences']}\n\n```text\n{$failure['evidence']}\n```";
            $search = json_decode($this->runGh(['gh', 'issue', 'list', '--repo', $repository, '--state', 'all', '--search', 'in:body URL-AUDIT-FINGERPRINT:'.$fingerprint, '--json', 'number,state,url']), true, 512, JSON_THROW_ON_ERROR);
            if ([] === $search) {
                $urls[] = trim($this->runGh(['gh', 'issue', 'create', '--repo', $repository, '--title', $title, '--body', $body, '--milestone', $milestone]));
                continue;
            }

            $issue = $search[0];
            $number = (string) $issue['number'];
            $this->runGh(['gh', 'issue', 'edit', $number, '--repo', $repository, '--title', $title, '--body', $body, '--milestone', $milestone]);
            if ('CLOSED' === strtoupper((string) ($issue['state'] ?? ''))) {
                $this->runGh(['gh', 'issue', 'reopen', $number, '--repo', $repository]);
            }
            $urls[] = (string) ($issue['url'] ?? '');
        }

        $output->writeln(json_encode(['milestone' => $milestone, 'issues' => $urls], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }

    /** @param list<string> $command */
    private function runGh(array $command): string
    {
        $process = new \Symfony\Component\Process\Process($command, null, null, null, 120);
        $process->mustRun();

        return $process->getOutput();
    }
}
