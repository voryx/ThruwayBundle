<?php

namespace Voryx\ThruwayBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Thruway\Peer\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Thruway\Transport\PawlTransportProvider;

class ThruwayWorkerCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
        parent::__construct();
    }

    private function getContainer() {
        return $this->container;
    }

    /**
     * @var LoggerInterface $logger
     */
    private $logger;

    /**
     * Called by the Service Container.
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('thruway:worker:start')
            ->setDescription('Start Thruway WAMP worker')
            ->setHelp('The <info>%command.name%</info> starts the Thruway WAMP client.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the worker you\'re starting')
            ->addArgument('instance', InputArgument::OPTIONAL, 'Worker instance number', 0);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getContainer()->getParameter('voryx_thruway')['enable_logging'])
        {
            \Thruway\Logging\Logger::set($this->logger);
        }
        else
        {
            \Thruway\Logging\Logger::set(new \Psr\Log\NullLogger());
        }

        try {
            $output->write("Making a go at starting a Thruway worker.");

            $name             = $input->getArgument('name');
            $config           = $this->getContainer()->getParameter('voryx_thruway');
            $loop             = $this->getContainer()->get('voryx.thruway.loop');
            $kernel           = $this->getContainer()->get('wamp_kernel');
            $workerAnnotation = $kernel->getResourceMapper()->getWorkerAnnotation($name);

            if ($workerAnnotation) {
                $realm = $workerAnnotation->getRealm() ?: $config['realm'];
                $url   = $workerAnnotation->getUrl() ?: $config['url'];
            } else {
                $realm = $config['realm'];
                $url   = $config['url'];
            }

            $transport = new PawlTransportProvider($url);
            $client    = new Client($realm, $loop);

            $client->addTransportProvider($transport);

            $kernel->setProcessName($name);
            $kernel->setClient($client, $this->getContainer()->get('voryx.thruway.client.react_connector'));
            $kernel->setProcessInstance($input->getArgument('instance'));

            $client->start();

        } catch (\Exception $e) {
            $this->logger->critical('EXCEPTION:' . $e->getMessage());
            $output->writeln('EXCEPTION:' . $e->getMessage());
        }

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}
