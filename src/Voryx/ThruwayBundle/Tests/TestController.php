<?php


namespace Voryx\ThruwayBundle\Tests;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Voryx\ThruwayBundle\Tests\Fixtures\Person;


class TestController extends Controller
{

    /**
     * simple rpc test - one argument
     */
    public function simpleRPCTest($firstArg)
    {
        return func_get_args();
    }

    /**
     * simple rpc test - 2 argument
     */
    public function simpleTwoArgRPCTest($firstArg, $secondArg)
    {
        return func_get_args();
    }


    /**
     * simple rpc test with default value
     */
    public function simpleRPCTestWithDefault($firstArg = "test")
    {
        return $firstArg;
    }

    /**
     * rpc test with type
     */
    public function RPCTestWithType(Person $person)
    {
        return func_get_args();
    }

    /**
     * rpc test with stdClass
     */
    public function RPCTestWithStdClass(\stdClass $person)
    {
        return func_get_args();
    }

    /**
     * rpc test with multiple types
     */
    public function RPCTestWithMultipleTypes(Person $person, Person $person2, Person $person3)
    {
        return func_get_args();
    }

    /**
     * rpc test with mixed types
     */
    public function RPCTestWithMixedTypes(Person $person, $name)
    {
        return func_get_args();
    }

    /**
     * rpc test with mixed types
     */
    public function RPCTestWithMixedTypesAndDefault(Person $person, $name, $name2 = "test")
    {
        return [$person, $name, $name2];
    }

    /**
     * rpc test with no args
     */
    public function RPCTestWithNull()
    {
        return [];
    }


    /**
     * rpc test that returns null
     */
    public function RPCTestReturnNull()
    {
        return null;
    }

    /**
     * rpc test that throws exception
     */
    public function RPCTestThrowException()
    {
        throw new \Exception('something bad');

    }

    /**
     * rpc test - undefined variable
     */
    public function RPCTestUndefinedVar()
    {
        $t[] = $b;
        return $t;

    }
}