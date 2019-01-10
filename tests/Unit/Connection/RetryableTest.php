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

namespace Tarantool\Client\Tests\Unit\Connection;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tarantool\Client\Connection\Connection;
use Tarantool\Client\Connection\Retryable;
use Tarantool\Client\Exception\ConnectionException;

final class RetryableTest extends TestCase
{
    /**
     * @var Connection|MockObject
     */
    private $wrappedConnection;

    /**
     * @var Retryable
     */
    private $connection;

    protected function setUp() : void
    {
        $this->wrappedConnection = $this->createMock(Connection::class);
        $this->connection = new Retryable($this->wrappedConnection);
    }

    public function testOpen() : void
    {
        $this->wrappedConnection->expects($this->once())->method('open')
            ->willReturn('salt');

        self::assertSame('salt', $this->connection->open());
    }

    public function testClose() : void
    {
        $this->wrappedConnection->expects($this->once())->method('close');

        $this->connection->close();
    }

    public function testIsClosed() : void
    {
        $this->wrappedConnection->expects($this->exactly(2))->method('isClosed')
            ->will($this->onConsecutiveCalls(false, true));

        self::assertFalse($this->connection->isClosed());
        self::assertTrue($this->connection->isClosed());
    }

    public function testSend() : void
    {
        $this->wrappedConnection->expects($this->once())->method('send')
            ->with('request')
            ->willReturn('response');

        self::assertSame('response', $this->connection->send('request'));
    }

    public function testSuccessRetry() : void
    {
        /** @var ConnectionException $exception */
        $exception = $this->createMock(ConnectionException::class);

        $this->wrappedConnection->expects($this->exactly(3))->method('open')
            ->will($this->onConsecutiveCalls(
                $this->throwException($exception),
                $this->throwException($exception),
                'salt'
            ));

        $connection = new Retryable($this->wrappedConnection, 3);

        self::assertSame('salt', $connection->open());
    }

    public function testThrowConnectionException() : void
    {
        /** @var ConnectionException $exception */
        $exception = $this->createMock(ConnectionException::class);

        $this->wrappedConnection->expects($this->exactly(4))->method('open')
            ->willThrowException($exception);

        $connection = new Retryable($this->wrappedConnection, 3);

        $this->expectException(ConnectionException::class);
        $connection->open();
    }

    public function testThrowException() : void
    {
        $exception = new \Exception('foo');

        $this->wrappedConnection->expects($this->atLeastOnce())->method('open')
            ->will($this->onConsecutiveCalls(
                $this->throwException($exception),
                'salt'
            ));

        $connection = new Retryable($this->wrappedConnection, 2);

        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());
        $connection->open();
    }
}
