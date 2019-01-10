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

namespace Tarantool\Client\Tests\Integration\Requests;

use Tarantool\Client\Exception\Exception;
use Tarantool\Client\Tests\Integration\TestCase;

final class AuthenticateTest extends TestCase
{
    /**
     * @dataProvider provideValidCredentials
     * @doesNotPerformAssertions
     */
    public function testAuthenticateWithValidCredentials(string $username, ?string $password) : void
    {
        $this->client->authenticate($username, $password);
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
        try {
            $this->client->authenticate($username, $password);
            $this->fail();
        } catch (Exception $e) {
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

    public function testAuthenticateDoesntSetInvalidCredentials() : void
    {
        $this->client->authenticate('user_conn', 'conn');
        $this->client->getSpace('space_conn')->select();

        try {
            $this->client->authenticate('user_foo', 'incorrect_password');
        } catch (Exception $e) {
            self::assertSame("Incorrect password supplied for user 'user_foo'", $e->getMessage());
            $this->client->disconnect();
            $this->client->getSpace('space_conn')->select();

            return;
        }

        $this->fail();
    }

    public function testUseCredentialsAfterReconnect() : void
    {
        $this->client->authenticate('user_foo', 'foo');
        $this->client->disconnect();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Space 'space_conn' does not exist");

        $this->client->getSpace('space_conn');
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testRegenerateSalt() : void
    {
        $this->client->connect();
        $this->client->disconnect();
        $this->client->authenticate('user_foo', 'foo');
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testReconnectOnEmptySalt() : void
    {
        $this->client->getConnection()->open();
        $this->client->authenticate('user_foo', 'foo');
    }
}
