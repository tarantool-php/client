<?php

declare(strict_types=1);

/*
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    public function __invoke($conn, string $sid) : ?bool
    {
        if ($this->disabled) {
            return null;
        }

        printf("$sid:   Sleep %d sec.\n", $this->socketDelay);
        sleep($this->socketDelay);

        if ($this->once) {
            $this->disabled = true;
        }

        return null;
    }
}
