<?php


namespace Voryx\ThruwayBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ThruwayServicesPass
 * @package Voryx\ThruwayBundle\DependencyInjection\Compiler
 */
class GlobalTaggedServicesPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {

        if (!$container->hasDefinition('tagged_service_holder')) {
            return;
        }
        $taggedServiceHolder = $container->getDefinition('tagged_service_holder');
        foreach ($container->findTaggedServiceIds('thruway.global') as $id => $attributes) {
            $taggedServiceHolder->addMethodCall('append', [[$id, new Reference($id)]]);
        }
    }
}
