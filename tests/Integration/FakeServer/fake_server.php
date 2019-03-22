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

/*
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Tarantool\Client\Tests\Integration\FakeServer\Handler\NoopHandler;

require __DIR__.'/../../../vendor/autoload.php';

$options = getopt('', ['uri:', 'ttl:', 'handler:']) + [
    'uri' => 'tcp://0.0.0.0:8000',
    'ttl' => 5.0,
];

if (!$socket = stream_socket_server($options['uri'], $errorCode, $errorMessage)) {
    echo "Unable to create server: $errorMessage\n";
    exit($errorCode);
}

$sid = stream_socket_get_name($socket, false);
echo "$sid: Server started.\n";

$handler = empty($options['handler'])
    ? new NoopHandler()
    : unserialize(base64_decode($options['handler']));

$isAliveChecked = false;
while ($conn = @stream_socket_accept($socket, (float) $options['ttl'])) {
    if (!$isAliveChecked) {
        $isAliveChecked = true;
        fclose($conn);
        echo "$sid:   Is-alive check received.\n";
        continue;
    }

    $handler($conn, $sid);
    fclose($conn);
    echo "$sid:   Connection closed.\n";
}

fclose($socket);
echo "$sid: Server stopped.\n";
