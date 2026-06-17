<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;

final class McpDeploymentHygieneTest extends TestCase
{
    public function testGatingMcpDeploymentDocsAndWorkerConfigExposeTheExpectedOperationalSurface(): void
    {
        $repoRoot = dirname(__DIR__, 4);
        $readme = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'Gating-mcp'.DIRECTORY_SEPARATOR.'README.md');
        $packageJson = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'Gating-mcp'.DIRECTORY_SEPARATOR.'package.json');
        $wranglerToml = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'Gating-mcp'.DIRECTORY_SEPARATOR.'wrangler.toml');

        self::assertIsString($readme);
        self::assertIsString($packageJson);
        self::assertIsString($wranglerToml);

        self::assertStringContainsString('smoke:readonly', $packageJson);
        self::assertStringContainsString('smoke:admin', $packageJson);
        self::assertStringContainsString('"smoke": "node scripts/smoke-remote-mcp.mjs all"', $packageJson);

        self::assertStringContainsString('Read-only smoke:', $readme);
        self::assertStringContainsString('Admin smoke:', $readme);
        self::assertStringContainsString('npm run smoke:readonly', $readme);
        self::assertStringContainsString('npm run smoke:admin', $readme);
        self::assertStringContainsString('npm run smoke', $readme);
        self::assertStringContainsString('Preflight validation fails fast if required client-side secrets are missing.', $readme);
        self::assertStringContainsString('gating.health', $readme);
        self::assertStringContainsString('gating.describe', $readme);
        self::assertStringContainsString('npx wrangler deploy', $readme);
        self::assertStringContainsString('Cloudflare Access', $readme);
        self::assertStringContainsString('The MCP endpoint is available at `/mcp`.', $readme);

        self::assertStringContainsString('[observability]', $wranglerToml);
        self::assertStringContainsString('enabled = true', $wranglerToml);
        self::assertStringContainsString('name = "gating-mcp"', $wranglerToml);
        self::assertStringContainsString('main = "src/index.ts"', $wranglerToml);
    }
}
