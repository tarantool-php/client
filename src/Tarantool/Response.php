<?php

namespace Tarantool;

class Response
{
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
