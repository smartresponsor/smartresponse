<?php

declare(strict_types=1);

namespace App\Tests\Unit\CodeMemory;

use App\Resolver\CodeMemory\CodeMemoryScopeResolver;
use PHPUnit\Framework\TestCase;

final class CodeMemoryScopeResolverTest extends TestCase
{
    public function testHostApplicationReadsComposerPathRepositoriesButEditsOnlyActiveProject(): void
    {
        $root = $this->createWorkspace();
        $app = $root.DIRECTORY_SEPARATOR.'App';
        $billing = $root.DIRECTORY_SEPARATOR.'Billing';

        mkdir($app, 0777, true);
        mkdir($billing, 0777, true);

        file_put_contents($app.DIRECTORY_SEPARATOR.'composer.json', json_encode([
            'name' => 'smartresponsor/app',
            'repositories' => [
                [
                    'type' => 'path',
                    'url' => '../Billing',
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        file_put_contents($app.DIRECTORY_SEPARATOR.'composer.lock', json_encode([
            'packages' => [
                ['name' => 'smartresponsor/billing'],
            ],
            'packages-dev' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        file_put_contents($billing.DIRECTORY_SEPARATOR.'composer.json', json_encode([
            'name' => 'smartresponsor/billing',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $scope = (new CodeMemoryScopeResolver())->resolve($app);

        self::assertSame('host-with-composer-links', $scope['mode']);
        self::assertSame($this->expectedProjectName($app), $scope['activeProject']);
        self::assertCount(1, $scope['editProjects']);
        self::assertSame($this->expectedProjectName($app), $scope['editProjects'][0]['project']);
        self::assertCount(2, $scope['readProjects']);
        self::assertSame($this->expectedProjectName($billing), $scope['readProjects'][1]['project']);
        self::assertSame('smartresponsor/billing', $scope['readProjects'][1]['package']);
        self::assertTrue($scope['readProjects'][1]['presentInLock']);
        self::assertFalse($scope['rules']['rawUnscopedGraphSearchAllowed']);
        self::assertFalse($scope['rules']['linkedProjectEditAllowedByDefault']);
    }

    public function testComponentWithoutComposerPathRepositoriesUsesRepoLocalMode(): void
    {
        $root = $this->createWorkspace();
        $component = $root.DIRECTORY_SEPARATOR.'Shipping';
        mkdir($component, 0777, true);
        file_put_contents($component.DIRECTORY_SEPARATOR.'composer.json', json_encode([
            'name' => 'smartresponsor/shipping',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $scope = (new CodeMemoryScopeResolver())->resolve($component);

        self::assertSame('repo-local', $scope['mode']);
        self::assertCount(1, $scope['readProjects']);
        self::assertCount(1, $scope['editProjects']);
        self::assertSame($scope['activeProject'], $scope['readProjects'][0]['project']);
        self::assertSame($scope['activeProject'], $scope['editProjects'][0]['project']);
    }

    private function createWorkspace(): string
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'code-memory-scope-'.bin2hex(random_bytes(6));
        mkdir($root, 0777, true);

        return $root;
    }

    private function expectedProjectName(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $normalized = preg_replace('/^[\\\\\/]+/', '', $normalized) ?? $normalized;
        $normalized = str_replace(':', '', $normalized);
        $name = preg_replace('/[^A-Za-z0-9]+/', '-', $normalized) ?? $normalized;

        return trim($name, '-');
    }
}
