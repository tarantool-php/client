<?php

namespace Tarantool\Tests\Integration;

use Tarantool\Exception\ConnectionException;
use Tarantool\Exception\Exception;
use Tarantool\Packer\PackUtils;
use Tarantool\Tests\Assert;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    use Assert;
    use Client;

    private static $fakeServerPort = 8000;

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
    public function testAuthenticate($username, $password = null)
    {
        $client = Utils::createClient();

        (1 === func_num_args())
            ? $client->authenticate($username)
            : $client->authenticate($username, $password);
    }

    public function provideCredentials()
    {
        return [
            ['guest'],
            ['guest', null],
            ['user_foo', 'foo'],
            ['user_empty', ''],
            ['user_big', '123456789012345678901234567890123456789012345678901234567890'],
        ];
    }

    /**
     * @dataProvider provideInvalidCredentials
     */
    public function testAuthenticateWithInvalidCredentials($errorMessage, $errorCode, $username, $password = null)
    {
        $client = Utils::createClient();

        try {
            (3 === func_num_args())
                ? $client->authenticate($username)
                : $client->authenticate($username, $password);

            $this->fail();
        } catch (Exception $e) {
            $this->assertSame($errorMessage, $e->getMessage());
            $this->assertSame($errorCode, $e->getCode());
        }
    }

    public function provideInvalidCredentials()
    {
        return [
            ["User 'non_existing_user' is not found", 45, 'non_existing_user', 'password'],
            ["User 'non_existing_user' is not found", 45, 'non_existing_user'],
            ["Incorrect password supplied for user 'guest'", 47, 'guest', 'password'],
            ["Incorrect password supplied for user 'guest'", 47, 'guest', ''],
            ["Incorrect password supplied for user 'guest'", 47, 'guest', 0],
            ["Invalid MsgPack - authentication request body", 20, 'user_conn'],
        ];
    }

    public function testAuthenticateDoesntSetInvalidCredentials()
    {
        $client = Utils::createClient();

        $client->authenticate('user_conn', 'conn');
        $client->getSpace('space_conn')->select();

        try {
            $client->authenticate('user_foo', 'incorrect_password');
        } catch (Exception $e) {
            $this->assertSame("Incorrect password supplied for user 'user_foo'", $e->getMessage());
            $client->disconnect();
            $client->getSpace('space_conn')->select();

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

    public function testReadLargeResponse()
    {
        $data = str_repeat('x', 1024 * 1024);
        $result = self::$client->evaluate('return ...', [$data]);

        $this->assertTrue($data === $result->getData()[0]);
    }

    /**
     * @dataProvider Tarantool\Tests\GreetingDataProvider::provideGreetingsWithInvalidServerName
     */
    public function testParseGreetingWithInvalidServerName($greeting)
    {
        $port = ++self::$fakeServerPort;

        (new FakeServerBuilder())
            ->setPort($port)
            ->setResponse($greeting)
            ->start();

        $client = Utils::createClient('0.0.0.0', $port);

        try {
            $client->connect();
        } catch (Exception $e) {
            $this->assertSame(
                '' === $greeting ? 'Unable to read greeting.' : 'Invalid greeting: unable to recognize Tarantool server.',
                $e->getMessage()
            );

            return;
        }

        $this->fail();
    }

    /**
     * @dataProvider Tarantool\Tests\GreetingDataProvider::provideGreetingsWithInvalidSalt
     *
     * @expectedException \Tarantool\Exception\Exception
     * @expectedExceptionMessage Invalid greeting: unable to parse salt.
     */
    public function testParseGreetingWithInvalidSalt($greeting)
    {
        $port = ++self::$fakeServerPort;

        (new FakeServerBuilder())
            ->setPort($port)
            ->setResponse($greeting)
            ->start();

        $client = Utils::createClient('0.0.0.0', $port);
        $client->connect();
    }

    /**
     * @expectedException \Tarantool\Exception\Exception
     * @expectedExceptionMessage Unable to read greeting.
     */
    public function testReadEmptyGreeting()
    {
        $port = ++self::$fakeServerPort;

        (new FakeServerBuilder())
            ->setPort($port)
            ->start();

        $client = Utils::createClient('0.0.0.0', $port);
        $client->connect();
    }

    public function testConnectTimeout()
    {
        $connectTimeout = 2.0;

        // http://stackoverflow.com/a/904609/1160901
        $client = Utils::createClient('10.255.255.1', null, ['connect_timeout' => $connectTimeout]);

        $start = microtime(true);

        try {
            $client->ping();
        } catch (ConnectionException $e) {
            $time = microtime(true) - $start;
            $this->assertSame('Unable to connect: Connection timed out.', $e->getMessage());
            $this->assertGreaterThanOrEqual($connectTimeout, $time);
            $this->assertLessThanOrEqual($connectTimeout + 0.1, $time);

            return;
        }

        $this->fail();
    }

    public function testSocketTimeout()
    {
        $socketTimeout = 2.0;
        $port = rand(8000, 8999);

        (new FakeServerBuilder())
            ->setPort($port)
            ->setSocketDelay($socketTimeout + 2)
            ->start();

        $socketTimeout = 2.0;
        $client = Utils::createClient('0.0.0.0', $port, ['socket_timeout' => $socketTimeout]);

        $start = microtime(true);

        try {
            $client->ping();
        } catch (ConnectionException $e) {
            $time = microtime(true) - $start;
            $this->assertSame('Unable to read greeting.', $e->getMessage());
            $this->assertGreaterThanOrEqual($socketTimeout, $time);
            $this->assertLessThanOrEqual($socketTimeout + 0.1, $time);

            return;
        }

        $this->fail();
    }

    /**
     * @group pureonly
     *
     * @expectedException \Tarantool\Exception\ConnectionException
     * @expectedExceptionMessage Unable to read response length.
     */
    public function testThrowExceptionOnMalformedRequest()
    {
        $conn = self::$client->getConnection();

        $data = 'malformed';
        $data = PackUtils::packLength(strlen($data)).$data;

        $conn->open();
        $conn->send($data);
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
