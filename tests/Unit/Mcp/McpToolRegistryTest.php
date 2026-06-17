<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;

final class McpToolRegistryTest extends TestCase
{
    public function testToolRegistryDocumentsTheCurrentCanonicalMcpSurface(): void
    {
        $registryPath = dirname(__DIR__, 4).DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'mcp'.DIRECTORY_SEPARATOR.'MCP_TOOL_REGISTRY.md';
        $registry = file_get_contents($registryPath);

        self::assertIsString($registry);

        self::assertStringContainsString('## codebase-memory-mcp', $registry);
        self::assertStringContainsString('transport | stdio', $registry);
        self::assertStringContainsString('endpoint | local executable `C:\\Users\\Admin\\.local\\bin\\codebase-memory-mcp.exe`', $registry);
        self::assertStringContainsString('tools | `index_repository`, `search_graph`, `query_graph`, `trace_path`, `get_code_snippet`, `get_graph_schema`, `get_architecture`, `search_code`, `list_projects`, `delete_project`, `index_status`, `detect_changes`, `manage_adr`, `ingest_traces`', $registry);
        self::assertStringContainsString('status | active primary code-memory engine', $registry);

        self::assertStringContainsString('## gating-mcp', $registry);
        self::assertStringContainsString('transport | Streamable HTTP', $registry);
        self::assertStringContainsString('endpoint | `https://gating-mcp.taa0662621456.workers.dev/mcp`', $registry);
        self::assertStringContainsString('tools | `gating.describe`, `gating.health`, `gating.get_policy_status`, `gating.list_rules`, `gating.get_review_contract`, `gating.validate_review_result`, `gating.publish_policy`, `gating.rollback_policy`', $registry);
        self::assertStringContainsString('permissions | read-only policy inspection for the six read-only tools; policy write/update for publish and rollback', $registry);
        self::assertStringContainsString('status | active remote policy server', $registry);

        self::assertStringContainsString('## Empty or placeholder surfaces', $registry);
        self::assertStringContainsString('server | `C:\\Users\\Admin\\.ai\\mcp\\mcp.json`', $registry);
        self::assertStringContainsString('status | empty', $registry);
    }
}
