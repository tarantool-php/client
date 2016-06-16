<?php

namespace Tarantool\Client\Tests\Integration;

class MsgpackTest extends \PHPUnit_Framework_TestCase
{
    use Client;

    /**
     * @dataProvider providerPackUnpackData
     */
    public function testPackUnpack($data)
    {
        $response = self::$client->evaluate('return func_arg(...)', [$data]);

        $this->assertSame($data, $response->getData()[0]);
    }

    public function providerPackUnpackData()
    {
        return [
            [42],
            [-42],
            [4.2],
            [-4.2],
            [null],
            [false],
            ['string'],
            ["\x04\x00\xa0\x00\x00"],
            [[1, 2]],
            [[[[1, 2]]]],
            [['foo' => 'bar']],
        ];
    }

    public function testPackUnpackMultiDimensionalArray()
    {
        $array = [
            true,
            [
                's' => [1, 1428578535],
                'u' => 1428578535,
                'v' => [],
                'c' => [
                    2 => [1, 1428578535],
                    106 => [1, 1428578535],
                ],
                'pc' => [
                    2 => [1, 1428578535, 9243],
                    106 => [1, 1428578535, 9243],
                ],
            ],
            true,
        ];

        $response = self::$client->evaluate('return func_arg(...)', [$array]);

        $this->assertEquals($array, $response->getData()[0], '', 0.0, 5, true);
    }

    /**
     * @group pure_only
     */
    public function testPackUnpackObject()
    {
        $packer = getenv('TNT_PACKER');

        if (ClientBuilder::PACKER_PECL !== $packer) {
            $this->markTestSkipped(sprintf('Packing/unpacking objects is not supported by "%s" packer.', $packer));
        }

        $obj = (object) ['foo' => 'bar'];
        $response = self::$client->evaluate('return func_arg(...)', [$obj]);

        $this->assertEquals($obj, $response->getData()[0]);
    }
}
