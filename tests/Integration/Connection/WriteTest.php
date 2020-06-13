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

namespace Tarantool\Client\Tests\Integration\Connection;

use Tarantool\Client\Keys;
use Tarantool\Client\Packer\PacketLength;
use Tarantool\Client\Tests\Integration\TestCase;

final class WriteTest extends TestCase
{
    public function testSendMalformedRequest() : void
    {
        $handler = $this->client->getHandler();
        $conn = $handler->getConnection();

        $data = 'malformed';
        $data = PacketLength::pack(strlen($data)).$data;

        $conn->open();
        $data = $conn->send($data);

        $response = $handler->getPacker()->unpack($data);

        self::assertTrue($response->isError());
        self::assertSame('Invalid MsgPack - packet header', $response->getBodyField(Keys::ERROR_24));
    }
}
