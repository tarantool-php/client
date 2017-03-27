<?php

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Exception\Exception;
use Tarantool\Client\Tests\Assert;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    use Assert;
    use Client;

    /**
     * @dataProvider provideCallData
     */
    public function testCall($func, array $args, $return)
    {
        $response = self::$client->call($func, $args);

        $this->assertResponse($response);
        $this->assertSame($return, $response->getData()[0]);
    }

    public function provideCallData()
    {
        yield [
            'func' => 'func_foo',
            'args' => [],
            'ret' => ['foo' => 'foo', 'bar' => 42],
        ];

        yield [
            'func' => 'func_sum',
            'args' => [42, -24],
            'ret' => 18,
        ];

        yield [
            'func' => 'func_arg',
            'args' => [ [42] ],
            'ret' => [42],
        ];

        yield [
            'func' => 'func_arg',
            'args' => [ [[42]] ],
            'ret' => [[42]],
        ];

        yield [
            'func' => 'func_arg',
            'args' => [ null ],
            'ret' => null,
        ];
    }

    /**
     * @dataProvider provideEvaluateData
     */
    public function testEvaluate(array $args, $result)
    {
        $response = 1 !== count($args)
            ? call_user_func_array([self::$client, 'evaluate'], $args)
            : self::$client->evaluate($args[0]);

        $this->assertResponse($response);
        $this->assertSame($result, $response->getData());
    }

    public function provideEvaluateData()
    {
        return [
            [['return func_foo()'], [['foo' => 'foo', 'bar' => 42]]],
            [['return func_sum(...)', [42, -24]], [18]],
            [['return func_arg(...)', [ [[42]] ]], [ [[42]] ]],
            [['return func_arg(...)', [ [42] ]], [ [42] ]],
        ];
    }

    public function testCacheSpace()
    {
        $total = Utils::getTotalSelectCalls();

        self::$client->flushSpaces();
        self::$client->getSpace('space_conn')->select();
        self::$client->getSpace('space_conn')->select();

        $this->assertSame(3, Utils::getTotalSelectCalls() - $total);
    }

    public function testFlushSpaces()
    {
        $total = Utils::getTotalSelectCalls();

        self::$client->flushSpaces();
        self::$client->getSpace('space_conn')->select();
        self::$client->flushSpaces();
        self::$client->getSpace('space_conn')->select();

        $this->assertSame(4, Utils::getTotalSelectCalls() - $total);
    }

    public function testSpacesAreFlushedAfterSuccessfulAuthentication()
    {
        $client = ClientBuilder::createFromEnv()->build();

        $client->getSpace('space_conn')->select();
        $client->authenticate('user_foo', 'foo');

        try {
            $client->getSpace('space_conn')->select();
        } catch (Exception $e) {
            // this error means that the client tried to select 'space_conn'
            // from '_vspace' to get the space id instead of getting it directly
            // from the cache (otherwise it will be 'Read access denied' error)
            $this->assertSame("Space 'space_conn' does not exist", $e->getMessage());

            return;
        }

        $this->fail();
    }

    /**
     * @group pure_only
     *
     * Pecl extension sends auth request and rereads schema in batches on authenticate:
     * https://github.com/tarantool/tarantool-php/blob/3e20c8cea4224cb441eb50624c406cbd40368318/src/tarantool.c#L732
     */
    public function testSpacesAreNotFlushedAfterFailedAuthentication()
    {
        $client = ClientBuilder::createFromEnv()->build();

        $client->getSpace('space_conn')->select();
        $total = Utils::getTotalSelectCalls();

        try {
            $client->authenticate('user_foo', 'incorrect_password');
        } catch (Exception $e) {
            $this->assertSame("Incorrect password supplied for user 'user_foo'", $e->getMessage());
        }

        $client->getSpace('space_conn')->select();
        $this->assertSame(1, Utils::getTotalSelectCalls() - $total);
    }

    /**
     * @expectedException \Tarantool\Client\Exception\Exception
     * @expectedExceptionMessage Foobar.
     * @expectedExceptionCode 42
     */
    public function testException()
    {
        self::$client->evaluate('box.error{code = 42, reason = "Foobar."}');
    }
}
