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

class WriteHandler implements Handler
{
    private $data;

    public function __construct(string $data)
    {
        $this->data = $data;
    }

    public function __invoke($conn, string $sid) : ?bool
    {
        printf("$sid:   Write data (base64): %s.\n", base64_encode($this->data));
        fwrite($conn, $this->data);

        return null;
    }
}
