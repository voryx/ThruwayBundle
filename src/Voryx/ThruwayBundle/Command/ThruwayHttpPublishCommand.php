<?php

namespace Voryx\ThruwayBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Voryx\ThruwayBundle\Client\HttpClient;

class ThruwayHttpPublishCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('thruway:http:publish')
          ->setDescription('WAMP publish over HTTP')
          ->setHelp("The <info>%command.name%</info> command allows you to publish to WAMP over HTTP")
          ->addArgument('uri', InputArgument::REQUIRED, 'Uri')
          ->addArgument('arguments', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Arguments', []);

    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {

            $config = $this->getContainer()->getParameter('voryx_thruway');
            $client = new HttpClient($config);
            $uri    = $input->getArgument('uri');
            $args   = $input->getArgument('arguments');
            $result = $client->publish($uri, $args);

            $output->writeln($result);

        } catch (\Exception $e) {
            $logger = $this->getContainer()->get('logger');
            $logger->addCritical("EXCEPTION:".$e->getMessage());
            $output->writeln("Could not complete publish action.  Make sure that the WAMP HTTP proxy client is running. ".$e->getMessage());
        }
    }
}
