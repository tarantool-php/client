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

use PHPUnit\Framework\TestCase;
use Tarantool\Client\Client;
use Tarantool\Client\Connection\Connection;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Schema\Space;

final class SpaceTest extends TestCase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var int
     */
    private $spaceId = 42;

    /**
     * @var Space
     */
    private $space;

    protected function setUp() : void
    {
        $this->client = new Client($this->createMock(Connection::class), $this->createMock(Packer::class));
        $this->space = new Space($this->client, $this->spaceId);
    }

    public function testGetId() : void
    {
        self::assertSame($this->spaceId, $this->space->getId());
    }
}
