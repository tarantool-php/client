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

namespace Tarantool\Client\Tests\Integration\Connection;

use Tarantool\Client\IProto;
use Tarantool\Client\Packer\PackUtils;
use Tarantool\Client\Tests\Integration\TestCase;

final class WriteTest extends TestCase
{
    public function testSendMalformedRequest() : void
    {
        $conn = $this->client->getConnection();

        $data = 'malformed';
        $data = PackUtils::packLength(strlen($data)).$data;

        $conn->open();
        $data = $conn->send($data);

        $rawResponse = $this->client->getPacker()->unpack($data);

        self::assertTrue($rawResponse->isError());
        self::assertSame('Invalid MsgPack - packet header', $rawResponse->getBodyField(IProto::ERROR));
    }
}
