<?php

namespace Voryx\ThruwayBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thruway\Transport\PawlTransportProvider;
use Thruway\Transport\RatchetTransportProvider;
use WampPost\WampPost;

class ThruwayHttpProxyCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('thruway:http-proxy:start')
          ->setDescription('Start HTTP WAMP proxy server')
          ->setHelp("The <info>%command.name%</info> starts the HTTP WAMP proxy server, which allows you to publish WAMP messages over http");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {

            // bundle config
            $config = $this->getContainer()->getParameter('voryx_thruway');

            $output->writeln("Starting the Thruway WAMP HTTP Server on {$config['ip']} {$config['http_port']}");

            // create an HTTP server on port 8181
            $wp = new WampPost($config['realm'], null, $config['ip'], $config['http_port']);

            $wp->setReconnectOptions(["max_retries" => 999999]); //does -1 work?

            // add a transport to connect to the WAMP router
            $wp->addTransportProvider(new PawlTransportProvider($config['trusted_url']));

            // start the WampPost client
            $wp->start();

        } catch (\Exception $e) {
            $logger = $this->getContainer()->get('logger');
            $logger->addCritical("EXCEPTION:".$e->getMessage());
            $output->writeln("EXCEPTION:".$e->getMessage());
        }
    }
}
