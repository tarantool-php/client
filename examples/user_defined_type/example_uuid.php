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

use Symfony\Component\Uid\Uuid;
use Tarantool\Client\Schema\Criteria;

require __DIR__.'/../bootstrap.php';

$client = create_client();
ensure_server_version_at_least('2.4', $client);
ensure_class(Uuid::class, 'Composer package "symfony/uid" is required');
ensure_pure_packer($client);

$spaceName = 'example';

$client->evaluate(
<<<LUA
    if box.space[...] then box.space[...]:drop() end
    local space = box.schema.space.create(...)
    space:create_index("primary", {parts = {1, 'uuid'}})
    local uuid = require('uuid')
    space:insert({uuid.fromstr('64d22e4d-ac92-4a23-899a-e59f34af5479'), 'foo'})
LUA
, $spaceName);

$space = $client->getSpace($spaceName);

$result1 = $space->insert([new Uuid('7e3b84a4-0819-473a-9625-5d57ad1c9604'), 'bar']);
$result2 = $space->select(Criteria::geIterator());

function print_result(string $title, array $rows) : void
{
    echo "$title:\n";
    foreach ($rows as $row) {
        printf("[%s('%s'), '%s']\n", get_class($row[0]), $row[0], $row[1]);
    }
}

print_result('Result 1', $result1);
print_result('Result 2', $result2);

/* OUTPUT
Result 1:
[Symfony\Component\Uid\UuidV4('7e3b84a4-0819-473a-9625-5d57ad1c9604'), 'bar']
Result 2:
[Symfony\Component\Uid\UuidV4('64d22e4d-ac92-4a23-899a-e59f34af5479'), 'foo']
[Symfony\Component\Uid\UuidV4('7e3b84a4-0819-473a-9625-5d57ad1c9604'), 'bar']
*/
