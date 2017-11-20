<?php

namespace Voryx\ThruwayBundle\Client;

use Psr\Log\NullLogger;
use React\Promise\Deferred;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Serializer\Serializer;
use Thruway\ClientSession;
use Thruway\Logging\Logger;
use Thruway\Peer\Client;
use Thruway\Transport\PawlTransportProvider;
use Thruway\Transport\TransportInterface;

/**
 * Class ClientManager
 * @package Voryx\ThruwayBundle\Client
 */
class ClientManager
{
    /* @var Container */
    private $container;

    /** @var */
    private $config;


    /** @var Serializer */
    private $serializer;

    /**
     * @param Container $container
     * @param $config
     */
    public function __construct(Container $container, $config, Serializer $serializer)
    {
        $this->container  = $container;
        $this->config     = $config;
        $this->serializer = $serializer;
    }

    /**
     * @param $topicName
     * @param $arguments
     * @param array|null $argumentsKw
     * @param null|array|Object $options
     * @return \React\Promise\Promise
     * @throws \Exception
     */
    public function publish($topicName, $arguments, array $argumentsKw = [], $options = null)
    {
        $arguments   = $arguments ?: [$arguments];
        $argumentsKw = $argumentsKw ?: [$argumentsKw];
        $arguments   = $this->serializer->normalize($arguments);
        $argumentsKw = $this->serializer->normalize($argumentsKw);

        //If we already have a client open that we can use, use that
        if ($this->container->initialized('wamp_kernel') && $client = $this->container->get('wamp_kernel')->getClient()) {
            $session = $this->container->get('wamp_kernel')->getSession();

            return $session->publish($topicName, $arguments, $argumentsKw, $options);
        }

        if (is_array($options)) {
            $options = (object)$options;
        }

        if (!is_object($options)) {
            $options = (object)[];
        }

        Logger::set(new NullLogger()); //So logs don't show up on the web page

        //If we don't already have a long running client, get a short lived one.
        $client               = $this->getShortClient();
        $options->acknowledge = true;
        $deferrer             = new Deferred();

        $client->on('open', function (ClientSession $session, TransportInterface $transport) use ($deferrer, $topicName, $arguments, $argumentsKw, $options) {
            $session->publish($topicName, $arguments, $argumentsKw, $options)->then(
                function () use ($deferrer, $transport) {
                    $transport->close();
                    $deferrer->resolve();
                });
        });

        $client->on('error', function ($error) use ($topicName) {
            $this->container->get('logger')->addError("Got the following error when trying to publish to '{$topicName}': {$error}");
        });

        $client->start();

        return $deferrer->promise();
    }

    /**
     * @param $procedureName
     * @param $arguments
     * @param array $argumentsKw
     * @param null $options
     * @return \React\Promise\Promise
     * @throws \Exception
     */
    public function call($procedureName, $arguments, $argumentsKw = [], $options = null)
    {
        $arguments   = $arguments ?: [$arguments];
        $argumentsKw = $argumentsKw ?: [$argumentsKw];
        $arguments   = $this->serializer->normalize($arguments);
        $argumentsKw = $this->serializer->normalize($argumentsKw);

        //If we already have a client open that we can use, use that
        if ($this->container->initialized('wamp_kernel') && $client = $this->container->get('wamp_kernel')->getClient()) {
            $session = $this->container->get('wamp_kernel')->getSession();

            return $session->call($procedureName, $arguments, $argumentsKw, $options);
        }

        Logger::set(new NullLogger()); //So logs don't show up on the web page

        //If we don't already have a long running client, get a short lived one.
        $client   = $this->getShortClient();
        $deferrer = new Deferred();

        $client->on('open', function (ClientSession $session, TransportInterface $transport) use ($deferrer, $procedureName, $arguments, $argumentsKw, $options) {
            $session->call($procedureName, $arguments, $argumentsKw, $options)->then(
                function ($res) use ($deferrer, $transport) {
                    $transport->close();
                    $deferrer->resolve($res);
                });
        });

        $client->on('error', function ($error) use ($procedureName) {
            $this->container->get('logger')->addError("Got the following error when trying to call '{$procedureName}': {$error}");
            throw new \Exception("Got the following error when trying to call '{$procedureName}': {$error}");
        });

        $client->start();

        return $deferrer->promise();
    }

    /**
     * @return Client
     * @throws \Exception
     */
    private function getShortClient()
    {
        $client = new Client($this->config['realm']);
        $client->setAttemptRetry(false);
        $client->addTransportProvider(new PawlTransportProvider($this->config['trusted_url']));

        return $client;
    }
}
