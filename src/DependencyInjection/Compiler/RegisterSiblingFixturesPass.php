<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterSiblingFixturesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('kernel.project_dir')) {
            return;
        }

        $projectDir = (string) $container->getParameter('kernel.project_dir');
        $workspaceDir = dirname($projectDir);
        $allowedSiblingFixtures = $this->getAllowedSiblingFixtures($container);
        $fixtures = [];

        foreach (glob($workspaceDir.'/*/src/DataFixtures', GLOB_ONLYDIR) ?: [] as $fixturesDir) {
            if (!$this->isAllowedSiblingFixturesDirectory($fixturesDir, $allowedSiblingFixtures)) {
                continue;
            }

            $fixtures = array_merge($fixtures, $this->registerFixturesFromDirectory($container, $fixturesDir));
        }

        if ([] === $fixtures || !$container->hasDefinition('doctrine.fixtures.loader')) {
            return;
        }

        $container->getDefinition('doctrine.fixtures.loader')->addMethodCall('addFixtures', [$fixtures]);
    }

    /**
     * @return list<string>
     */
    private function getAllowedSiblingFixtures(ContainerBuilder $container): array
    {
        if (!$container->hasParameter('app.sibling_fixtures_allowlist')) {
            return [];
        }

        $value = $container->getParameter('app.sibling_fixtures_allowlist');
        if (!\is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn ($item): string => strtolower((string) $item), $value), static fn (string $item): bool => '' !== $item));
    }

    /**
     * @param list<string> $allowedSiblingFixtures
     */
    private function isAllowedSiblingFixturesDirectory(string $fixturesDir, array $allowedSiblingFixtures): bool
    {
        if ([] === $allowedSiblingFixtures) {
            return true;
        }

        $componentDir = basename(dirname($fixturesDir, 2));
        if ('' === $componentDir) {
            return false;
        }

        return \in_array(strtolower($componentDir), $allowedSiblingFixtures, true);
    }

    /**
     * @return list<array{fixture: Reference, groups: list<string>}>
     */
    private function registerFixturesFromDirectory(ContainerBuilder $container, string $fixturesDir): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fixturesDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $fixtures = [];

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile() || 'php' !== strtolower($fileInfo->getExtension())) {
                continue;
            }

            $fqcn = $this->extractClassName($fileInfo->getPathname());
            if (null === $fqcn) {
                continue;
            }

            if ($container->hasDefinition($fqcn) || $container->hasAlias($fqcn)) {
                continue;
            }

            if (!class_exists($fqcn, false)) {
                require_once $fileInfo->getPathname();
            }

            if (!class_exists($fqcn)) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($fqcn);
            if ($reflectionClass->isAbstract() || !$reflectionClass->isInstantiable()) {
                continue;
            }

            if (!is_a($fqcn, FixtureInterface::class, true)) {
                continue;
            }

            $definition = new Definition($fqcn);
            $definition->setAutowired(true);
            $definition->setAutoconfigured(true);

            $container->setDefinition($fqcn, $definition);

            $groups = [$reflectionClass->getShortName()];
            if (is_a($fqcn, FixtureGroupInterface::class, true)) {
                $groups = array_values(array_unique(array_merge($groups, $fqcn::getGroups())));
            }

            $fixtures[] = [
                'fixture' => new Reference($fqcn),
                'groups' => $groups,
            ];
        }

        return $fixtures;
    }

    private function extractClassName(string $path): ?string
    {
        $code = file_get_contents($path);
        if (false === $code) {
            return null;
        }

        $tokens = token_get_all($code);
        $namespace = '';
        $className = null;
        $tokenCount = count($tokens);

        for ($i = 0; $i < $tokenCount; ++$i) {
            $token = $tokens[$i];

            if (is_array($token) && T_NAMESPACE === $token[0]) {
                $namespace = '';
                for (++$i; $i < $tokenCount; ++$i) {
                    $namespaceToken = $tokens[$i];
                    if (';' === $namespaceToken || '{' === $namespaceToken) {
                        break;
                    }
                    if (is_array($namespaceToken)) {
                        if (\in_array($namespaceToken[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE], true)) {
                            $namespace .= $namespaceToken[1];
                        }
                        continue;
                    }
                    $namespace .= $namespaceToken;
                }
                continue;
            }

            if (!is_array($token) || T_CLASS !== $token[0]) {
                continue;
            }

            $previousSignificantToken = null;
            for ($j = $i - 1; $j >= 0; --$j) {
                $candidate = $tokens[$j];
                if (is_array($candidate) && \in_array($candidate[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_ATTRIBUTE], true)) {
                    continue;
                }

                $previousSignificantToken = $candidate;
                break;
            }

            if (is_array($previousSignificantToken) && T_NEW === $previousSignificantToken[0]) {
                continue;
            }

            for (++$i; $i < $tokenCount; ++$i) {
                $classToken = $tokens[$i];
                if (is_array($classToken) && T_STRING === $classToken[0]) {
                    $className = $classToken[1];
                    break 2;
                }
            }
        }

        if (null === $className) {
            return null;
        }

        return '' !== $namespace ? $namespace.'\\'.$className : $className;
    }
}
