<?php

// Test client for the callback-based event loop server
// Run this after starting the callback server example above

$client = stream_socket_client('tcp://127.0.0.1:9090', $errno, $errstr, 30);
if (!$client) {
    die("Failed to connect: $errstr\n");
}

echo "Connected to callback server\n";

// Send test messages
$messages = [
    "Test message 1\n",
    "Test message 2\n",
    "Test message 3\n"
];

foreach ($messages as $msg) {
    fwrite($client, $msg);
    echo "Sent: $msg";
    
    // Read echo response
    $response = fread($client, 8192);
    echo "Received: $response";
    
    usleep(100000); // 100ms delay
}

fclose($client);
echo "Test completed\n";
