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

require __DIR__.'/../bootstrap.php';

$client = create_client();
$client->evaluate('function func_42() return 42 end');

$result1 = $client->call('func_42');
$result2 = $client->call('math.min', 5, 3, 8);

printf("Result 1: %s\n", json_encode($result1));
printf("Result 2: %s\n", json_encode($result2));

/* OUTPUT
Result 1: [42]
Result 2: [3]
*/
