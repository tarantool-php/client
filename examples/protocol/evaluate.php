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

require __DIR__.'/../bootstrap.php';

$client = create_client();

$result1 = $client->evaluate('function func_42() return 42 end');
$result2 = $client->evaluate('return func_42()');
$result3 = $client->evaluate('return math.min(...)', 5, 3, 8);

printf("Result 1: %s\n", json_encode($result1));
printf("Result 2: %s\n", json_encode($result2));
printf("Result 3: %s\n", json_encode($result3));

/* OUTPUT
Result 1: []
Result 2: [42]
Result 3: [3]
*/
