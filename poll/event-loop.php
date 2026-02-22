<?php

use Io\Poll\{Context, Event};

/**
 * Generic event-driven wrapper that dispatches to callbacks
 */
class EventLoop
{
    private Context $poll;
    
    public function __construct()
    {
        $this->poll = new Context();
    }
    
    /**
     * Add a stream with callback handlers
     * 
     * @param resource $stream The stream to monitor
     * @param array $events Array of Event enums to watch for
     * @param callable $callback Callback to invoke when events occur
     * @return \Io\Poll\Watcher The created watcher
     */
    public function addStream($stream, array $events, callable $callback): \Io\Poll\Watcher
    {
        $handle = new StreamPollHandle($stream);
        return $this->poll->add($handle, $events, ['callback' => $callback]);
    }
    
    /**
     * Run the event loop
     */
    public function run(): void
    {
        while (true) {
            $watchers = $this->poll->wait();
            
            foreach ($watchers as $watcher) {
                $data = $watcher->getData();
                $callback = $data['callback'];
                
                // Get stream from the handle
                $handle = $watcher->getHandle();
                if ($handle instanceof \StreamPollHandle) {
                    $stream = $handle->getStream();
                    
                    // Invoke the callback with the watcher and stream
                    $callback($watcher, $stream);
                }
            }
        }
    }
    
    public function getContext(): Context
    {
        return $this->poll;
    }
}

// Usage example - Simple echo server using callbacks
$loop = new EventLoop();

$server = stream_socket_server('tcp://127.0.0.1:9090');
stream_set_blocking($server, false);

$loop->addStream($server, [Event::Read], function($watcher, $stream) use ($loop) {
    // Accept new client
    $client = stream_socket_accept($stream, 0);
    if ($client) {
        stream_set_blocking($client, false);
        echo "Client connected\n";
        
        // Add client with its own callback
        $loop->addStream($client, [Event::Read], function($watcher, $stream) {
            $data = fread($stream, 8192);
            if ($data === false || $data === '') {
                echo "Client disconnected\n";
                $watcher->remove();
                fclose($stream);
                return;
            }
            
            echo "Received: $data";
            
            // Echo back to client
            fwrite($stream, "Echo: $data");
        });
    }
});

echo "Callback-based server listening on port 9090\n";
$loop->run();
