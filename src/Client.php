<?php

namespace Tarantool\Client;

use Tarantool\Client\Request\Request;

interface Client
{
    public function sendRequest(Request $request);
}
