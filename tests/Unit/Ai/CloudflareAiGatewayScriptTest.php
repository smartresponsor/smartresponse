<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ai;

use PHPUnit\Framework\TestCase;

final class CloudflareAiGatewayScriptTest extends TestCase
{
    public function testScriptsReferenceRequiredEnvVarsAndRedactSecrets(): void
    {
        $repoRoot = dirname(__DIR__, 4);
        $preflight = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'tools'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'cf-ai-preflight.ps1');
        $verify = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'tools'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'cf-ai-verify.ps1');
        $smoke = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'tools'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'cf-ai-codex-smoke.ps1');
        $shapeSmoke = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'tools'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'cf-ai-codex-shape-smoke.ps1');

        self::assertIsString($preflight);
        self::assertIsString($verify);
        self::assertIsString($smoke);

        self::assertStringContainsString('CLOUDFLARE_ACCOUNT_ID', $preflight);
        self::assertStringContainsString('CLOUDFLARE_API_TOKEN', $preflight);
        self::assertStringContainsString('CF_AIG_GATEWAY_ID', $preflight);
        self::assertStringContainsString('placeholder value detected', $preflight);

        self::assertStringContainsString('/ai/v1/responses', $verify);
        self::assertStringContainsString('cf-aig-gateway-id', $verify);
        self::assertStringContainsString('cf-aig-collect-log-payload', $verify);
        self::assertStringContainsString('cf-aig-metadata', $verify);
        self::assertStringContainsString('data:', $verify);
        self::assertStringContainsString('event:', $verify);
        self::assertStringContainsString('CLOUDFLARE_BILLING', $verify);
        self::assertStringContainsString('CLOUDFLARE_REQUEST_SCHEMA', $verify);
        self::assertStringContainsString('CLOUDFLARE_STREAMING', $verify);
        self::assertStringContainsString('Redact-Text', $verify);

        self::assertStringContainsString('codex_smoke_ok auth=ChatGPT baseline=unchanged', $smoke);
        self::assertStringContainsString('ChatGPT', $smoke);
        self::assertStringContainsString('Get-FileHash', $smoke);
        self::assertStringNotContainsString('cloudflare-pilot', $smoke);
        self::assertStringNotContainsString('cloudflare-rest', $smoke);
        self::assertStringContainsString('--ignore-user-config', $shapeSmoke);
        self::assertStringContainsString('--ignore-rules', $shapeSmoke);
        self::assertStringNotContainsString('/compat', $smoke);
        self::assertStringNotContainsString('gateway.ai.cloudflare.com/v1', $smoke);

        self::assertStringContainsString('cf-ai-request-shape-probe.mjs', $shapeSmoke);
        self::assertStringContainsString('LOCAL_REQUEST_SHAPE', $shapeSmoke);
        self::assertStringContainsString('CODEX_RUNTIME', $shapeSmoke);
        self::assertStringContainsString('PROFILE_LOAD', $shapeSmoke);
        self::assertStringContainsString('shape_ok', $shapeSmoke);
        self::assertStringContainsString('codex_shape_smoke_ok', $shapeSmoke);
    }
}
