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

require __DIR__.'/../bootstrap.php';
require __DIR__.'/Email.php';
require __DIR__.'/EmailTransformer.php';

$connection = isset($_SERVER['argv'][1])
    ? StreamConnection::create($_SERVER['argv'][1])
    : StreamConnection::createTcp();

if (extension_loaded('msgpack')) {
    $packer = new PeclPacker();
} else {
    $transformer = new EmailTransformer(42);
    $packer = new PurePacker(
        (new Packer())->registerTransformer($transformer),
        (new BufferUnpacker())->registerTransformer($transformer)
    );
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

$result1 = $space->insert([3, new Email('foo@bar.com')]);
$result2 = $space->select(Criteria::key([3]));

printf("Result 1: %s\n", var_export($result2, true));
printf("Result 2: %s\n", var_export($result2, true));

/* OUTPUT
Result 1: array (
  0 =>
  array (
    0 => 1,
    1 =>
    Email::__set_state(array(
       'value' => 'foo@bar.com',
    )),
  ),
)
Result 2: array (
  0 =>
  array (
    0 => 1,
    1 =>
    Email::__set_state(array(
       'value' => 'foo@bar.com',
    )),
  ),
)
*/
