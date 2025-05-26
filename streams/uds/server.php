<?php

$socketPath = '/tmp/test_socket.sock';

// Make sure old socket doesn't exist
if (file_exists($socketPath)) {
    unlink($socketPath);
}

$server = stream_socket_server("unix://$socketPath", $errno, $errstr);
if (!$server) {
    die("Server error: $errstr ($errno)\n");
}

echo "Server listening on $socketPath\n";

while ($conn = @stream_socket_accept($server, -1)) {
    $request = fread($conn, 1024);
    echo "Received: $request\n";

    $response = "Hello from server\n";
    fwrite($conn, $response);
    fclose($conn);
}

fclose($server);
unlink($socketPath);

