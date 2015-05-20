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
    public function testAutoConnect($methodName, array $methodArgs)
    {
        $response = call_user_func_array([self::$client, $methodName], $methodArgs);

        $this->assertResponse($response);
    }

    public function provideAutoConnectData()
    {
        return [
            ['ping', []],
            ['select', ['space_conn', [42]]],
            ['insert', ['space_conn', [1]]],
            ['replace', ['space_conn', [1, 2]]],
            ['update', ['space_conn', 1, [['+', 1, 2]]]],
            ['delete', ['space_conn', [1]]],
            ['call', ['box.stat']],
            ['evaluate', ['return 1']],
        ];
    }

    public function testCreateManyConnections()
    {
        for ($i = 10; $i; $i--) {
            self::createClient()->connect();
        };
    }

    public function testConnectMany()
    {
        self::$client->connect();
        self::$client->connect();
    }

    public function testDisconnectMany()
    {
        self::$client->disconnect();
        self::$client->disconnect();
    }

    /**
     * @expectedException \Tarantool\Exception\ConnectionException
     * @expectedExceptionMessageRegExp /Unable to connect: (Unknown host|Host name lookup failure)\./
     */
    public function testConnectInvalidHost()
    {
        self::createClient('invalid_host')->connect();
    }

    /**
     * @expectedException \Tarantool\Exception\ConnectionException
     * @expectedExceptionMessage Unable to connect: Connection refused.
     */
    public function testConnectInvalidPort()
    {
        self::createClient(null, 123456)->connect();
    }

    /**
     * @dataProvider provideCredentials
     */
    public function testAuthenticate($username, $password)
    {
        self::createClient()->authenticate($username, $password);
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
            self::createClient()->authenticate($username, $password);
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

    /**
     * @expectedException \Tarantool\Exception\Exception
     * @expectedExceptionMessage Read access denied for user 'user_foo' to space '_space'
     */
    public function testUseCredentialsAfterReconnect()
    {
        $client = self::createClient();

        $client->authenticate('user_foo', 'foo');
        $client->disconnect();
        $client->select('space_conn');
    }

    public function testRegenerateSalt()
    {
        $client = self::createClient();

        $client->connect();
        $client->disconnect();
        $client->authenticate('user_foo', 'foo');
    }

    public function testReconnectOnEmptySalt()
    {
        $client = self::createClient();
        $client->getConnection()->open();
        $client->authenticate('user_foo', 'foo');
    }

    public function testCacheSchema()
    {
        $total = self::getTotalSelectCalls();

        self::$client->flushSchema();
        self::$client->select('space_conn');
        self::$client->select('space_conn');

        $this->assertSame(3, self::getTotalSelectCalls() - $total);
    }

    public function testFlushSchema()
    {
        $total = self::getTotalSelectCalls();

        self::$client->flushSchema();
        self::$client->select('space_conn');
        self::$client->flushSchema();
        self::$client->select('space_conn');

        $this->assertSame(4, self::getTotalSelectCalls() - $total);
    }

    private static function getTotalSelectCalls()
    {
        $response = self::$client->evaluate('return box.stat().SELECT.total');

        return $response->getData()[0];
    }
}
