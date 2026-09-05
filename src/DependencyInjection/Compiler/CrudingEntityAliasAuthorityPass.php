<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

final readonly class CrudingEntityAliasAuthorityPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('kernel.project_dir')) {
            return;
        }

        $configPath = rtrim((string) $container->getParameter('kernel.project_dir'), '/\\').'/config/packages/cruding.yaml';
        if (!is_file($configPath)) {
            return;
        }

        $config = Yaml::parseFile($configPath);
        if (!is_array($config)) {
            return;
        }

        $cruding = $config['cruding'] ?? null;
        if (!is_array($cruding)) {
            return;
        }

        $aliases = $cruding['entity_class_alias_map'] ?? null;
        if (!is_array($aliases)) {
            return;
        }

        $canonical = [];
        foreach ($aliases as $resourcePath => $entityClass) {
            if (!is_string($resourcePath) || '' === trim($resourcePath) || !is_string($entityClass) || '' === trim($entityClass)) {
                continue;
            }

            $canonical[trim($resourcePath)] = trim($entityClass);
        }

        $container->setParameter('cruding.entity_class_alias_map', $canonical);
    }
}
