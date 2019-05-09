<?php

/**
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Tests\Integration\FakeServer\Handler;

final class ChainHandler implements Handler
{
    private $handlers;

    public function __construct(array $handlers)
    {
        $this->handlers = $handlers;
    }

    public function __invoke($conn, string $sid) : ?bool
    {
        foreach ($this->handlers as $handler) {
            if (false === $handler($conn, $sid)) {
                break;
            }
        }

        return null;
    }
}
