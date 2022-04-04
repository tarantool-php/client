<?php

/**
 * This file is part of the tarantool/client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Decimal\Decimal;
use Tarantool\Client\Schema\Criteria;

require __DIR__.'/../bootstrap.php';

$client = create_client();
ensure_server_version_at_least('2.3', $client);
ensure_extension('decimal');

$spaceName = 'example';

$client->evaluate(
<<<LUA
    if box.space[...] then box.space[...]:drop() end
    local space = box.schema.space.create(...)
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
