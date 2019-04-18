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

use MessagePack\BufferUnpacker;
use MessagePack\Packer;
use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Handler\DefaultHandler;
use Tarantool\Client\Packer\PeclPacker;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Tests\Integration\MessagePack\DateTimeTransformer;

require __DIR__.'/bootstrap.php';

$connection = isset($_SERVER['argv'][1])
    ? StreamConnection::create($_SERVER['argv'][1])
    : StreamConnection::createTcp();

if (class_exists(Packer::class)) {
    $transformer = new DateTimeTransformer(42);
    $msgpackPacker = (new Packer())->registerTransformer($transformer);
    $msgpackUnpacker = (new BufferUnpacker())->registerTransformer($transformer);
    $packer = new PurePacker($msgpackPacker, $msgpackUnpacker);
} else {
    $packer = new PeclPacker();
}

$client = new Client(new DefaultHandler($connection, $packer));
$spaceName = 'example';

$client->evaluate(
<<<LUA
    if box.space[...] then box.space[...]:drop() end
    space = box.schema.space.create(...)
    space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
LUA
, $spaceName);

$space = $client->getSpace($spaceName);

$date = new \DateTimeImmutable('2022-05-08 20:20:42.123194');
$result1 = $space->insert([1, $date]);
$result2 = $space->select(Criteria::key([1]));

printf("Date: %s\n", $date->format('Y-m-d\TH:i:s.uP'));
printf("Result 1: %s\n", var_export($result2, true));
printf("Result 2: %s\n", var_export($result2, true));

/* OUTPUT
Date: 2022-05-08T20:20:42.123194+00:00
Result 1: array (
  0 =>
  array (
    0 => 1,
    1 =>
    DateTimeImmutable::__set_state(array(
       'date' => '2022-05-08 20:20:42.123194',
       'timezone_type' => 1,
       'timezone' => '+00:00',
    )),
  ),
)
Result 2: array (
  0 =>
  array (
    0 => 1,
    1 =>
    DateTimeImmutable::__set_state(array(
       'date' => '2022-05-08 20:20:42.123194',
       'timezone_type' => 1,
       'timezone' => '+00:00',
    )),
  ),
)
*/
