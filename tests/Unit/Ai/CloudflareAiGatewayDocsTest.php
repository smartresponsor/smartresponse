<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ai;

use PHPUnit\Framework\TestCase;

final class CloudflareAiGatewayDocsTest extends TestCase
{
    public function testDocsSeparateDefaultCanaryPlannedAndTargetStates(): void
    {
        $repoRoot = dirname(__DIR__, 4);
        $architecture = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'AI_GATEWAY_ARCHITECTURE.md');
        $inventory = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'AI_GATEWAY_INVENTORY.md');
        $providers = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'AI_PROVIDER_REGISTRY.md');
        $routes = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'AI_ROUTE_REGISTRY.md');
        $security = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'AI_SECURITY.md');
        $cost = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'AI_COST_MODEL.md');
        $gaps = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'AI_GAPS.md');
        $roadmap = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'AI_ROADMAP.md');
        $cfAi = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'cf-ai.md');

        foreach ([$architecture, $inventory, $providers, $routes, $security, $cost, $gaps, $roadmap, $cfAi] as $content) {
            self::assertIsString($content);
            self::assertStringNotContainsString('/compat', $content);
        }

        self::assertStringContainsString('current default runtime', $architecture);
        self::assertStringContainsString('configured Cloudflare canary runtime', $architecture);
        self::assertStringContainsString('planned routes', $architecture);
        self::assertStringContainsString('target control-plane architecture', $architecture);

        self::assertStringContainsString('Configured canary runtime', $inventory);
        self::assertStringContainsString('Planned / supported', $providers);
        self::assertStringContainsString('Logical route aliases', $routes);
        self::assertStringContainsString('Cloudflare canary', $cfAi);
        self::assertStringContainsString('cf-ai-preflight.ps1', $cfAi);
        self::assertStringContainsString('cf-ai-verify.ps1', $cfAi);
        self::assertStringContainsString('cf-ai-codex-smoke.ps1', $cfAi);
        self::assertStringContainsString('cloudflare-pilot.config.toml', $cfAi);
    }

    public function testDocsCorrectProfileNamingAndModelChoice(): void
    {
        $repoRoot = dirname(__DIR__, 4);
        $inventory = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'AI_GATEWAY_INVENTORY.md');
        $routes = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'AI_ROUTE_REGISTRY.md');
        $cost = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'AI_COST_MODEL.md');

        self::assertIsString($inventory);
        self::assertIsString($routes);
        self::assertIsString($cost);

        self::assertStringContainsString('cf-ai.config.toml', $inventory);
        self::assertStringContainsString('Codex profile', $inventory);
        self::assertStringContainsString('openai/gpt-4.1', $cost);
        self::assertStringContainsString('Pricing is exposed in the Cloudflare dashboard', $cost);
        self::assertStringContainsString('tool-calling', $cost);
        self::assertStringContainsString('Cloudflare AI Gateway Unified Billing', $cost);
        self::assertStringContainsString('configured canary request path', $routes);
    }
}
