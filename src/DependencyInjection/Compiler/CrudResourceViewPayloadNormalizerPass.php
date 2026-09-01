<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Service\View\CrudResourceViewPayloadNormalizer;
use App\Viewing\Subscriber\View\ViewKernelViewSubscriber;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final readonly class CrudResourceViewPayloadNormalizerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ViewKernelViewSubscriber::class)) {
            return;
        }

        if (!$container->has(CrudResourceViewPayloadNormalizer::class)) {
            return;
        }

        $container
            ->getDefinition(ViewKernelViewSubscriber::class)
            ->setArgument(0, new Reference(CrudResourceViewPayloadNormalizer::class));
    }
}
