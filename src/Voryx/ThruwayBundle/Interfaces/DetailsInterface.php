<?php


namespace Voryx\ThruwayBundle\Interfaces;


interface DetailsInterface
{
    /**
     * @return WampArgumentInterface
     */
    public function getArgs();

    /**
     * @return null
     */
    public function getArgsKw();

    /**
     * @return null
     */
    public function getDetails();
}