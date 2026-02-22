<?php

use Io\Poll\{Context, Event};

// Create a poll context with automatic backend selection
$poll = new Context();

// Create a server socket
$server = stream_socket_server('tcp://0.0.0.0:8080', $errno, $errstr);
if (!$server) {
    die("Failed to create server: $errstr\n");
}
stream_set_blocking($server, false);

// Wrap the stream in a PollHandle
$serverHandle = new StreamPollHandle($server);

// Add the server socket to the poll context
$poll->add($serverHandle, [Event::Read], ['type' => 'server']);

echo "Server listening on port 8080\n";

while (true) {
    // Returns array of Watcher instances that have events
    $watchers = $poll->wait(1);
    
    foreach ($watchers as $watcher) {
        $data = $watcher->getData();
        
        if ($data['type'] === 'server' && $watcher->hasTriggered(Event::Read)) {
            // Accept new client connection
            $handle = $watcher->getHandle();
            if ($handle instanceof StreamPollHandle) {
                $server = $handle->getStream();
                $client = stream_socket_accept($server, 0);
                if ($client) {
                    stream_set_blocking($client, false);
                    $clientHandle = new StreamPollHandle($client);
                    $poll->add($clientHandle, [Event::Read], ['type' => 'client']);
                    echo "New client connected\n";
                }
            }
        } elseif ($data['type'] === 'client') {
            $handle = $watcher->getHandle();
            if ($handle instanceof StreamPollHandle) {
                $stream = $handle->getStream();
                
                if ($watcher->hasTriggered(Event::Read)) {
                    // Read data from client
                    $buffer = fread($stream, 8192);
                    if ($buffer === false || $buffer === '') {
                        echo "Client disconnected\n";
                        $watcher->remove();
                        fclose($stream);
                    } else {
                        echo "Received: $buffer";
                        // Echo back to client
                        fwrite($stream, "Echo: $buffer");
                    }
                }
                
                if ($watcher->hasTriggered(Event::HangUp) || $watcher->hasTriggered(Event::Error)) {
                    echo "Client connection error or hangup\n";
                    $watcher->remove();
                    fclose($stream);
                }
            }
        }
    }
}

