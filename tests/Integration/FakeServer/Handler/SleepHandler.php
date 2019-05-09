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

class SleepHandler implements Handler
{
    private $seconds;
    private $once;
    private $disabled = false;

    public function __construct(int $seconds, bool $once = false)
    {
        $this->seconds = $seconds;
        $this->once = $once;
    }

    public function __invoke($conn, string $sid) : ?bool
    {
        if ($this->disabled) {
            return null;
        }

        printf("$sid:   Sleep %d sec.\n", $this->seconds);
        sleep($this->seconds);

        if ($this->once) {
            $this->disabled = true;
        }

        return null;
    }
}
