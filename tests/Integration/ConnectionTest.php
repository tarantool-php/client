<?php

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Exception\ConnectionException;
use Tarantool\Client\Exception\Exception;
use Tarantool\Client\Packer\PackUtils;
use Tarantool\Client\Tests\Assert;
use Tarantool\Client\Tests\GreetingDataProvider;
use Tarantool\Client\Tests\Integration\FakeServer\FakeServerBuilder;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\ChainHandler;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\NoopHandler;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\ResponseHandler;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\SocketDelayHandler;

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
        $clientBuilder = ClientBuilder::createFromEnv();

        for ($i = 10; $i; $i--) {
            $clientBuilder->build()->connect();
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
     * @group tcp_only
     * @expectedException \Tarantool\Client\Exception\ConnectionException
     */
    public function testConnectInvalidHost()
    {
        $client = ClientBuilder::createFromEnv()
            ->setHost('invalid_host')
            ->build();

        $client->connect();
    }

    /**
     * @group tcp_only
     * @expectedException \Tarantool\Client\Exception\ConnectionException
     */
    public function testConnectInvalidPort()
    {
        $client = ClientBuilder::createFromEnv()
            ->setPort(123456)
            ->build();

        $client->connect();
    }

    /**
     * @dataProvider provideCredentials
     */
    public function testAuthenticate($username, $password = null)
    {
        $client = ClientBuilder::createFromEnv()->build();

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
        $client = ClientBuilder::createFromEnv()->build();

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
        $client = ClientBuilder::createFromEnv()->build();

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
     * @expectedException \Tarantool\Client\Exception\Exception
     * @expectedExceptionMessage Space 'space_conn' does not exist
     */
    public function testUseCredentialsAfterReconnect()
    {
        $client = ClientBuilder::createFromEnv()->build();

        $client->authenticate('user_foo', 'foo');
        $client->disconnect();
        $client->getSpace('space_conn')->select();
    }

    public function testRegenerateSalt()
    {
        $client = ClientBuilder::createFromEnv()->build();

        $client->connect();
        $client->disconnect();
        $client->authenticate('user_foo', 'foo');
    }

    public function testReconnectOnEmptySalt()
    {
        $client = ClientBuilder::createFromEnv()->build();

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
     * @dataProvider Tarantool\Client\Tests\GreetingDataProvider::provideGreetingsWithInvalidServerName
     */
    public function testParseGreetingWithInvalidServerName($greeting)
    {
        $clientBuilder = self::createClientBuilderForFakeServer();

        (new FakeServerBuilder(new ResponseHandler($greeting)))
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();

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
     * @dataProvider Tarantool\Client\Tests\GreetingDataProvider::provideGreetingsWithInvalidSalt
     *
     * @expectedException \Tarantool\Client\Exception\Exception
     * @expectedExceptionMessage Invalid greeting: unable to parse salt.
     */
    public function testParseGreetingWithInvalidSalt($greeting)
    {
        $clientBuilder = self::createClientBuilderForFakeServer();

        (new FakeServerBuilder(new ResponseHandler($greeting)))
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();
        $client->connect();
    }

    /**
     * @expectedException \Tarantool\Client\Exception\Exception
     * @expectedExceptionMessage Unable to read greeting.
     */
    public function testReadEmptyGreeting()
    {
        $clientBuilder = self::createClientBuilderForFakeServer();

        (new FakeServerBuilder(new NoopHandler()))
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();
        $client->connect();
    }

    /**
     * @group tcp_only
     */
    public function testConnectTimeout()
    {
        $connectTimeout = 2.0;
        $clientBuilder = ClientBuilder::createFromEnv();

        // http://stackoverflow.com/a/904609/1160901
        $clientBuilder->setHost('10.255.255.1');
        $clientBuilder->setConnectionOptions(['connect_timeout' => $connectTimeout]);

        $client = $clientBuilder->build();

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

        $clientBuilder = self::createClientBuilderForFakeServer();
        $clientBuilder->setConnectionOptions(['socket_timeout' => $socketTimeout]);

        (new FakeServerBuilder(new SocketDelayHandler($socketTimeout + 2)))
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();

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
     * @group pure_only
     *
     * @expectedException \Tarantool\Client\Exception\ConnectionException
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

    public function testConnectionRetry()
    {
        $clientBuilder = self::createClientBuilderForFakeServer();
        $clientBuilder->setConnectionOptions([
            'socket_timeout' => 2,
            'retries' => 1,
        ]);
        $client = $clientBuilder->build();

        (new FakeServerBuilder(
            new ChainHandler([
                new SocketDelayHandler(3, true),
                new ResponseHandler(GreetingDataProvider::generateGreeting()),
            ])
        ))
            ->setUri($clientBuilder->getUri())
            ->start()
        ;

        $client->connect();
    }

    private static function createClientBuilderForFakeServer()
    {
        static $fakeServerPort = 8000;

        $builder = ClientBuilder::createFromEnv();

        if ($builder->isTcpConnection()) {
            $builder->setHost('0.0.0.0');
            $builder->setPort(++$fakeServerPort);
        } else {
            $builder->setUri(sprintf('unix://%s/tnt_client_%s.sock', sys_get_temp_dir(), uniqid()));
        }

        return $builder;
    }
}
