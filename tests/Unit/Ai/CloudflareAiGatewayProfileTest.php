<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ai;

use PHPUnit\Framework\TestCase;

final class CloudflareAiGatewayProfileTest extends TestCase
{
    public function testDefaultCodexPathRemainsChatgptAuthenticatedAndUnchanged(): void
    {
        $userProfile = getenv('USERPROFILE');
        self::assertIsString($userProfile);
        self::assertNotSame('', $userProfile);

        $defaultConfigPath = $userProfile.DIRECTORY_SEPARATOR.'.codex'.DIRECTORY_SEPARATOR.'config.toml';
        $authPath = $userProfile.DIRECTORY_SEPARATOR.'.codex'.DIRECTORY_SEPARATOR.'auth.json';

        self::assertFileExists($defaultConfigPath);
        self::assertFileExists($authPath);

        $defaultConfig = file_get_contents($defaultConfigPath);
        $authJson = file_get_contents($authPath);
        self::assertIsString($defaultConfig);
        self::assertIsString($authJson);

        $auth = json_decode($authJson, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($auth);
        self::assertSame('chatgpt', $auth['auth_mode'] ?? null);
        self::assertStringContainsString('model = "gpt-5.4-mini"', $defaultConfig);
        self::assertStringContainsString('[mcp_servers.gating-mcp]', $defaultConfig);
        self::assertStringContainsString('[mcp_servers.codebase-memory-mcp]', $defaultConfig);
        self::assertStringNotContainsString('cloudflare-pilot', $defaultConfig);
    }

    public function testCloudflarePilotProfileUsesResponsesApiAndHeaderOnlySecrets(): void
    {
        $userProfile = getenv('USERPROFILE');
        self::assertIsString($userProfile);
        self::assertNotSame('', $userProfile);

        $profilePath = $userProfile.DIRECTORY_SEPARATOR.'.codex'.DIRECTORY_SEPARATOR.'cloudflare-pilot.config.toml';
        self::assertFileExists($profilePath);

        $profile = file_get_contents($profilePath);
        self::assertIsString($profile);

        self::assertStringContainsString('model = "openai/gpt-4.1"', $profile);
        self::assertStringContainsString('model_provider = "cloudflare-rest"', $profile);
        self::assertStringContainsString('base_url = "https://api.cloudflare.com/client/v4/accounts/ea89ff31882730b6044b8e0a1df0eb2b/ai/v1"', $profile);
        self::assertStringNotContainsString('/ai/v1/responses', $profile);
        self::assertStringContainsString('wire_api = "responses"', $profile);
        self::assertStringContainsString('env_key = "CLOUDFLARE_API_TOKEN"', $profile);
        self::assertStringContainsString('cf-aig-gateway-id', $profile);
        self::assertStringContainsString('CF_AIG_GATEWAY_ID', $profile);
        self::assertStringContainsString('cf-aig-collect-log-payload', $profile);
        self::assertStringContainsString('request_max_retries = 0', $profile);
        self::assertStringContainsString('stream_max_retries = 0', $profile);
        self::assertStringContainsString('requires_openai_auth = false', $profile);
        self::assertStringContainsString('supports_websockets = false', $profile);

        self::assertStringNotContainsString('/compat', $profile);
        self::assertStringNotContainsString('gateway.ai.cloudflare.com/v1', $profile);
        self::assertStringNotContainsString('OpenRouter', $profile);
        self::assertStringNotContainsString('Anthropic', $profile);
        self::assertStringNotContainsString('Google', $profile);
        self::assertStringNotContainsString('Workers AI', $profile);
    }
}
