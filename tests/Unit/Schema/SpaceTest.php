<?php

namespace Tarantool\Tests\Unit;

use Tarantool\Schema\Space;

class SpaceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Tarantool\Client|\PHPUnit_Framework_MockObject_MockObject|
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
        $this->client = $this->getMock('Tarantool\Client');
        $this->space = new Space($this->client, $this->spaceId);
    }

    public function testGetId()
    {
        $this->assertSame($this->spaceId, $this->space->getId());
    }
}
