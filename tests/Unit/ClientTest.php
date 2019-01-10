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

namespace Tarantool\Client\Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tarantool\Client\Client;
use Tarantool\Client\Connection\Connection;
use Tarantool\Client\Packer\Packer;

final class ClientTest extends TestCase
{
    /**
     * @var Connection|MockObject
     */
    private $connection;

    /**
     * @var Packer|MockObject
     */
    private $packer;

    /**
     * @var Client
     */
    private $client;

    protected function setUp() : void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->packer = $this->createMock(Packer::class);
        $this->client = new Client($this->connection, $this->packer);
    }

    public function testGetConnection() : void
    {
        self::assertSame($this->connection, $this->client->getConnection());
    }

    public function testGetPacker() : void
    {
        self::assertSame($this->packer, $this->client->getPacker());
    }
}
