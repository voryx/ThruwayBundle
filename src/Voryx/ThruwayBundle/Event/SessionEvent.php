<?php

namespace Voryx\ThruwayBundle\Event;

use Thruway\ClientSession;
use Thruway\Transport\TransportInterface;

/**
 * Class SessionEvent
 * @package Voryx\ThruwayBundle\Event
 */
class SessionEvent
{
    /**
     * @var ClientSession
     */
    private $session;

    /**
     * @var TransportInterface
     */
    private $transport;

    /**
     * @var string
     */
    private $processName;

    /**
     * @var int
     */
    private $processInstance;

    /**
     * @var
     */
    private $resourceMappings;

    /**
     * @param ClientSession $session
     * @param TransportInterface $transport
     * @param $processName
     * @param $processInstance
     * @param $resourceMappings
     */
    public function __construct(ClientSession $session, TransportInterface $transport, $processName, $processInstance, $resourceMappings)
    {
        $this->session          = $session;
        $this->transport        = $transport;
        $this->processName      = $processName;
        $this->processInstance  = $processInstance;
        $this->resourceMappings = $resourceMappings;
    }

    /**
     * @return ClientSession
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return TransportInterface
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @return string
     */
    public function getProcessName()
    {
        return $this->processName;
    }

    /**
     * @return int
     */
    public function getProcessInstance()
    {
        return $this->processInstance;
    }

    /**
     * @return mixed
     */
    public function getResourceMappings()
    {
        return $this->resourceMappings;
    }
}
