<?php

/**
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use App\Email;
use App\EmailExtension;
use MessagePack\BufferUnpacker;
use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Handler\DefaultHandler;
use Tarantool\Client\Packer\PeclPacker;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Schema\Criteria;

$loader = require __DIR__.'/../../vendor/autoload.php';
$loader->addPsr4('App\\', __DIR__);

$connection = isset($_SERVER['argv'][1])
    ? StreamConnection::create($_SERVER['argv'][1])
    : StreamConnection::createTcp();

$packer = class_exists(BufferUnpacker::class)
    ? PurePacker::fromExtensions(new EmailExtension(42))
    : new PeclPacker();

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

printf("Result 1: %s\n", var_export($result1, true));
printf("Result 2: %s\n", var_export($result2, true));

/* OUTPUT
Result 1: array (
  0 =>
  array (
    0 => 3,
    1 =>
    App\Email::__set_state(array(
       'value' => 'foo@bar.com',
    )),
  ),
)
Result 2: array (
  0 =>
  array (
    0 => 3,
    1 =>
    App\Email::__set_state(array(
       'value' => 'foo@bar.com',
    )),
  ),
)
*/
