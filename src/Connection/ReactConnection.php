<?php

namespace Tarantool\Client\Connection;

use React\Promise\Deferred;
use React\SocketClient\ConnectorInterface;
use React\Stream\Stream;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\IProto;
use Tarantool\Client\ResponseParser;

class ReactConnection implements Connection
{
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 3301;

    private $host;
    private $port;

    private $connector;
    private $promise;
    private $packer;
    private $sync = 0;
    private $requestEvents = [];
    private $requestData = '';
    private $salt;
    private $stream;
    private $responseParser;
    private $ending = false;

    public function __construct(ConnectorInterface $connector, Packer $packer, $host = null, $port = null)
    {
        $this->connector = $connector;
        $this->packer = $packer;

        $this->host = null === $host ? self::DEFAULT_HOST : $host;
        $this->port = null === $port ? self::DEFAULT_PORT : $port;

        $this->responseParser = new ResponseParser();
    }

    public function open()
    {
        $this->close();

        $this->promise = $this->connector->create($this->host, $this->port);

        $this->promise->then(function (Stream $stream) {
            $this->ending = false;
            $this->stream = $stream;

            $buffer = '';

            $stream->on('data', function ($data, $stream) use (&$buffer) {
                if (!$this->salt && !$this->ending) {
                    $buffer .= $data;
                    if (strlen($buffer) >= IProto::GREETING_SIZE) {
                        $this->salt = IProto::parseGreeting($buffer);
                        $buffer = (string) substr($buffer, IProto::GREETING_SIZE);
                    }
                } else {
                    $data = $buffer.$data;
                    $buffer = '';

                    $responses = $this->responseParser->pushIncoming($data);

                    foreach ($responses as $response) {
                        $response = $this->packer->unpack($response);
                        $key = $response->getSync();
                        $deferred = $this->requestEvents[$key];
                        $deferred->resolve($response->getData());
                        unset($this->requestEvents[$key]);
                    }
                }

                if ($this->requestData) {
                    $stream->write($this->requestData);
                    $this->requestData = '';
                }

                if ($this->ending && !$this->requestEvents) {
                    //$stream->close();
                    $stream->end();
                }
            });

        });

        return $this->promise;
    }

    public function close()
    {
        $this->ending = true;
        $this->promise = null;
        $this->salt = null;
    }

    public function isClosed()
    {
        return null === $this->promise;
    }

    public function send($request)
    {
        $key = $this->generateSync();
        $data = $this->packer->pack($request, $key);

        $this->requestData .= $data;
        $this->requestEvents[$key] = new Deferred();

        return $this->requestEvents[$key]->promise();
    }

    private function generateSync()
    {
        ++$this->sync;

        if (($this->sync | 0) < 0) {
            $this->sync = 0;
        }

        return $this->sync;
    }
}
