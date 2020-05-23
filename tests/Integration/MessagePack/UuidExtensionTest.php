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

namespace Tarantool\Client\Tests\Integration\MessagePack;

use Symfony\Component\Uid\Uuid;
use Tarantool\Client\Packer\Extension\UuidExtension;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @requires Tarantool >=2.4
 * @requires package symfony/uid
 * @requires clientPacker pure
 */
final class UuidExtensionTest extends TestCase
{
    /**
     * @lua uuid = require('uuid')
     * @lua space = create_space('uuid')
     * @lua space:create_index("primary", {parts = {1, 'uuid'}})
     * @lua space:insert({uuid.fromstr('64d22e4d-ac92-4a23-899a-e59f34af5479')})
     */
    public function testPackingAndUnpacking() : void
    {
        $client = ClientBuilder::createFromEnv()
            ->setPackerPureFactory(static function () {
                return PurePacker::fromExtensions(new UuidExtension());
            })
            ->build();

        $space = $client->getSpace('uuid');
        $result = $space->select(Criteria::key([new Uuid('64d22e4d-ac92-4a23-899a-e59f34af5479')]));

        self::assertSame('64d22e4d-ac92-4a23-899a-e59f34af5479', $result[0][0]->toRfc4122());
    }
}
