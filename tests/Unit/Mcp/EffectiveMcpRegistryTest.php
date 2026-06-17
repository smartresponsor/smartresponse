<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;

final class EffectiveMcpRegistryTest extends TestCase
{
    public function testCodexConfigExposesExpectedServersAndDocFallbacks(): void
    {
        $userProfile = getenv('USERPROFILE');
        self::assertIsString($userProfile);
        self::assertNotSame('', $userProfile);

        $globalConfigPath = $userProfile.DIRECTORY_SEPARATOR.'.codex'.DIRECTORY_SEPARATOR.'config.toml';
        $workspaceConfigPath = dirname(__DIR__, 4).DIRECTORY_SEPARATOR.'.codex'.DIRECTORY_SEPARATOR.'config.toml';

        self::assertFileExists($globalConfigPath);
        self::assertFileExists($workspaceConfigPath);

        $globalConfig = file_get_contents($globalConfigPath);
        $workspaceConfig = file_get_contents($workspaceConfigPath);

        self::assertIsString($globalConfig);
        self::assertIsString($workspaceConfig);

        self::assertStringContainsString('[mcp_servers.gating-mcp]', $globalConfig);
        self::assertStringContainsString('url = "https://gating-mcp.taa0662621456.workers.dev/mcp"', $globalConfig);
        self::assertStringContainsString('[mcp_servers.codebase-memory-mcp]', $globalConfig);
        self::assertStringContainsString('command = "C:/Users/Admin/.local/bin/codebase-memory-mcp.exe"', $globalConfig);
        self::assertStringNotContainsString("[projects.'D:\\']", $globalConfig);
        self::assertStringNotContainsString("[projects.'\\\\?\\D:\\']", $globalConfig);
        self::assertStringNotContainsString("[projects.'C:\\Users\\Admin']", $globalConfig);
        self::assertStringNotContainsString("[projects.'C:\\Users\\Admin\\Documents']", $globalConfig);
        self::assertStringContainsString('project_doc_fallback_filenames = ["README.md", "AGENTS.md"]', $workspaceConfig);
    }

    public function testGatingMcpReadmeMatchesRegisteredTools(): void
    {
        $readmePath = dirname(__DIR__, 4).DIRECTORY_SEPARATOR.'Gating-mcp'.DIRECTORY_SEPARATOR.'README.md';
        $sourcePath = dirname(__DIR__, 4).DIRECTORY_SEPARATOR.'Gating-mcp'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'index.ts';

        $readme = file_get_contents($readmePath);
        $source = file_get_contents($sourcePath);

        self::assertIsString($readme);
        self::assertIsString($source);

        preg_match_all('/^\- `([^`]+)`$/m', $readme, $readmeMatches);
        preg_match_all('/registerTool\(\s*"([^"]+)"/m', $source, $sourceMatches);

        $readmeTools = array_values(array_unique($readmeMatches[1]));
        $sourceTools = array_values(array_unique($sourceMatches[1]));

        sort($readmeTools);
        sort($sourceTools);

        self::assertSame($sourceTools, $readmeTools);
    }
}
