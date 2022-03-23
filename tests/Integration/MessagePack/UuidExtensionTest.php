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

namespace Tarantool\Client\Tests\Integration\MessagePack;

use Symfony\Component\Uid\Uuid;
use Tarantool\Client\Client;
use Tarantool\Client\Packer\Extension\UuidExtension;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @requires Tarantool >=2.4
 *
 * @lua uuid = require('uuid').fromstr('64d22e4d-ac92-4a23-899a-e59f34af5479')
 * @lua space = create_space('uuid_primary')
 * @lua space:format({{name = 'id', type = 'uuid'}})
 * @lua space:create_index("primary", {parts = {1, 'uuid'}})
 * @lua space:insert({uuid})
 */
final class UuidExtensionTest extends TestCase
{
    private const UUID_RFC4122 = '64d22e4d-ac92-4a23-899a-e59f34af5479';

    public function testBinarySelectByUuidKeySucceeds() : void
    {
        $client = self::createClientWithUuidSupport();

        $uuid = new Uuid(self::UUID_RFC4122);
        $space = $client->getSpace('uuid_primary');
        $result = $space->select(Criteria::key([$uuid]));

        self::assertTrue(isset($result[0][0]));
        self::assertTrue($uuid->equals($result[0][0]));
    }

    /**
     * @requires Tarantool >=2.10-stable
     */
    public function testSqlSelectByUuidKeySucceeds() : void
    {
        $client = self::createClientWithUuidSupport();

        $uuid = new Uuid(self::UUID_RFC4122);
        $result = $client->executeQuery('SELECT * FROM "uuid_primary" WHERE "id" = ?', $uuid);

        self::assertFalse($result->isEmpty());
        self::assertTrue($uuid->equals($result->getFirst()['id']));
    }

    public function testLuaPackingAndUnpacking() : void
    {
        $client = self::createClientWithUuidSupport();

        [$uuid] = $client->evaluate('return require("uuid").fromstr(...)', self::UUID_RFC4122);
        self::assertInstanceOf(Uuid::class, $uuid);
        self::assertSame($uuid->toRfc4122(), self::UUID_RFC4122);

        [$isEqual] = $client->evaluate(
            sprintf("return require('uuid').fromstr('%s') == ...", self::UUID_RFC4122),
            new Uuid(self::UUID_RFC4122)
        );
        self::assertTrue($isEqual);
    }

    private static function createClientWithUuidSupport() : Client
    {
        return ClientBuilder::createFromEnv()
            ->setPackerFactory(static function () {
                return PurePacker::fromExtensions(new UuidExtension());
            })
            ->build();
    }
}
