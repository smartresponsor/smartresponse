<?php

declare(strict_types=1);

namespace App;

use App\DependencyInjection\Compiler\CrudingEntityAliasAuthorityPass;
use App\DependencyInjection\Compiler\CrudResourceViewPayloadNormalizerPass;
use App\DependencyInjection\Compiler\RegisterSiblingFixturesPass;
use App\Kernel\RuntimeBundleIterator;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield from RuntimeBundleIterator::fromProject($this->getProjectDir(), $this->environment);
    }

    public function getCacheDir(): string
    {
        if ('dev' !== $this->environment) {
            $override = $_SERVER['APP_CACHE_DIR'] ?? $_ENV['APP_CACHE_DIR'] ?? null;
            if (is_string($override) && '' !== trim($override)) {
                return rtrim($override, '/\\');
            }
        }

        return parent::getCacheDir();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CrudingEntityAliasAuthorityPass());

        if (!\in_array($this->environment, ['dev', 'test'], true)) {
            return;
        }

        $container->addCompilerPass(new RegisterSiblingFixturesPass());
        $container->addCompilerPass(new CrudResourceViewPayloadNormalizerPass());
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $configDir = $this->getProjectDir().'/config';

        $deferredRoutes = [];
        foreach ([
            $configDir.'/routes/*.{php,yaml}',
            $configDir.'/routes/'.$this->environment.'/*.{php,yaml}',
        ] as $pattern) {
            foreach (glob($pattern, GLOB_BRACE) ?: [] as $path) {
                if ('cruding.yaml' === basename($path)) {
                    $deferredRoutes[] = $path;
                    continue;
                }

                $routes->import($path);
            }
        }

        foreach ($deferredRoutes as $path) {
            $routes->import($path);
        }

        $routesFile = $configDir.'/routes.yaml';
        if (is_file($routesFile)) {
            $routes->import($routesFile);
        } elseif (is_file($configDir.'/routes.php')) {
            $routes->import($configDir.'/routes.php');
        }

        if ($fileName = (new \ReflectionObject($this))->getFileName()) {
            $routes->import($fileName, 'attribute');
        }
    }
}
