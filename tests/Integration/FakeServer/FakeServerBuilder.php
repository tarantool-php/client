<?php

namespace Tarantool\Client\Tests\Integration\FakeServer;

use Tarantool\Client\Tests\Integration\FakeServer\Handler\Handler;

class FakeServerBuilder
{
    private $handler;
    private $uri = 'tcp://0.0.0.0:8000';
    private $ttl = 5;
    private $logFile = '/tmp/fake_server.log';

    public function __construct(Handler $handler)
    {
        $this->handler = $handler;
    }

    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    public function setTtl($ttl)
    {
        $this->ttl = $ttl;

        return $this;
    }

    public function setLogFile($logFile)
    {
        $this->logFile = $logFile;

        return $this;
    }

    public function getCommand()
    {
        return sprintf(
            'php %s/fake_server.php \
                --handler=%s \
                --uri=%s \
                --ttl=%d \
            >> %s 2>&1 &',
            __DIR__,
            escapeshellarg(base64_encode(serialize($this->handler))),
            escapeshellarg($this->uri),
            $this->ttl,
            escapeshellarg($this->logFile)
        );
    }

    public function start()
    {
        exec($this->getCommand(), $output, $result);
        if (0 !== $result) {
            throw new \RuntimeException("Unable to start the fake server ($this->uri).");
        }

        $stopTime = time() + 5;
        while (time() < $stopTime) {
            if (@stream_socket_client($this->uri)) {
                return;
            }
            usleep(100);
        }

        throw new \RuntimeException("Unable to connect to the fake server ($this->uri).");
    }
}
