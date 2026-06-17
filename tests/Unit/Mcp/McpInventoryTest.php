<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;

final class McpInventoryTest extends TestCase
{
    public function testAuxiliaryMcpConfigSurfaceIsEmptyAndNoOtherClientProfilesArePresent(): void
    {
        $userProfile = getenv('USERPROFILE');
        self::assertIsString($userProfile);
        self::assertNotSame('', $userProfile);

        $claudePath = $userProfile.DIRECTORY_SEPARATOR.'.claude';
        $cursorPath = $userProfile.DIRECTORY_SEPARATOR.'.cursor';
        $windsurfPath = $userProfile.DIRECTORY_SEPARATOR.'.windsurf';
        $auxiliaryConfigPath = $userProfile.DIRECTORY_SEPARATOR.'.ai'.DIRECTORY_SEPARATOR.'mcp'.DIRECTORY_SEPARATOR.'mcp.json';

        self::assertDirectoryDoesNotExist($claudePath);
        self::assertDirectoryDoesNotExist($cursorPath);
        self::assertDirectoryDoesNotExist($windsurfPath);
        self::assertFileExists($auxiliaryConfigPath);
        self::assertSame(0, filesize($auxiliaryConfigPath));
    }
}
