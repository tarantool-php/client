<?php

/**
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @requires Tarantool 2.3
 * @requires extension decimal
 * @requires function MessagePack\Packer::pack
 */

declare(strict_types=1);

use Decimal\Decimal;
use Tarantool\Client\Schema\Criteria;

require __DIR__.'/../../vendor/autoload.php';

$client = create_client();
$spaceName = 'example';

$client->evaluate(
<<<LUA
    if box.space[...] then box.space[...]:drop() end
    space = box.schema.space.create(...)
    space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
LUA
, $spaceName);

$space = $client->getSpace($spaceName);

$result1 = $space->insert([3, new Decimal('1.000000099')]);
$result2 = $space->select(Criteria::key([3]));

printf("Result 1: %s\n", $result1[0][1]->toString());
printf("Result 2: %s\n", $result2[0][1]->toString());

/* OUTPUT
Result 1: 1.000000099
Result 2: 1.000000099
*/
