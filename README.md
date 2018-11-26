ThruwayBundle
===========

This a Symfony Bundle for [Thruway](https://github.com/voryx/Thruway), which is a php implementation of WAMP (Web Application Messaging Protocol).

Note:  This project is still undergoing a lot of changes, so the API will change.

### Quick Start with Composer


Install the Thruway Bundle

      $ composer require "voryx/thruway-bundle"
      

Update AppKernel.php (when using Symfony < 4)

```php
$bundles = array(
    // ...
    new Voryx\ThruwayBundle\VoryxThruwayBundle(),
    // ...
);
```

### Configuration

```yml
#app/config/config.yml

voryx_thruway:
    realm: 'realm1'
    url: 'ws://127.0.0.1:8081' #The url that the clients will use to connect to the router
    router:
        ip: '127.0.0.1'  # the ip that the router should start on
        port: '8080'  # public facing port.  If authentication is enabled, this port will be protected
        trusted_port: '8081' # Bypasses all authentication.  Use this for trusted clients.
#        authentication: false # true will load the AuthenticationManager
    locations:
        bundles: ["AppBundle"]
#        files:
#            - "Acme\\DemoBundle\\Controller\\DemoController"
#
# For symfony 4, this bundle will automatically scan for annotated worker files in the src/Controller folder
      
```

With Symfony 4 use a filename like: ```config/packages/voryx.yaml```

If you are using the in-memory user provider, you'll need to add a ```thruway``` to the security firewall and set the ``in_memory_user_provider``.

```yml
#app/config/security.yml

security: 
   firewalls:
        thruway:
            security: false	     
```

You can also tag services with `thruway.resource` and any annotation will get picked up

```xml
<service id="some.service" class="Acme\Bundle\SomeService">
    <tag name="thruway.resource"/>
</service>

```

Note: tagging a service as `thruway.resource` will make it public.

```yml
services:
    App\Worker\:
        resource: '../src/Worker'
        tags: ['thruway.resource']
```

### Authentication with FOSUserBundle via WampCRA

Change the Password Encoder (tricky on existing sites) to master wamp challenge

```yml
#app/config/security.yml

security:
    ...
    encoders:
        FOS\UserBundle\Model\UserInterface:
            algorithm:            pbkdf2
            hash_algorithm:       sha256
            encode_as_base64:     true
            iterations:           1000
            key_length:           32
```

set voryx_thruway.user_provider to "fos_user.user_provider"

```yml
#app/config/config.yml

voryx_thruway:
    user_provider: 'fos_user.user_provider.username' #fos_user.user_provider.username_email login with email

```

The WAMP-CRA service is already configured, we just need to add a tag to it to have the bundle install it:

```yml
    wamp_cra_auth:
        class: Thruway\Authentication\WampCraAuthProvider
        parent: voryx.thruway.wamp.cra.auth.client
        tags:
            - { name: thruway.internal_client }
```


### Custom Authorization Manager

You can set your own Authorization Manager in order to check if a user (identified by its authid) is allowed to publish | subscribe | call | register

Create your Authorization Manager service, extending RouterModuleClient and implementing RealmModuleInterface (see the Thruway doc for details)

```php
// src/ACME/AppBundle/Security/MyAuthorizationManager.php


use Thruway\Event\MessageEvent;
use Thruway\Event\NewRealmEvent;
use Thruway\Module\RealmModuleInterface;
use Thruway\Module\RouterModuleClient;

class MyAuthorizationManager extends RouterModuleClient implements RealmModuleInterface
{
    /**
     * Listen for Router events.
     * Required to add the authorization module to the realm
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'new_realm' => ['handleNewRealm', 10]
        ];
    }

    /**
     * @param NewRealmEvent $newRealmEvent
     */
    public function handleNewRealm(NewRealmEvent $newRealmEvent)
    {
        $realm = $newRealmEvent->realm;

        if ($realm->getRealmName() === $this->getRealm()) {
            $realm->addModule($this);
        }
    }

    /**
     * @return array
     */
    public function getSubscribedRealmEvents()
    {
        return [
            'PublishMessageEvent'   => ['authorize', 100],
            'SubscribeMessageEvent' => ['authorize', 100],
            'RegisterMessageEvent'  => ['authorize', 100],
            'CallMessageEvent'      => ['authorize', 100],
        ];
    }

    /**
     * @param MessageEvent $msg
     * @return bool
     */
    public function authorize(MessageEvent $msg)
    {
        if ($msg->session->getAuthenticationDetails()->getAuthId() === 'username') {
            return true;
        }
        return false;
    }
}
```

Register your authorization manager service

```yml
     my_authorization_manager:
        class: ACME\AppBundle\Security\MyAuthorizationManager
```

Insert your service name in the voryx_thruway config

```yml
#app/config/config.yml

voryx_thruway:
    ...
        authorization: my_authorization_manager # insert the name of your custom authorizationManager
   ...
```

Restart the Thruway server; it will now check authorization upon publish | subscribe | call | register. 
Remember to catch error when you try to subscribe to a topic (or any other action) as it may now be denied and this will be returned as an error.
 

## Usage


#### Register RPC

```php
    use Voryx\ThruwayBundle\Annotation\Register;
    
    /**
     *
     * @Register("com.example.add")
     *
     */
    public function addAction($num1, $num2)
    {
        return $num1 + $num2;
    }
```

#### Call RPC

```php
    public function call($value)
    {
        $client = $this->container->get('thruway.client');
        $client->call("com.myapp.add", [2, 3])->then(
            function ($res) {
                echo $res[0];
            }
        );
    }
```

#### Subscribe	

```php	
     use Voryx\ThruwayBundle\Annotation\Subscribe;

    /**
     *
     * @Subscribe("com.example.subscribe")
     *
     */
    public function subscribe($value)
    {
        echo $value;
    }
```


#### Publish

```php
    public function publish($value)
    {
        $client = $this->container->get('thruway.client');
        $client->publish("com.myapp.hello_pubsub", [$value]);
    }
```

It uses Symfony Serializer, so it can serialize and deserialize Entities

```php
    
    use Voryx\ThruwayBundle\Annotation\Register;

    /**
     *
     * @Register("com.example.addrpc", serializerEnableMaxDepthChecks=true)
     *
     */
    public function addAction(Post $post)
    {
        //Do something to $post

        return $post;
    }
```

#### Start the Thruway Process

You can start the default Thruway workers (router and client workers), without any additional configuration.

    $ nohup php app/console thruway:process start &
    
By default, the router starts on ws://127.0.0.1:8080
    
     
## Workers

The Thruway bundle will start up a separate process for the router and each defined worker.  If you haven't defined any workers, all of the annotated calls and subscriptions will be started within the `default` worker.

There are two main ways to break your application apart into multiple workers.

1.  Use the `worker` property on the `Register` and `Subscribe` annotations.  The following RPC will be added to the `posts` worker.
     
    ```PHP
      use Voryx\ThruwayBundle\Annotation\Register;
    
      /**
      * @Register("com.example.addrpc", serializerEnableMaxDepthChecks=true, worker="posts")
      */
      public function addAction(Post $post)
    ```
2.  Use the `@Worker` annotation on the class.  The following annotation will create a worker called `chat` that can have a max of 5 instances.
     
    ```PHP
      use Voryx\ThruwayBundle\Annotation\Worker;
    
      /**
      * @Worker("chat", maxProcesses="5")
      */
      class ChatController
    ```
 
If a worker is shut down with anything other than `SIGTERM`, it will automatically be restarted.
  
## More Commands

##### To see a list of running processes (workers)
     
    $ php app/console thruway:process status
    
##### Stop a process, i.e. `default`

    $ php app/console thruway:process stop default
    
##### Start a process, i.e. `default`
    
    $ php app/console thruway:process start default
    

### Javascript Client

For the client, you can use [AutobahnJS](https://github.com/tavendo/AutobahnJS) or any other WAMPv2 compatible client.

Here are some [examples](https://github.com/tavendo/AutobahnJS#show-me-some-code)

# Symfony 4 Quick Start
```BASH
composer create-project symfony/skeleton my_project
```
```BASH
cd my_project
```
```BASH
composer require symfony/expression-language
```
```BASH
composer require symfony/annotations-pack
```
```BASH
composer require voryx/thruway-bundle:dev-master
```

Create config/packages/my_project.yml with the following config:
```YML
voryx_thruway:
    realm: 'realm1'
    url: 'ws://127.0.0.1:8081' #The url that the clients will use to connect to the router
    router:
        ip: '127.0.0.1'  # the ip that the router should start on
        port: '8080'  # public facing port.  If authentication is enabled, this port will be protected
        trusted_port: '8081' # Bypasses all authentication.  Use this for trusted clients.
```

Create the controller src/Controller/TestController.php

```PHP
<?php
namespace App\Controller;

use Voryx\ThruwayBundle\Annotation\Register;

class TestController
{
    /**
     * @Register("com.example.add")
     */
    public function addAction($num1, $num2)
    {
        return $num1 + $num2;
    }
}
```

Test to see if the RPC has been configured correctly `bin/console thruway:debug`
```BASH
 URI             Type Worker  File                                                  Method    
 com.example.add RPC  default /my_project/src/Controller/TestController.php         addAction 
 ```
 For more debug info for the RPC we created: `bin/console thruway:debug com.example.add`
 
Start everything: `bin/console thruway:process start`

The RPC `com.example.add` is now available to any WAMP client connected to ws://127.0.0.1:8081 on realm1.
