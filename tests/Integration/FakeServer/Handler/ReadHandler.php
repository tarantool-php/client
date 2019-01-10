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

class ReadHandler implements Handler
{
    private $length;
    private $stopOnNoData;

    public function __construct(int $length, bool $stopOnNoData = true)
    {
        $this->length = $length;
        $this->stopOnNoData = $stopOnNoData;
    }

    public function __invoke($conn, string $sid) : ?bool
    {
        printf("$sid:   Read data ($this->length bytes).\n");
        $data = fread($conn, $this->length);

        if ($this->stopOnNoData && !$data) {
            return false;
        }

        return null;
    }
}
