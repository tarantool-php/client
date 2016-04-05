<?php

namespace Tarantool\Tests\Integration;

class FakeServerBuilder
{
    private $host = '0.0.0.0';
    private $port = 8000;
    private $response = '';
    private $socketDelay = 0;
    private $ttl = 5;
    private $logFile = '/tmp/fake_server.log';

    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    public function setPort($port)
    {
        $this->port = $port;

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
                --host=%s \
                --port=%d \
                --response=%s \
                --socket_delay=%d \
                --ttl=%d \
            >> %s 2>&1 &',
            __DIR__,
            escapeshellarg($this->host),
            $this->port,
            $this->response ? escapeshellarg(base64_encode($this->response)) : '',
            $this->socketDelay,
            $this->ttl,
            escapeshellarg($this->logFile)
        );
    }

    public function start()
    {
        exec($this->getCommand(), $output, $result);
        if (0 !== $result) {
            throw new \RuntimeException("Unable to start the fake server ($this->host:$this->port).");
        }

        $stopTime = time() + 5;
        while (time() < $stopTime) {
            if (@stream_socket_client("$this->host:$this->port")) {
                return;
            }
            usleep(100);
        }

        throw new \RuntimeException("Unable to connect to the fake server ($this->host:$this->port).");
    }
}
