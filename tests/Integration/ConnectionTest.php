<?php

namespace Tarantool\Tests\Integration;

use Tarantool\Exception\Exception;
use Tarantool\Tests\Assert;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    use Assert;
    use Client;

    protected function setUp()
    {
        self::$client->disconnect();
    }

    public function testConnect()
    {
        self::$client->connect();

        $response = self::$client->ping();
        $this->assertResponse($response);
    }

    /**
     * @dataProvider provideAutoConnectData
     */
    public function testAutoConnect($methodName, array $methodArgs, $space = null)
    {
        $object = $space ? self::$client->getSpace($space) : self::$client;
        self::$client->disconnect();

        $response = call_user_func_array([$object, $methodName], $methodArgs);

        $this->assertResponse($response);
    }

    public function provideAutoConnectData()
    {
        return [
            ['ping', []],
            ['call', ['box.stat']],
            ['evaluate', ['return 1']],

            ['select', [[42]], 'space_conn'],
            ['insert', [[time()]], 'space_conn'],
            ['replace', [[1, 2]], 'space_conn'],
            ['update', [1, [['+', 1, 2]]], 'space_conn'],
            ['delete', [[1]], 'space_conn'],

        ];
    }

    public function testCreateManyConnections()
    {
        for ($i = 10; $i; $i--) {
            Utils::createClient()->connect();
        };
    }

    public function testMultipleConnect()
    {
        self::$client->connect();
        self::$client->connect();
    }

    public function tesMultipleDisconnect()
    {
        self::$client->disconnect();
        self::$client->disconnect();
    }

    /**
     * @expectedException \Tarantool\Exception\ConnectionException
     */
    public function testConnectInvalidHost()
    {
        Utils::createClient('invalid_host')->connect();
    }

    /**
     * @expectedException \Tarantool\Exception\ConnectionException
     */
    public function testConnectInvalidPort()
    {
        Utils::createClient(null, 123456)->connect();
    }

    /**
     * @dataProvider provideCredentials
     */
    public function testAuthenticate($username, $password)
    {
        Utils::createClient()->authenticate($username, $password);
    }

    public function provideCredentials()
    {
        return [
            ['user_foo', 'foo'],
            ['user_empty', ''],
            ['user_big', '123456789012345678901234567890123456789012345678901234567890'],
        ];
    }

    /**
     * @dataProvider provideInvalidCredentials
     */
    public function testAuthenticateWithInvalidCredentials($username, $password, $errorMessage, $errorCode)
    {
        try {
            Utils::createClient()->authenticate($username, $password);
            $this->fail();
        } catch (Exception $e) {
            $this->assertSame($errorMessage, $e->getMessage());
            $this->assertSame($errorCode, $e->getCode());
        }
    }

    public function provideInvalidCredentials()
    {
        return [
            ['non_existing_user', 'password', "User 'non_existing_user' is not found", 45],
            ['guest', 'password', "Incorrect password supplied for user 'guest'", 47],
            ['guest', '', "Incorrect password supplied for user 'guest'", 47],
        ];
    }

    public function testAuthenticateDoesntSetInvalidCredentials()
    {
        self::$client->authenticate('user_conn', 'conn');
        self::$client->getSpace('space_conn')->select();

        try {
            self::$client->authenticate('user_foo', 'incorrect_password');
        } catch (Exception $e) {
            $this->assertSame("Incorrect password supplied for user 'user_foo'", $e->getMessage());
            self::$client->disconnect();
            self::$client->getSpace('space_conn')->select();

            return;
        }

        $this->fail();
    }

    /**
     * @expectedException \Tarantool\Exception\Exception
     * @expectedExceptionMessage Space 'space_conn' does not exist
     */
    public function testUseCredentialsAfterReconnect()
    {
        $client = Utils::createClient();

        $client->authenticate('user_foo', 'foo');
        $client->disconnect();
        $client->getSpace('space_conn')->select();
    }

    public function testRegenerateSalt()
    {
        $client = Utils::createClient();

        $client->connect();
        $client->disconnect();
        $client->authenticate('user_foo', 'foo');
    }

    public function testReconnectOnEmptySalt()
    {
        $client = Utils::createClient();
        $client->getConnection()->open();
        $client->authenticate('user_foo', 'foo');
    }

    /**
     * @group pureonly
     */
    public function testRetryableConnection()
    {
        $connection = self::$client->getConnection();
        $client = Utils::createClient($connection);

        $client->connect();
        $this->assertFalse($client->isDisconnected());

        $client->disconnect();
        $this->assertTrue($client->isDisconnected());
    }
}
