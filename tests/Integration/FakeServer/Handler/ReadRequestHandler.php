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

use Tarantool\Client\Packer\PacketLength;

/** Reads and discards one complete IPROTO request from the client. */
final class ReadRequestHandler implements Handler
{
    public function __invoke($conn, string $sid) : ?bool
    {
        $length = PacketLength::tryUnpack($this->read($conn, PacketLength::SIZE_BYTES));
        if (null === $length) {
            throw new \RuntimeException('Unable to read request length');
        }

        $this->read($conn, $length);

        return null;
    }

    private function read($conn, int $length) : string
    {
        $data = '';

        while ($length > \strlen($data)) {
            $chunk = \fread($conn, $length - \strlen($data));
            if (false === $chunk || '' === $chunk) {
                throw new \RuntimeException('Unable to read request from client');
            }

            $data .= $chunk;
        }

        return $data;
    }
}
