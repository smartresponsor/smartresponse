<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ai;

use PHPUnit\Framework\TestCase;

final class CloudflareAiGatewayShapeProbeTest extends TestCase
{
    public function testLocalShapeProbeRecordsOnlySanitizedRequestMetadata(): void
    {
        $repoRoot = dirname(__DIR__, 4);
        $probe = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'tools'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'cf-ai-request-shape-probe.mjs');
        $smoke = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'tools'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'cf-ai-codex-shape-smoke.ps1');

        self::assertIsString($probe);
        self::assertIsString($smoke);

        foreach ([
            'request_id',
            'method',
            'url_path',
            'content_type',
            'header_names',
            'top_level_keys',
            'field_shapes',
            'model_value_type',
            'input_value_type',
            'stream_value_type',
            'tools_value_type',
            'response_format_value_type',
            'has_messages_key',
            'has_responses_suffix',
            'has_double_responses_suffix',
            'raw_body_length',
        ] as $needle) {
            self::assertStringContainsString($needle, $probe);
        }

        self::assertStringNotContainsString('prompt text', $probe);
        self::assertStringNotContainsString('authorization', $probe);
        self::assertStringNotContainsString('cookie', $probe);
        self::assertStringNotContainsString('source code', $probe);

        self::assertStringContainsString('LOCAL_REQUEST_SHAPE', $smoke);
        self::assertStringContainsString('PROFILE_LOAD', $smoke);
        self::assertStringContainsString('CODEX_RUNTIME', $smoke);
        self::assertStringContainsString('LOCAL_STREAMING', $smoke);
        self::assertStringContainsString('shape_ok', $smoke);
        self::assertStringContainsString('request-shape.jsonl', $smoke);
        self::assertStringContainsString('response.completed', $probe);
        self::assertStringContainsString('text/event-stream', $probe);
    }

    public function testLocalShapeSmokeSupportsCompletionAndNegativeFixtures(): void
    {
        $repoRoot = dirname(__DIR__, 4);
        $probe = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'tools'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'cf-ai-request-shape-probe.mjs');
        $smoke = file_get_contents($repoRoot.DIRECTORY_SEPARATOR.'tools'.DIRECTORY_SEPARATOR.'ai'.DIRECTORY_SEPARATOR.'cf-ai-codex-shape-smoke.ps1');

        self::assertIsString($probe);
        self::assertIsString($smoke);

        self::assertStringContainsString('[ValidateSet(\'success\', \'missing_completed\', \'malformed\')]', $smoke);
        self::assertStringContainsString('--mode', $smoke);
        self::assertStringContainsString('missing_completed', $smoke);
        self::assertStringContainsString('malformed', $smoke);
        self::assertStringContainsString('response.completed', $probe);
        self::assertStringContainsString('LOCAL_STREAMING', $smoke);
        self::assertStringContainsString('probeRecord', $smoke);
        self::assertStringNotContainsString('prompt content', $smoke);
        self::assertStringNotContainsString('authorization values', $smoke);
    }
}
