<?php

$options = getopt('', ['uri:', 'response:', 'ttl:', 'socket_delay:']) + [
    'uri' => 'tcp://0.0.0.0:8000',
    'response' => null,
    'ttl' => 5,
    'socket_delay' => null,
];

if (!$socket = stream_socket_server($options['uri'], $errorCode, $errorMessage)) {
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
