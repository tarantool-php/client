<?php

namespace Tarantool\Client\Tests\Unit;

use Tarantool\Client\Response;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideConstructorArgs
     */
    public function testConstructorAndGetters($sync, $data)
    {
        $response = new Response($sync, $data);

        $this->assertSame($sync, $response->getSync());
        $this->assertSame($data, $response->getData());
    }

    public function provideConstructorArgs()
    {
        return [
            [42, ['foo' => 1, 'bar' => 2]],
            [0, null],
        ];
    }
}
