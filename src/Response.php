<?php

namespace Tarantool\Client;

class Response
{
    const TYPE_ERROR = 0x8000;

    private $sync;
    private $data;

    public function __construct($sync, array $data = null)
    {
        $this->sync = $sync;
        $this->data = $data;
    }

    public function getSync()
    {
        return $this->sync;
    }

    public function getData()
    {
        return $this->data;
    }
}
