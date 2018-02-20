<?php

namespace Voryx\ThruwayBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ThruwayServicesPass
 * @package Voryx\ThruwayBundle\DependencyInjection\Compiler
 */
class ServiceConfigurationPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {

        foreach ($container->findTaggedServiceIds('thruway.resource') as $id => $attr) {

            $def = $container->getDefinition($id);

            //For symfony >= v2.8
            if (method_exists($def, 'setShared')) {
                $def->setShared(true);
            } elseif (method_exists($def, 'setScope')) {
                $def->setScope('prototype');
            }
            // For Symfony >= 4 we need to expose worker publicly.
            $def->setPublic(true);

            $className      = $def->getClass();
            $class          = new \ReflectionClass($className);
            $methods        = $class->getMethods();
            $resourceMapper = $container->getDefinition('voryx.thruway.resource.mapper');

            $resourceMapper->addMethodCall('setWorkerAnnotation', [$class->getName()]);

            foreach ($methods as $method) {
                $resourceMapper->addMethodCall('map', [$id, $class->getName(), $method->getName()]);
            }
        }

        $router = $container->getDefinition('voryx.thruway.server');
        foreach ($container->findTaggedServiceIds('thruway.router_module') as $id => $attr) {
            $router->addMethodCall('registerModule', [new Reference($id)]);
        }

        foreach ($container->findTaggedServiceIds('thruway.internal_client') as $id => $attr) {
            $router->addMethodCall('addInternalClient', [new Reference($id)]);
        }
    }
}
