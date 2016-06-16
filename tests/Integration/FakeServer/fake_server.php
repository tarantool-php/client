<?php

use Tarantool\Client\Tests\Integration\FakeServer\Handler\NoopHandler;

require __DIR__.'/../../../vendor/autoload.php';

$options = getopt('', ['uri:', 'ttl:', 'handler:']) + [
    'uri' => 'tcp://0.0.0.0:8000',
    'ttl' => 5,
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

while ($conn = @stream_socket_accept($socket, $options['ttl'])) {
    $handler($conn, $sid);
    fclose($conn);
    echo "$sid:   Connection closed.\n";
}

fclose($socket);
echo "$sid: Server stopped.\n";
