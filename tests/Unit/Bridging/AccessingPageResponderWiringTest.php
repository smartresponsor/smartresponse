<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bridging;

use App\Accessing\ServiceInterface\Rendering\PageResponderInterface;
use App\Bridging\Service\AccessingInterfacing\AccessingInterfacingPageResponder;
use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AccessingPageResponderWiringTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testAccessingPageResponderResolvesToBridgeResponderInHostApp(): void
    {
        self::bootKernel();

        $responder = static::getContainer()->get(PageResponderInterface::class);

        self::assertInstanceOf(AccessingInterfacingPageResponder::class, $responder);
    }
}
