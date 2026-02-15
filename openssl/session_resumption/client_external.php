#!/usr/bin/env php
<?php
/**
 * Standalone TLS Session Resumption Client
 * 
 * Usage: php client.php [host] [port] [connections]
 * 
 * Examples:
 *   php client.php                    # Connect to localhost:8443, 2 connections
 *   php client.php localhost 9000     # Connect to localhost:9000, 2 connections
 *   php client.php localhost 8443 5   # Make 5 connections
 * 
 * This client demonstrates session resumption by:
 * 1. Making a first connection and saving the session
 * 2. Making subsequent connections reusing the saved session
 */

$host = $argv[1] ?? 'localhost';
$port = $argv[2] ?? 8443;
$numConnections = (int)($argv[3] ?? 2);

$sessionData = null;
$sessionId = null;

echo "=== TLS Session Resumption Client ===\n";
echo "Target: $host:$port\n";
echo "Connections: $numConnections\n\n";

for ($i = 1; $i <= $numConnections; $i++) {
    echo "=== Connection #$i ===\n";
    
    $flags = STREAM_CLIENT_CONNECT;
    $ctxOptions = [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ];
    
    // First connection: set up callback to capture session
    if ($i === 1) {
        $ctxOptions['session_new_cb'] = function($stream, $sid, $sdata) use (&$sessionData, &$sessionId) {
            $sessionId = $sid;
            $sessionData = $sdata;
            echo "[CLIENT] Session callback invoked!\n";
            echo "         Session ID: " . bin2hex($sid) . "\n";
            echo "         Session data length: " . strlen($sdata) . " bytes\n";
        };
        echo "Type: FULL HANDSHAKE (no session data)\n";
    } else {
        // Subsequent connections: reuse session
        if ($sessionData !== null) {
            $ctxOptions['session_data'] = $sessionData;
            echo "Type: RESUMED SESSION\n";
            echo "      Using session ID: " . bin2hex($sessionId) . "\n";
        } else {
            echo "Type: FULL HANDSHAKE (no session data available)\n";
        }
    }
    
    $ctx = stream_context_create(['ssl' => $ctxOptions]);
    
    $startTime = microtime(true);
    $client = @stream_socket_client(
        "tls://$host:$port",
        $errno,
        $errstr,
        30,
        $flags,
        $ctx
    );
    $connectTime = microtime(true) - $startTime;
    
    if (!$client) {
        echo "ERROR: Failed to connect: $errstr ($errno)\n\n";
        continue;
    }
    
    echo "Connected in " . round($connectTime * 1000, 2) . " ms\n";
    
    // Send HTTP request
    $request = "GET / HTTP/1.1\r\n";
    $request .= "Host: $host\r\n";
    $request .= "Connection: close\r\n";
    $request .= "\r\n";
    
    fwrite($client, $request);
    
    // Read response
    $response = '';
    while (!feof($client)) {
        $response .= fread($client, 8192);
    }
    
    fclose($client);
    
    // Parse and display response
    list($headers, $body) = explode("\r\n\r\n", $response, 2);
    echo "\nServer response:\n";
    echo "---\n";
    echo trim($body) . "\n";
    echo "---\n\n";
    
    // Wait a bit between connections
    if ($i < $numConnections) {
        usleep(100000); // 100ms
    }
}

echo "\n=== Summary ===\n";
echo "Total connections: $numConnections\n";
echo "Session data captured: " . ($sessionData !== null ? "YES" : "NO") . "\n";
if ($sessionData !== null) {
    echo "Session ID: " . bin2hex($sessionId) . "\n";
    echo "Session data size: " . strlen($sessionData) . " bytes\n";
    echo "First connection: Full handshake\n";
    echo "Subsequent connections: Session resumption\n";
}
echo "\nNote: Compare connection times - resumed sessions should be faster!\n";
