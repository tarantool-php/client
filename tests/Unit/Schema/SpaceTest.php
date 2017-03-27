<?php

namespace Tarantool\Client\Tests\Unit;

use Tarantool\Client\Client;
use Tarantool\Client\Schema\Space;

class SpaceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Tarantool\Client\Client|\PHPUnit_Framework_MockObject_MockObject|
     */
    private $client;

    /**
     * @var int
     */
    private $spaceId;

    /**
     * @var Space
     */
    private $space;

    protected function setUp()
    {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->space = new Space($this->client, $this->spaceId);
    }

    public function testGetId()
    {
        $this->assertSame($this->spaceId, $this->space->getId());
    }
}
