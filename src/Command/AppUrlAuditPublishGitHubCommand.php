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
final class AppUrlAuditPublishGitHubCommand extends Command
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

        $existingIssues = json_decode($this->runGh([
            'gh', 'issue', 'list', '--repo', $repository, '--state', 'all', '--limit', '1000', '--json', 'number,state,url,body',
        ]), true, 512, JSON_THROW_ON_ERROR);
        $issuesByFingerprint = [];
        foreach ($existingIssues as $issue) {
            if (preg_match('/URL-AUDIT-FINGERPRINT:([a-f0-9]{64})/', (string) ($issue['body'] ?? ''), $match)) {
                $issuesByFingerprint[$match[1]] = $issue;
            }
        }

        $urls = [];
        $findings = $this->publishableFindings($report);
        foreach ($findings as $index => $failure) {
            $fingerprint = (string) $failure['fingerprint'];
            $title = sprintf('[URL Audit] %s (%s)', $failure['type'], substr($fingerprint, 0, 12));
            $affectedPaths = implode("\n", array_map(
                static fn (string $path): string => '- `'.$path.'`',
                (array) ($failure['affectedPaths'] ?? [$failure['path']]),
            ));
            $body = "URL-AUDIT-FINGERPRINT:$fingerprint\n\nRun: `{$report['runId']}`\n\nRoute: `{$failure['route']}`\nPath: `{$failure['path']}`\nStatus: `{$failure['status']}`\nOccurrences: {$failure['occurrences']}\n\nAffected paths:\n{$affectedPaths}\n\n```text\n{$failure['evidence']}\n```";
            $issue = $issuesByFingerprint[$fingerprint] ?? null;
            if (!is_array($issue)) {
                $url = trim($this->runGh(['gh', 'issue', 'create', '--repo', $repository, '--title', $title, '--body', $body, '--milestone', $milestone]));
                $urls[] = $url;
                $output->writeln(sprintf('[%d/%d] created %s', $index + 1, count($findings), $url));
                continue;
            }

            $number = (string) $issue['number'];
            $this->runGh(['gh', 'issue', 'edit', $number, '--repo', $repository, '--title', $title, '--body', $body, '--milestone', $milestone]);
            if ('CLOSED' === strtoupper((string) ($issue['state'] ?? ''))) {
                $this->runGh(['gh', 'issue', 'reopen', $number, '--repo', $repository]);
            }
            $urls[] = (string) ($issue['url'] ?? '');
            $output->writeln(sprintf('[%d/%d] updated %s', $index + 1, count($findings), (string) ($issue['url'] ?? '#'.$number)));
        }

        $output->writeln(json_encode(['milestone' => $milestone, 'issues' => $urls], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $report
     *
     * @return list<array<string, mixed>>
     */
    private function publishableFindings(array $report): array
    {
        $findings = array_values(array_filter(
            (array) ($report['failures'] ?? []),
            'is_array',
        ));

        $performance = $report['performance'] ?? null;
        $routes = is_array($performance) ? ($performance['routes'] ?? []) : [];
        if (!is_array($routes)) {
            return $findings;
        }

        foreach ($routes as $route) {
            if (!is_array($route)) {
                continue;
            }

            $classification = (string) ($route['classification'] ?? '');
            if (!in_array($classification, ['sustained_slow', 'unstable', 'payload_heavy', 'first_touch'], true)) {
                continue;
            }

            $routeName = (string) ($route['route'] ?? 'unknown');
            $path = (string) ($route['path'] ?? '/');
            $fingerprint = hash('sha256', implode('|', ['performance', $classification, $routeName, $path]));
            $evidence = sprintf(
                'classification=%s; responseBytes=%d; coldMs=%d; warmAvgMs=%d; p50Ms=%d; p95Ms=%d; maxMs=%d; investigate=%s',
                $classification,
                (int) ($route['responseBytes'] ?? 0),
                (int) ($route['coldMs'] ?? 0),
                (int) ($route['warmAvgMs'] ?? 0),
                (int) ($route['p50Ms'] ?? 0),
                (int) ($route['p95Ms'] ?? 0),
                (int) ($route['maxMs'] ?? 0),
                (string) ($route['investigate'] ?? ''),
            );

            $findings[] = [
                'fingerprint' => $fingerprint,
                'type' => 'performance_'.$classification,
                'route' => $routeName,
                'path' => $path,
                'status' => (int) ($route['status'] ?? 0),
                'occurrences' => 1,
                'affectedPaths' => [$path],
                'evidence' => $evidence,
            ];
        }

        return $findings;
    }

    /** @param list<string> $command */
    private function runGh(array $command): string
    {
        $process = new \Symfony\Component\Process\Process($command, null, null, null, 120);
        $process->mustRun();

        return $process->getOutput();
    }
}
