<?php

/**
 * This file is part of the tarantool/client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Client;
use Tarantool\Client\Connection\Connection;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Exception\CommunicationFailed;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Tests\PhpUnitCompat;
use Tarantool\PhpUnit\Annotation\Requirement\TarantoolVersionRequirement;
use Tarantool\PhpUnit\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use PhpUnitCompat;

    /** @var Client|null */
    protected $client;

    /**
     * @before
     */
    protected function getClient() : Client
    {
        return $this->client
            ?? $this->client = ClientBuilder::createFromEnv()->build();
    }

    final protected function tarantoolVersionSatisfies(string $constraints) : bool
    {
        return null === (new TarantoolVersionRequirement($this->client))->check($constraints);
    }

    final protected static function triggerUnexpectedResponse(Handler $handler, Request $initialRequest, int $sync = 0) : Connection
    {
        $connection = $handler->getConnection();
        $packer = $handler->getPacker();
        $rawRequest = $packer->pack($initialRequest, $sync);

        // write a request without reading a response
        $connection->open();
        if (!\fwrite(self::getRawStream($connection), $rawRequest)) {
            throw new CommunicationFailed('Unable to write request');
        }

        return $connection;
    }

    final public static function getRawStream(StreamConnection $connection)
    {
        $prop = (new \ReflectionObject($connection))->getProperty('stream');
        $prop->setAccessible(true);

        return $prop->getValue($connection);
    }
}
