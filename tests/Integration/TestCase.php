<?php

/**
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Tests\Integration;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PHPUnit\Util\Test;
use Tarantool\Client\Client;

abstract class TestCase extends BaseTestCase
{
    protected const STAT_REQUEST_SELECT = 'SELECT';
    protected const STAT_REQUEST_AUTH = 'AUTH';

    /**
     * @var Client
     */
    protected $client;

    public static function setUpBeforeClass() : void
    {
        $annotations = Test::parseTestMethodAnnotations(static::class);

        self::enableCustomAnnotations($annotations['class']);
    }

    protected function setUp() : void
    {
        $this->client = ClientBuilder::createFromEnv()->build();

        $annotations = $this->getAnnotations();

        self::enableCustomAnnotations($annotations['method']);
    }

    private static function enableCustomAnnotations(array $annotations) : void
    {
        if (empty($annotations['eval'])) {
            return;
        }

        $client = ClientBuilder::createFromEnv()->build();
        foreach ($annotations['eval'] as $expr) {
            $client->evaluate($expr);
        }
    }

    final protected static function getTotalCalls(string $requestName) : int
    {
        $client = ClientBuilder::createFromEnv()->build();

        return $client->evaluate("return box.stat().$requestName.total")[0];
    }

    final protected static function getTarantoolVersion() : int
    {
        $connection = ClientBuilder::createFromEnv()->createConnection();

        return $connection->open()->getServerVersion();
    }
}
