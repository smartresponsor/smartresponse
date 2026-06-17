<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;

final class CodebaseMemoryRegistryTest extends TestCase
{
    public function testCodebaseMemoryHelpAndConfigExposeExpectedRegistrySurface(): void
    {
        $binary = 'C:\\Users\\Admin\\.local\\bin\\codebase-memory-mcp.exe';
        $cbmignore = file_get_contents(dirname(__DIR__, 4).DIRECTORY_SEPARATOR.'.cbmignore');

        $help = shell_exec('"'.$binary.'" --help');
        $config = shell_exec('"'.$binary.'" config list');

        self::assertIsString($help);
        self::assertIsString($config);
        self::assertIsString($cbmignore);

        self::assertStringContainsString('Tools: index_repository, search_graph, query_graph, trace_path', $help);
        self::assertStringContainsString('get_code_snippet, get_graph_schema, get_architecture, search_code', $help);
        self::assertStringContainsString('list_projects, delete_project, index_status, detect_changes', $help);
        self::assertStringContainsString('manage_adr, ingest_traces', $help);
        self::assertStringContainsString('Supported agents (auto-detected):', $help);

        self::assertStringContainsString('auto_index                = true', $config);
        self::assertStringContainsString('auto_index_limit          = 50000', $config);
        self::assertStringContainsString('vendor/', $cbmignore);
        self::assertStringContainsString('**/vendor/', $cbmignore);
        self::assertStringContainsString('node_modules/', $cbmignore);
        self::assertStringContainsString('**/node_modules/', $cbmignore);
        self::assertStringContainsString('.cache/', $cbmignore);
        self::assertStringContainsString('generated/', $cbmignore);
    }
}
