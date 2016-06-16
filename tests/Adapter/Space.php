<?php

namespace Tarantool\Client\Tests\Adapter;

use Tarantool\Client\Response;

class Space
{
    private $tarantool;
    private $space;

    public function __construct(\Tarantool $taranool, $space)
    {
        $this->tarantool = $taranool;
        $this->space = $space;
    }

    public function update($key, array $operations, $index = null)
    {
        foreach ($operations as $k => $operation) {
            if (0 === $count = count($operation)) {
                continue;
            }

            $operations[$k] = [
                'op' => $operation[0],
                'field' => $operation[1],
            ];

            if (3 === $count) {
                $operations[$k]['arg'] = $operation[2];
            } else if (5 === $count) {
                $operations[$k]['offset'] = $operation[2];
                $operations[$k]['length'] = $operation[3];
                $operations[$k]['list'] = $operation[4];
            }
        }

        return $this->__call('update', [$key, $operations/*, $index*/]);
    }

    public function flushIndexes()
    {
    }

    public function __call($method, array $args)
    {
        array_unshift($args, $this->space);

        try {
            $result = call_user_func_array([$this->tarantool, $method], $args);
        } catch (\Exception $e) {
            throw ExceptionFactory::create($e);
        }

        return new Response(0, $result);
    }
}
