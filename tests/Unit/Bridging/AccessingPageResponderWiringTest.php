<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bridging;

use App\Accessing\ServiceInterface\Rendering\PageResponderInterface;
use App\Accessing\Service\Rendering\TwigPageResponder;
use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AccessingPageResponderWiringTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testAccessingPageResponderResolvesToOwningSideResponder(): void
    {
        self::bootKernel();

        $responder = static::getContainer()->get(PageResponderInterface::class);

        self::assertInstanceOf(TwigPageResponder::class, $responder);
    }
}
