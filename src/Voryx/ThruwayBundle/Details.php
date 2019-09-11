<?php

namespace Voryx\ThruwayBundle;

use Voryx\ThruwayBundle\Interfaces\DetailsInterface;

class Details implements DetailsInterface
{
    protected $args;
    protected $argsKw;
    protected $details;

    public function __construct($args = [], $argsKw = null, $details = null)
    {
        $this->args    = $args;
        $this->argsKw  = $argsKw;
        $this->details = $details;
    }

    /**
     * {@inheritDoc}
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @return mixed
     */
    public function getArgsKw()
    {
        return $this->argsKw;
    }

    /**
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
    }
}
