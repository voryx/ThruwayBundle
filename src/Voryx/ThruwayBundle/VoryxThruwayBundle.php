<?php

namespace Voryx\ThruwayBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Voryx\ThruwayBundle\DependencyInjection\Compiler\AnnotationConfigurationPass;
use Voryx\ThruwayBundle\DependencyInjection\Compiler\GlobalTaggedServicesPass;
use Voryx\ThruwayBundle\DependencyInjection\Compiler\ServiceConfigurationPass;

use Symfony\Component\Console\Application;

/**
 * Class VoryxThruwayBundle
 * @package Voryx\ThruwayBundle
 */
class VoryxThruwayBundle extends Bundle
{

    /**
     * @param ContainerBuilder $container
     * @throws \LogicException
     */
    public function build(ContainerBuilder $container)
    {
        $passConfig = $container->getCompilerPassConfig();
        $passConfig->addPass(new AnnotationConfigurationPass($container->getParameter('kernel.bundles_metadata')));
        $passConfig->addPass(new ServiceConfigurationPass());
        $container->addCompilerPass(new GlobalTaggedServicesPass());

        $container->loadFromExtension('framework', [
            'serializer' => [
                'enabled' => true,
            ],
        ]);
    }


    /**
     * {@inheritDoc}
     */
    public function registerCommands(Application $application)
    {
    }

}
