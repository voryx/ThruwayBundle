<?php


namespace Voryx\ThruwayBundle\Interfaces;


interface WampArgumentInterface
{
    /**
     * @return int
     */
    public function getCallerId(): int;

    /**
     * @return string
     */
    public function authId(): string;

    /***
     * @return string
     */
    public function authRole(): string;

    /**
     * @return string[]
     */
    public function authRoles(): array;

    /**
     * @return string
     */
    public function authMethod(): string;
}