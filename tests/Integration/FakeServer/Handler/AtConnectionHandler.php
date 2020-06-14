<?php

/**
 * This file is part of the tarantool/client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Tests\Integration\FakeServer\Handler;

class AtConnectionHandler implements Handler
{
    private $atConnectionNumber;
    private $handler;
    private $connectionCount = 0;

    public function __construct(int $atConnectionNumber, Handler $handler)
    {
        $this->atConnectionNumber = $atConnectionNumber;
        $this->handler = $handler;
    }

    public function __invoke($conn, string $sid) : ?bool
    {
        ++$this->connectionCount;

        if ($this->connectionCount === $this->atConnectionNumber) {
            return ($this->handler)($conn, $sid);
        }

        return null;
    }
}
