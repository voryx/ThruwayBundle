<?php

namespace Voryx\ThruwayBundle;

use React\EventLoop\LoopInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Voryx\ThruwayBundle\Client\ClientManager;

class ContainerFactory
{

    public static function createContainer($containerName, ClientManager $thruwayClient, LoopInterface $loop, ContainerInterface $parentContainer)
    {

        /** @var ContainerInterface $childContainer */
        $childContainer = new $containerName([
            'container.build_id'   => $parentContainer->getParameter('container.build_id'),
            'container.build_hash' => $parentContainer->getParameter('container.build_hash'),
            'container.build_time' => $parentContainer->getParameter('container.build_time'),
        ]);

        //These services will be passed from the outer container into the inner container
        $childContainer->set('thruway.client', $thruwayClient);
        $childContainer->set('voryx.thruway.loop', $loop);

        //Any service that is tagged 'thruway.global' will be copied to the child container
        foreach ($parentContainer->get('tagged_service_holder') as $taggedService) {
            $childContainer->set($taggedService[0], $taggedService[1]);
        }

        return $childContainer;
    }

}
