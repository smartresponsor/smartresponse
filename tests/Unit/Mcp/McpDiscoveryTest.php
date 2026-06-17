<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;

final class McpDiscoveryTest extends TestCase
{
    public function testCodebaseMemoryDiscoverySurfaceExposesTheWorkspaceAndReadyIndexStatus(): void
    {
        $listProjects = self::runCodebaseMemoryCli(['cli', 'list_projects', '{}']);
        $indexStatus = self::runCodebaseMemoryCli(['cli', 'index_status', '{"project":"D-PhpstormProjects-www"}']);

        $projectsPayload = json_decode($listProjects, true);
        $statusPayload = json_decode($indexStatus, true);

        self::assertIsArray($projectsPayload, $listProjects);
        self::assertIsArray($statusPayload, $indexStatus);

        $projectNames = array_map(static fn (array $project): string => $project['name'] ?? '', $projectsPayload['projects'] ?? []);

        self::assertContains('D-PhpstormProjects-www', $projectNames, $listProjects);
        self::assertSame('D-PhpstormProjects-www', $statusPayload['project'] ?? null, $indexStatus);
        self::assertSame('ready', $statusPayload['status'] ?? null, $indexStatus);
    }

    /**
     * @param list<string> $arguments
     */
    private static function runCodebaseMemoryCli(array $arguments): string
    {
        $binary = 'C:\\Users\\Admin\\.local\\bin\\codebase-memory-mcp.exe';
        $command = array_merge([$binary], $arguments);
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);

        self::assertIsResource($process);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        self::assertSame(0, $exitCode, $stderr);

        return (string) $stdout;
    }
}
