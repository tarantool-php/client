<?php

declare(strict_types=1);

/*
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tarantool\Client\Tests\Integration;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PHPUnit\Util\Test;
use Tarantool\Client\Client;

abstract class TestCase extends BaseTestCase
{
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

    protected static function getTotalSelectCalls() : int
    {
        $client = ClientBuilder::createFromEnv()->build();
        $response = $client->evaluate('return box.stat().SELECT.total');

        return $response->getData()[0];
    }

    protected static function getTarantoolVersion() : string
    {
        $client = ClientBuilder::createFromEnv()->build();
        $response = $client->evaluate('return box.info().version');

        return $response->getData()[0];
    }

    protected static function matchTarantoolVersion(string $expr, &$ver) : bool
    {
        if (!preg_match('/(?P<op><|lt|<=|le|>|gt|>=|ge|==|=|eq|!=|<>|ne)?(?P<ver>\d.*)/', $expr, $matches)) {
            throw new \InvalidArgumentException('Invalid version expression.');
        }

        return version_compare($ver = self::getTarantoolVersion(), $matches['ver'], $matches['op'] ?? '=');
    }
}
