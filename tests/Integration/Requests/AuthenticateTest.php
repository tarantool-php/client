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

use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;

final class AuthenticateTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     * @testWith
     * ["guest", ""]
     * ["user_foo", "foo"]
     * ["user_empty", ""]
     * ["user_big", "123456789012345678901234567890123456789012345678901234567890"]
     */
    public function testAuthenticateWithValidCredentials(string $username, string $password) : void
    {
        $client = ClientBuilder::createFromEnv()->setOptions([
            'username' => $username,
            'password' => $password,
        ])->build();

        $client->ping();
    }

    /**
     * @testWith
     * ["User 'non_existing_user' is not found", 45, "non_existing_user", "password"]
     * ["Incorrect password supplied for user 'guest'", 47, "guest", "password"]
     */
    public function testAuthenticateWithInvalidCredentials(string $errorMessage, int $errorCode, $username, $password) : void
    {
        $client = ClientBuilder::createFromEnv()->setOptions([
            'username' => $username,
            'password' => $password,
        ])->build();

        try {
            $client->ping();
            self::fail(sprintf('Client must throw an exception on authenticating "%s" with the password "%s".', $username, $password));
        } catch (RequestFailed $e) {
            self::assertSame($errorMessage, $e->getMessage());
            self::assertSame($errorCode, $e->getCode());
        }
    }

    public function testUseCredentialsAfterReconnect() : void
    {
        $client = ClientBuilder::createFromEnv()->setOptions([
            'username' => 'user_foo',
            'password' => 'foo',
        ])->build();

        $client->getHandler()->getConnection()->close();

        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessage("Space 'space_conn' does not exist");

        $client->getSpace('space_conn');
    }
}
