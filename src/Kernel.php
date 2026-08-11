<?php

namespace App;

use Ghost\GovUkCoreBundle\Kernel as CoreKernel;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use function dirname;

class Kernel extends CoreKernel implements CompilerPassInterface
{
    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/{routes}/'.$this->environment.'/*.yaml');
        $routes->import('../config/{routes}/*.yaml');

        if (is_file(dirname(__DIR__).'/config/routes.yaml')) {
            $routes->import('../config/routes.yaml');
        } else {
            $routes->import('../config/{routes}.php');
        }
    }
}
