<?php

namespace Voryx\ThruwayBundle\DependencyInjection;

use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Thruway\Logging\Logger;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class VoryxThruwayExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        
        $loader->load('services.xml');

        $this->validate($config);

        $container->setParameter('voryx_thruway', $config);

        $this->configureOptions($config, $container);

        $this->createTaggedServiceHolder($config, $container);
    }

    /**
     * Validation for config
     * @param $config
     */
    protected function validate($config)
    {

        //@todo add more config validation

        if (isset($config['resources']) && !is_array($config['resources'])) {
            throw new \InvalidArgumentException(
              'The "resources" option must be an array'
            );
        }

        if (isset($config['uri'])) {
            throw new \InvalidArgumentException(
              'The "uri" config option has been deprecated, please use "url" instead'
            );
        }

        if (isset($config['trusted_uri'])) {
            throw new \InvalidArgumentException(
              'The "trusted_uri" config option has been deprecated, please use "trusted_url" instead'
            );
        }

        if (!isset($config['realm'])) {
            throw new \InvalidArgumentException(
              'The "realm" option must be set within voryx_thruway'
            );
        }
    }

    /**
     * Configure optional settings
     *
     * @param $config
     * @param ContainerBuilder $container
     */
    protected function configureOptions(&$config, ContainerBuilder $container)
    {

        if ($config['enable_logging'] !== true) {
            Logger::set(new NullLogger());
        }

        if (isset($config['router']['authentication']) && $config['router']['authentication'] !== false) {

            //Inject the authentication manager into the router
            $container
              ->getDefinition('voryx.thruway.server')
              ->addMethodCall('registerModule', [new Reference('voryx.thruway.authentication.manager')]);
        }


        if (isset($config['router']['authorization']) && $config['router']['authorization'] !== false) {
            $authId = $config['router']['authorization'];
            $container->getDefinition('voryx.thruway.server')
                ->addMethodCall('registerModule', [new Reference($authId)]);
        }

        if ($container->hasDefinition('security.user.provider.concrete.in_memory')) {
            $container->addAliases(['in_memory_user_provider' => 'security.user.provider.concrete.in_memory']);
        }

        //Topic State Handler
        if (isset($config['router']['enable_topic_state']) && $config['router']['enable_topic_state'] === true) {

            $container
              ->getDefinition('voryx.thruway.server')
              ->addMethodCall('registerModule', [new Reference('voryx.thruway.topic.state.handler')]);
        }
    }

    /**
     * Creates a service that allows us to store the services that get tagged with 'thruway.global'
     *
     * @param $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    protected function createTaggedServiceHolder($config, ContainerBuilder $container)
    {

        if (!$container->hasDefinition('tagged_service_holder')) {
            $taggedServiceHolder = new Definition();
            $taggedServiceHolder->setClass('ArrayObject');
            $container->setDefinition('tagged_service_holder', $taggedServiceHolder);
        }

        //Create a new instance of the container each time
        if (!$container->hasDefinition('thruway_container')) {
            $def = $container->getDefinition('thruway_container');

            //For symfony >= v2.8
            if (method_exists($def, 'setShared')) {
                $def->setShared(true);
            } elseif (method_exists($def, 'setScope')) {
                $def->setScope('prototype');
            }
        }
    }
}
