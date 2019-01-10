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
use Tarantool\Client\Schema\Space;

final class SpaceTest extends TestCase
{
    /**
     * @var Client|MockObject
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
        $this->client = $this->createMock(Client::class);
        $this->space = new Space($this->client, $this->spaceId);
    }

    public function testGetId() : void
    {
        self::assertSame($this->spaceId, $this->space->getId());
    }
}
