<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use function dirname;

class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/{packages}/*.yaml');
        $container->import('../config/{packages}/'.$this->environment.'/*.yaml');

        if (Features::isEnabled(Features::GAE_ENVIRONMENT)) {
            $container->import('../config/{packages}/gae/*.yaml');
        }

        if (is_file(dirname(__DIR__).'/config/services.yaml')) {
            $container->import('../config/services.yaml');
            $container->import('../config/{services}_'.$this->environment.'.yaml');
        } else {
            $container->import('../config/{services}.php');
        }
    }

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

    public function getCacheDir(): string
    {
        return Features::isEnabled(Features::GAE_ENVIRONMENT)
            ? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'symf-cache'
            : parent::getCacheDir();
    }

    public function getLogDir(): string
    {
        return Features::isEnabled(Features::GAE_ENVIRONMENT)
            ? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'symf-log'
            : parent::getLogDir();
    }

    public function process(ContainerBuilder $container)
    {
        // Register Workflow Expression Language providers
        if ($container->hasDefinition('workflow.security.expression_language')) {
            $definition = $container->findDefinition('workflow.security.expression_language');
            foreach ($container->findTaggedServiceIds('workflow.expression_language_provider') as $id => $attributes) {
                $definition->addMethodCall('registerProvider', [new Reference($id)]);
            }
        }

        // Replace Validator Expression Language providers
        if ($container->hasDefinition('validator.expression')) {
            $definition = $container->findDefinition('validator.expression');
            $providers = $container->findTaggedServiceIds('validator.expression_language_provider');

            $expressionLanguage = (new Definition(ExpressionLanguage::class, [
                null, array_map(function($service){return new Reference($service);}, array_keys($providers))
            ]));
            $definition->setArguments([$expressionLanguage]);
        }

        // tag form wizard workflows
        $formWizardServiceIds = array_filter(
            $container->getServiceIds(),
            // filter services that start with 'state_machine.form_wizard', but don't end with '.definition' or '.metadata_store'
            fn($id) => preg_match('/^state_machine\.form_wizard(\.(?!(definition|metadata_store))\w+)+$/', $id)
        );
        foreach ($formWizardServiceIds as $formWizardServiceId) {
            $definition = $container->findDefinition($formWizardServiceId);
            $definition->addTag('app.form_wizard.workflow');
        }
    }
}

