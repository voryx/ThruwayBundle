<?php

namespace Voryx\ThruwayBundle\Tests;

use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Voryx\ThruwayBundle\Annotation\Register;
use Voryx\ThruwayBundle\Mapping\URIClassMapping;
use Voryx\ThruwayBundle\Serialization\ArrayEncoder;
use Voryx\ThruwayBundle\Tests\Fixtures\Person;
use Voryx\ThruwayBundle\WampKernel;

class WampKernelTest extends \PHPUnit_Framework_TestCase
{

    /** @var  Container */
    private $container;

    /** @var  Serializer */
    private $serializer;

    /** @var  WampKernel */
    private $wampkernel;

    public function setup()
    {

        $this->container = new ContainerBuilder();

        //Create a WampKernel instance
        $reader         = $this->getMockBuilder('Doctrine\Common\Annotations\Reader')->getMock();
        $resourceMapper = new \Voryx\ThruwayBundle\ResourceMapper($reader);
        $dispatcher     = new EventDispatcher();

        $encoders    = [new ArrayEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $this->serializer = new Serializer($normalizers, $encoders);

        $this->wampkernel = new WampKernel($this->container, $this->serializer, $resourceMapper, $dispatcher, new NullLogger());

    }

    /**
     * @test
     */
    public function simple_rpc()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('simpleRPCTest');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);

        $args    = [3, "test", "test2"];
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

        $this->assertEquals($args, $result);

    }

    /**
     * @test
     */
    public function simple_rpc_with_default_value()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('simpleRPCTestWithDefault');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);

        $args    = null;
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

        $this->assertEquals("test", $result);

    }

    /**
     * @test
     */
    public function rpc_test_with_type()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('RPCTestWithType');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);


        $args    = [["name" => "dave"]];
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

        $this->assertEquals([new Person("dave")], $result);

    }

    /**
     * @test
     * @expectedException \Exception
     */
    public function rpc_test_with_type_bad_data()
    {

        $this->markTestSkipped("I don't think is possible anymore");
        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('RPCTestWithType');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);


        $args    = [new Person("badman")];
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

    }


    /**
     * @test
     */
    public function rpc_test_with_multiple_types()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('RPCTestWithMultipleTypes');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);


        $args    = [["name" => "dave"], ["name" => "matt"], ["name" => "jim"]];
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

        $this->assertEquals([new Person("dave"), new Person("matt"), new Person("jim")], $result);

    }


    /**
     * @test
     */
    public function rpc_test_with_mixed_types()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('RPCTestWithMixedTypes');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);


        $args    = [["name" => "dave"], "matt"];
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

        $this->assertEquals([new Person("dave"), "matt"], $result);

    }

    /**
     * @test
     */
    public function rpc_test_with_mixed_types_and_default_value()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('RPCTestWithMixedTypesAndDefault');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);


        $args    = [["name" => "dave"], "matt"];
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

        $this->assertEquals([new Person("dave"), "matt", "test"], $result);

    }

    /**
     * @test
     */
    public function rpc_test_with_null_value()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('RPCTestWithNull');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);


        $args    = null;
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

        $this->assertEquals([], $result);

    }

    /**
     * @test
     */
    public function rpc_test_with_two_args_null_value()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('simpleTwoArgRPCTest');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);


        $args    = [null, null];
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

        $this->assertEquals([null, null], $result);

    }

    /**
     * @test
     */
    public function rpc_test_return_null_value()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('RPCTestReturnNull');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);


        $args    = null;
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

        $this->assertEquals(null, $result);

    }

    /**
     * @test
     *
     * @expectedException \Exception
     */
    public function rpc_test_throw_exception()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('RPCTestThrowException');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);

        $args    = null;
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

    }

    /**
     * @test
     *
     */
    public function rpc_test_fatal_error()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('RPCTestUndefinedVar');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);

        $args    = null;
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        try {
            $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);
        } catch (\Exception $e) {
            $this->assertEquals("Unable to make the call: test.uri \n Message:  Undefined variable: b", $e->getMessage());
        }

    }

    /**
     * @test
     */
    public function get_resource_mapper()
    {
        $resourceMapper = $this->wampkernel->getResourceMapper();

        $this->assertInstanceOf('Voryx\ThruwayBundle\ResourceMapper', $resourceMapper);
    }

}
