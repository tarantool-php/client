<?php

namespace Tarantool\Tests\Integration;

class FakeServerBuilder
{
    private $uri = 'tcp://0.0.0.0:8000';
    private $response = '';
    private $ttl = 5;
    private $socketDelay = 0;
    private $logFile = '/tmp/fake_server.log';

    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    public function setSocketDelay($socketDelay)
    {
        $this->socketDelay = $socketDelay;

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
                --uri=%s \
                --response=%s \
                --ttl=%d \
                --socket_delay=%d \
            >> %s 2>&1 &',
            __DIR__,
            escapeshellarg($this->uri),
            $this->response ? escapeshellarg(base64_encode($this->response)) : '',
            $this->ttl,
            $this->socketDelay,
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
