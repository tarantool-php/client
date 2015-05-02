<?php

namespace Tarantool\Tests\Integration;

trait Assert
{
    protected function assertResponse($response)
    {
        $this->assertInstanceOf('Tarantool\Response', $response);
    }
}
