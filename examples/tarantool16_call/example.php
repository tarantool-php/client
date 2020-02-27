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

use App\LegacyCallMiddleware;

$loader = require __DIR__.'/../bootstrap.php';
$loader->addPsr4('App\\', __DIR__);

$client = create_client();
$clientCall16 = $client->withMiddleware(new LegacyCallMiddleware());

$result = $client->call('math.min', 5, 3, 8);
$resultCall16 = $clientCall16->call('math.min', 5, 3, 8);

printf("1.7.1+ call response: %s\n", json_encode($result));
printf("1.6 call response: %s\n", json_encode($resultCall16));

/* OUTPUT
1.7.1+ call response: [3]
1.6 call response: [[3]]
*/
