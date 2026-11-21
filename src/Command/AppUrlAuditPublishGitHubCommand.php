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
        $reportFile = $input->getArgument('report');
        if (!is_string($reportFile)) {
            throw new \InvalidArgumentException('Report path must be a string.');
        }
        $contents = file_get_contents($reportFile);
        if (false === $contents) {
            throw new \RuntimeException('Unable to read URL audit report.');
        }
        $report = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($report) || array_is_list($report)) {
            throw new \UnexpectedValueException('URL audit report must be a JSON object.');
        }
        $repository = $input->getOption('repository');
        $date = $input->getOption('date');
        if (!is_string($repository) || !is_string($date)) {
            throw new \InvalidArgumentException('Repository and date must be strings.');
        }
        $milestone = 'Platform URL Audit - '.$date;

        $existingMilestones = $this->runGh(['gh', 'api', 'repos/'.$repository.'/milestones', '--paginate']);
        $milestones = json_decode($existingMilestones, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($milestones)) {
            throw new \UnexpectedValueException('GitHub milestones response must be an array.');
        }
        if (!array_filter($milestones, static fn (mixed $item): bool => is_array($item) && ($item['title'] ?? null) === $milestone)) {
            $this->runGh(['gh', 'api', 'repos/'.$repository.'/milestones', '-f', 'title='.$milestone]);
        }

        $existingIssues = json_decode($this->runGh([
            'gh', 'issue', 'list', '--repo', $repository, '--state', 'all', '--limit', '1000', '--json', 'number,state,url,body',
        ]), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($existingIssues)) {
            throw new \UnexpectedValueException('GitHub issues response must be an array.');
        }
        $issuesByFingerprint = [];
        foreach ($existingIssues as $issue) {
            if (!is_array($issue) || array_is_list($issue)) {
                continue;
            }
            /** @var array<string, mixed> $issue */
            $issueBody = $issue['body'] ?? null;
            if (is_string($issueBody) && preg_match('/URL-AUDIT-FINGERPRINT:([a-f0-9]{64})/', $issueBody, $match)) {
                $issuesByFingerprint[$match[1]] = $issue;
            }
        }

        $urls = [];
        /** @var array<string, mixed> $report */
        $findings = $this->publishableFindings($report);
        foreach ($findings as $index => $failure) {
            $fingerprint = $this->stringField($failure, 'fingerprint');
            $type = $this->stringField($failure, 'type');
            $title = sprintf('[URL Audit] %s (%s)', $type, substr($fingerprint, 0, 12));
            $rawPaths = $failure['affectedPaths'] ?? [$this->stringField($failure, 'path')];
            if (!is_array($rawPaths)) {
                throw new \UnexpectedValueException('Affected paths must be an array.');
            }
            $paths = [];
            foreach ($rawPaths as $rawPath) {
                if (!is_string($rawPath)) {
                    throw new \UnexpectedValueException('Affected path must be a string.');
                }
                $paths[] = '- `'.$rawPath.'`';
            }
            $affectedPaths = implode("\n", $paths);
            $runId = $this->stringField($report, 'runId');
            $route = $this->stringField($failure, 'route');
            $path = $this->stringField($failure, 'path');
            $status = $this->scalarField($failure, 'status');
            $occurrences = $this->scalarField($failure, 'occurrences');
            $evidence = $this->stringField($failure, 'evidence');
            $body = "URL-AUDIT-FINGERPRINT:$fingerprint\n\nRun: `{$runId}`\n\nRoute: `{$route}`\nPath: `{$path}`\nStatus: `{$status}`\nOccurrences: {$occurrences}\n\nAffected paths:\n{$affectedPaths}\n\n```text\n{$evidence}\n```";
            $issue = $issuesByFingerprint[$fingerprint] ?? null;
            if (!is_array($issue)) {
                $url = trim($this->runGh(['gh', 'issue', 'create', '--repo', $repository, '--title', $title, '--body', $body, '--milestone', $milestone]));
                $urls[] = $url;
                $output->writeln(sprintf('[%d/%d] created %s', $index + 1, count($findings), $url));
                continue;
            }

            $number = $this->scalarField($issue, 'number');
            $this->runGh(['gh', 'issue', 'edit', $number, '--repo', $repository, '--title', $title, '--body', $body, '--milestone', $milestone]);
            if ('CLOSED' === strtoupper($this->stringField($issue, 'state'))) {
                $this->runGh(['gh', 'issue', 'reopen', $number, '--repo', $repository]);
            }
            $issueUrl = $this->stringField($issue, 'url');
            $urls[] = $issueUrl;
            $output->writeln(sprintf('[%d/%d] updated %s', $index + 1, count($findings), $issueUrl));
        }

        $output->writeln(json_encode(['milestone' => $milestone, 'issues' => $urls], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }

    /** @param array<string, mixed> $record */
    private function integerField(array $record, string $key): int
    {
        $value = $record[$key] ?? 0;
        if (!is_int($value) && !is_float($value)) {
            throw new \UnexpectedValueException(sprintf('Field "%s" must be numeric.', $key));
        }

        return (int) $value;
    }

    /** @param array<string, mixed> $record */
    private function stringField(array $record, string $key): string
    {
        $value = $record[$key] ?? null;
        if (!is_string($value)) {
            throw new \UnexpectedValueException(sprintf('Field "%s" must be a string.', $key));
        }

        return $value;
    }

    /** @param array<string, mixed> $record */
    private function scalarField(array $record, string $key): string
    {
        $value = $record[$key] ?? null;
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new \UnexpectedValueException(sprintf('Field "%s" must be scalar.', $key));
        }

        return (string) $value;
    }

    /**
     * @param array<string, mixed> $report
     *
     * @return list<array<string, mixed>>
     */
    private function publishableFindings(array $report): array
    {
        /** @var list<array<string, mixed>> $findings */
        $findings = [];
        $failures = $report['failures'] ?? [];
        if (!is_array($failures)) {
            throw new \UnexpectedValueException('Failures must be an array.');
        }
        foreach ($failures as $failure) {
            if (is_array($failure) && !array_is_list($failure)) {
                $findings[] = array_filter($failure, static fn (string|int $key): bool => is_string($key), ARRAY_FILTER_USE_KEY);
            }
        }

        $performance = $report['performance'] ?? null;
        $routes = is_array($performance) ? ($performance['routes'] ?? []) : [];
        if (!is_array($routes)) {
            return $findings;
        }

        foreach ($routes as $route) {
            if (!is_array($route) || array_is_list($route)) {
                continue;
            }
            /** @var array<string, mixed> $route */
            $classification = $this->stringField($route, 'classification');
            if (!in_array($classification, ['sustained_slow', 'unstable', 'payload_heavy', 'first_touch'], true)) {
                continue;
            }

            $routeName = $this->stringField($route, 'route');
            $path = $this->stringField($route, 'path');
            $fingerprint = hash('sha256', implode('|', ['performance', $classification, $routeName, $path]));
            $evidence = sprintf(
                'classification=%s; responseBytes=%d; coldMs=%d; warmAvgMs=%d; p50Ms=%d; p95Ms=%d; maxMs=%d; investigate=%s',
                $classification,
                $this->integerField($route, 'responseBytes'),
                $this->integerField($route, 'coldMs'),
                $this->integerField($route, 'warmAvgMs'),
                $this->integerField($route, 'p50Ms'),
                $this->integerField($route, 'p95Ms'),
                $this->integerField($route, 'maxMs'),
                is_string($route['investigate'] ?? null) ? $route['investigate'] : '',
            );

            $findings[] = [
                'fingerprint' => $fingerprint,
                'type' => 'performance_'.$classification,
                'route' => $routeName,
                'path' => $path,
                'status' => $this->integerField($route, 'status'),
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
