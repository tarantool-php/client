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

namespace Tarantool\Client\Tests\Integration\Requests;

use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;

final class AuthenticateTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     * @dataProvider provideValidCredentials
     *
     * @eval create_user('user_foo', 'foo')
     * @eval create_user('user_empty', '')
     * @eval create_user('user_big', '123456789012345678901234567890123456789012345678901234567890')
     */
    public function testAuthenticateWithValidCredentials(string $username, string $password) : void
    {
        $client = ClientBuilder::createFromEnv()->setOptions([
            'username' => $username,
            'password' => $password,
        ])->build();

        $client->ping();
    }

    public function provideValidCredentials() : iterable
    {
        return [
            ['guest', ''],
            ['user_foo', 'foo'],
            ['user_empty', ''],
            ['user_big', '123456789012345678901234567890123456789012345678901234567890'],
        ];
    }

    /**
     * @dataProvider provideInvalidCredentials
     */
    public function testAuthenticateWithInvalidCredentials(string $errorMessage, int $errorCode, $username, $password) : void
    {
        $client = ClientBuilder::createFromEnv()->setOptions([
            'username' => $username,
            'password' => $password,
        ])->build();

        try {
            $client->ping();
            self::fail(sprintf('Client must throw an exception on authenticating "%s" with the password "%s"', $username, $password));
        } catch (RequestFailed $e) {
            self::assertSame($errorMessage, $e->getMessage());
            self::assertSame($errorCode, $e->getCode());
        }
    }

    public function provideInvalidCredentials() : iterable
    {
        return [
            ["User 'non_existing_user' is not found", 45, 'non_existing_user', 'password'],
            ["Incorrect password supplied for user 'guest'", 47, 'guest', 'password'],
        ];
    }

    /**
     * @eval create_user('user_foo', 'foo')
     * @eval create_space('test_auth_reconnect'):create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
     */
    public function testUseCredentialsAfterReconnect() : void
    {
        $client = ClientBuilder::createFromEnv()->setOptions([
            'username' => 'user_foo',
            'password' => 'foo',
        ])->build();

        $client->getHandler()->getConnection()->close();

        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessage("Space 'test_auth_reconnect' does not exist");

        $client->getSpace('test_auth_reconnect');
    }

    public function testAuthenticateOnceOnOpenedPersistentConnection() : void
    {
        $total = self::getTotalCalls(self::STAT_REQUEST_AUTH);

        $client = ClientBuilder::createFromEnv()
            ->setConnectionOptions(['persistent' => true])
            ->setOptions(['username' => 'guest'])
            ->build();

        // ensure that no persistent connection is opened
        $connection = $client->getHandler()->getConnection();
        $connection->open();
        $connection->close();

        $client->ping();
        $client->ping();

        $client = ClientBuilder::createFromEnv()
            ->setConnectionOptions(['persistent' => true])
            ->setOptions(['username' => 'guest'])
            ->build();

        $client->ping();
        $client->ping();

        $connection->close();

        self::assertSame(1, self::getTotalCalls(self::STAT_REQUEST_AUTH) - $total);
    }

    public function testReauthenticateOnClosedPersistentConnection() : void
    {
        $total = self::getTotalCalls(self::STAT_REQUEST_AUTH);

        $client = ClientBuilder::createFromEnv()
            ->setConnectionOptions(['persistent' => true])
            ->setOptions(['username' => 'guest'])
            ->build();

        // ensure that no persistent connection is opened
        $connection = $client->getHandler()->getConnection();
        $connection->open();
        $connection->close();

        $client->ping();
        $client->ping();

        $connection->close();

        $client = ClientBuilder::createFromEnv()
            ->setConnectionOptions(['persistent' => true])
            ->setOptions(['username' => 'guest'])
            ->build();

        $client->ping();
        $client->ping();

        $connection->close();

        self::assertSame(2, self::getTotalCalls(self::STAT_REQUEST_AUTH) - $total);
    }
}
