<?php

namespace Tarantool\Client\Tests;

trait Assert
{
    protected function assertResponse($response)
    {
        $this->assertInstanceOf('Tarantool\Client\Response', $response);
    }
}
