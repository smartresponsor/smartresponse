<?php

declare(strict_types=1);

namespace App;

use App\DependencyInjection\Compiler\RegisterSiblingFixturesPass;
use App\Kernel\RuntimeBundleIterator;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield from RuntimeBundleIterator::fromProject($this->getProjectDir(), $this->environment);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        if (!\in_array($this->environment, ['dev', 'test'], true)) {
            return;
        }

        $container->addCompilerPass(new RegisterSiblingFixturesPass());
    }
}
