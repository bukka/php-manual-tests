<?php

use Io\Poll\{Context, Event};

function sendAndReceive($poll, $client, $message, $timeoutSeconds = 5) {
    // Send message to server
    fwrite($client, $message);
    echo "Sent: $message";
    
    // Wait for response
    $watchers = $poll->wait($timeoutSeconds);
    
    if (empty($watchers)) {
        echo "Timeout waiting for response\n";
        return false;
    }
    
    foreach ($watchers as $watcher) {
        if ($watcher->hasTriggered(Event::Read)) {
            $data = fread($client, 8192);
            if ($data === false || $data === '') {
                echo "Server closed connection\n";
                return false;
            }
            echo "Received: $data";
            return true;
        }
        
        if ($watcher->hasTriggered(Event::Error) || $watcher->hasTriggered(Event::HangUp)) {
            echo "Connection error\n";
            return false;
        }
    }
    
    return true;
}

// Create a poll context
$poll = new Context();

// Connect to the echo server
$client = stream_socket_client('tcp://127.0.0.1:8080', $errno, $errstr, 30);
if (!$client) {
    die("Failed to connect: $errstr\n");
}
stream_set_blocking($client, false);

// Create handle and watch for readable data
$handle = new StreamPollHandle($client);
$watcher = $poll->add($handle, [Event::Read]);

// Send messages to the server
$messages = ["Hello, Server!\n", "How are you?\n", "Goodbye!\n"];
foreach ($messages as $message) {
    if (!sendAndReceive($poll, $client, $message)) {
        break;
    }
    
    // Small delay between messages
    usleep(100000); // 100ms
}

fclose($client);
echo "Client finished\n";
