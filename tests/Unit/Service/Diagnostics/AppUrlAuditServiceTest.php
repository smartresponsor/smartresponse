<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Diagnostics;

use PHPUnit\Framework\TestCase;

final class AppUrlAuditServiceTest extends TestCase
{
    public function testPermanentAuditSurfaceExists(): void
    {
        $root = dirname(__DIR__, 4);
        self::assertFileExists($root.'/src/Service/Diagnostics/AppUrlAuditService.php');
        self::assertFileExists($root.'/src/Command/AppUrlAuditInventoryCommand.php');
        self::assertFileExists($root.'/src/Command/AppUrlAuditRunCommand.php');
        self::assertFileExists($root.'/src/Command/AppUrlAuditPublishGithubCommand.php');
        self::assertFileExists($root.'/tools/platform-url-audit.ps1');
    }
}
