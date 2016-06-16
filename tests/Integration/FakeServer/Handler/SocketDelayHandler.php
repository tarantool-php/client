<?php

namespace Tarantool\Client\Tests\Integration\FakeServer\Handler;

class SocketDelayHandler implements Handler
{
    private $socketDelay;
    private $once;
    private $disabled = false;

    public function __construct($socketDelay, $once = null)
    {
        $this->socketDelay = $socketDelay;
        $this->once = (bool) $once;
    }

    public function __invoke($conn, $sid)
    {
        if ($this->disabled) {
            return;
        }

        printf("$sid:   Sleep %d sec.\n", $this->socketDelay);
        sleep($this->socketDelay);

        if ($this->once) {
            $this->disabled = true;
        }
    }
}
