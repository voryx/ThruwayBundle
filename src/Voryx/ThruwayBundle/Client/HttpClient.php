<?php

namespace Voryx\ThruwayBundle\Client;

use GuzzleHttp\Client;

/**
 * Class HttpClient
 * @package Voryx\ThruwayBundle\Client
 */
class HttpClient
{
    /** @var */
    private $config;

    /** @var string */
    private $baseUri;

    /**
     * HttpClient constructor.
     * @param $config array
     */
    function __construct(array $config)
    {
        $this->config  = $config;
        $this->baseUri = "http://{$config['ip']}:{$config['http_port']}";
    }

    /**
     * @param $topicName
     * @param $arguments
     * @param array|null $argumentsKw
     * @param null $options
     * @return string
     */
    public function publish($topicName, $arguments, $argumentsKw = [], $options = null)
    {
        $client = new Client(['base_uri' => $this->baseUri, 'timeout' => 1.0,]);

        $data = [
          "topic"   => $topicName,
          "args"    => $arguments,
          "argsKw"  => $argumentsKw,
          "options" => $options,
        ];

        $response = $client->request('POST', '/pub', ['json' => $data]);

        return $response->getBody()->getContents();
    }

    /**
     * @param $procedureName
     * @param $arguments
     * @param array $argumentsKw
     * @param null $options
     * @return string
     */
    public function call($procedureName, $arguments, $argumentsKw = [], $options = null)
    {
        $client = new Client(['base_uri' => $this->baseUri, 'timeout' => 1.0,]);

        $data = [
          "procedure" => $procedureName,
          "args"      => $arguments,
          "argsKw"    => $argumentsKw,
          "options"   => $options,
        ];

        $response = $client->request('POST', '/call', ['json' => $data]);

        return $response->getBody()->getContents();
    }
}
