<?php

$options = getopt('', ['response:', 'host:', 'port:', 'ttl:', 'socket_delay:']) + [
    'response' => null,
    'host' => '0.0.0.0',
    'port' => '8000',
    'ttl' => 5,
    'socket_delay' => null,
];

if (!$socket = stream_socket_server($options['host'].':'.$options['port'], $errorCode, $errorMessage)) {
    echo "Unable to create server: $errorMessage\n";
    exit($errorCode);
}

$sid = stream_socket_get_name($socket, false);
echo "$sid: Server started.\n";

while ($conn = @stream_socket_accept($socket, $options['ttl'])) {
    if ($options['socket_delay']) {
        printf("$sid:   Sleep %d sec.\n", $options['socket_delay']);
        sleep($options['socket_delay']);
    }
    if ($options['response']) {
        printf("$sid:   Write response (base64): %s.\n", $options['response']);
        fwrite($conn, base64_decode($options['response']));
    }
    fclose($conn);
    echo "$sid:   Connection closed.\n";
}

fclose($socket);
echo "$sid: Server stopped.\n";
