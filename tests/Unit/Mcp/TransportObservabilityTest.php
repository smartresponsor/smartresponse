<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;

final class TransportObservabilityTest extends TestCase
{
    public function testGatingMcpHasObservabilityAndAuthFailureModesDocumentedInCode(): void
    {
        $repoRoot = dirname(__DIR__, 4);
        $wranglerToml = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'Gating-mcp'.DIRECTORY_SEPARATOR.'wrangler.toml');
        $source = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'Gating-mcp'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'index.ts');
        $smoke = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'Gating-mcp'.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'smoke-remote-mcp.mjs');

        self::assertIsString($wranglerToml);
        self::assertIsString($source);
        self::assertIsString($smoke);

        self::assertStringContainsString('[observability]', $wranglerToml);
        self::assertStringContainsString('enabled = true', $wranglerToml);

        self::assertStringContainsString('GATING_MCP_AUTH_TOKEN', $source);
        self::assertStringContainsString('GATING_MCP_SYNC_TOKEN', $source);
        self::assertStringContainsString('gating.health', $source);
        self::assertStringContainsString('status: 401', $source);
        self::assertStringContainsString('status: 403', $source);
        self::assertStringContainsString('Unauthorized publication.', $source);

        self::assertStringContainsString('assertUnauthorizedBearerRejected', $smoke);
        self::assertStringContainsString('assertUnknownToolRejected(authToken)', $smoke);
        self::assertStringContainsString('parseSmokeMode(process.argv.slice(2))', $smoke);
        self::assertStringContainsString('preflightValidateSecrets(smokeMode)', $smoke);
        self::assertStringContainsString('runReadonlySmoke(authToken)', $smoke);
        self::assertStringContainsString('runAdminSmoke(transport, syncToken', $smoke);
        self::assertStringContainsString('redactSensitiveText', $smoke);
        self::assertStringContainsString('gating.publish_policy', $smoke);
        self::assertStringContainsString('gating.rollback_policy', $smoke);
        self::assertStringContainsString('gating.health', $smoke);
        self::assertStringContainsString('gating.__missing_tool__', $smoke);
        self::assertStringContainsString('-32601', $smoke);
        self::assertStringContainsString('CONNECT_TIMEOUT_MS = 15000', $smoke);
        self::assertStringContainsString('HTTP_TIMEOUT_MS = 15000', $smoke);
        self::assertStringContainsString('RETRY_ATTEMPTS = 3', $smoke);
        self::assertStringContainsString('RETRY_DELAY_MS = 500', $smoke);
        self::assertStringContainsString('retryWithTimeout(() => client.connect(transport), CONNECT_TIMEOUT_MS, "MCP connect")', $smoke);
        self::assertStringContainsString('AbortSignal.timeout?.(HTTP_TIMEOUT_MS)', $smoke);
        self::assertStringContainsString('class RetryableHttpError extends Error', $smoke);
    }
}
