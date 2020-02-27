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
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Schema\Criteria;

$loader = require __DIR__.'/../bootstrap.php';
$loader->addPsr4('App\\', __DIR__);

$packer = PurePacker::fromExtensions(new EmailExtension(42));
$client = create_client($packer);
$spaceName = 'example';

$client->evaluate(
<<<LUA
    if box.space[...] then box.space[...]:drop() end
    space = box.schema.space.create(...)
    space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
LUA
, $spaceName);

$space = $client->getSpace($spaceName);

$email = new Email('foo@bar.com');
[$insertedRow] = $space->insert([3, $email]);
[$selectedRow] = $space->select(Criteria::key([3]));

printf("Result 1: %s\n", $selectedRow[1]->toString());
printf("Result 2: %s\n", json_encode([$insertedRow[1]->equals($selectedRow[1]), $selectedRow[1]->equals($email)]));

/* OUTPUT
Result 1: foo@bar.com
Result 2: [true,true]
*/
