<?php

declare(strict_types=1);

namespace App\Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3).'/tools/inspection/app-source-structure-guard.php';

final class AppSourceStructureTest extends TestCase
{
    public function testSourceTreeAndNamingFollowHostCanon(): void
    {
        self::assertSame(
            [],
            \inspectAppSourceStructure(dirname(__DIR__, 3)),
            'App source tree or naming drifted from the host canon.',
        );
    }
}
