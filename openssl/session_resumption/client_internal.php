<?php
/**
 * TLS Session Resumption Debug Client
 * 
 * Run the server first, then run this.
 * Usage: php debug_client.php
 */

$port = 44330;
$timeout = 300; // 5 minutes
$addr = "tls://127.0.0.1:$port";

$sessionData = '';
$sessionId = '';

$flags = STREAM_CLIENT_CONNECT;

echo "=== First Connection (Full Handshake) ===\n";
echo "Connecting to $addr...\n";

$ctx1 = stream_context_create(['ssl' => [
    'verify_peer' => false,
    'verify_peer_name' => false,
    'session_new_cb' => function($stream, $sid, $data) use (&$sessionData, &$sessionId) {
        $sessionId = bin2hex($sid);
        $sessionData = $data;
        echo "  [Callback] New session received!\n";
        echo "  [Callback] Session ID: $sessionId\n";
        echo "  [Callback] Session data length: " . strlen($data) . " bytes\n";
    }
]]);

$client1 = @stream_socket_client($addr, $errno, $errstr, $timeout, $flags, $ctx1);

if ($client1) {
    $meta1 = stream_get_meta_data($client1);
    $crypto1 = $meta1['crypto'] ?? [];
    
    echo "Connected!\n";
    echo "  Protocol: " . ($crypto1['protocol'] ?? 'N/A') . "\n";
    echo "  Cipher: " . ($crypto1['cipher_name'] ?? 'N/A') . "\n";
    echo "  Session reused: " . (($crypto1['session_reused'] ?? false) ? "YES" : "NO") . "\n";
    
    $response = trim(fgets($client1));
    echo "  Server response: $response\n";
    
    fclose($client1);
    echo "Connection 1 closed.\n";
} else {
    die("Connection 1 failed: $errstr ($errno)\n");
}

echo "\n";
echo "Session data captured: " . (strlen($sessionData) > 0 ? "YES (" . strlen($sessionData) . " bytes)" : "NO") . "\n";

if (strlen($sessionData) === 0) {
    die("No session data received, cannot test resumption.\n");
}

echo "\n";
echo "=== Second Connection (Resumption Attempt) ===\n";
echo "Connecting to $addr with saved session...\n";

$ctx2 = stream_context_create(['ssl' => [
    'verify_peer' => false,
    'verify_peer_name' => false,
    'session_data' => $sessionData,
]]);

$client2 = stream_socket_client($addr, $errno, $errstr, $timeout, $flags, $ctx2);

if ($client2) {
    $meta2 = stream_get_meta_data($client2);
    $crypto2 = $meta2['crypto'] ?? [];
    
    echo "Connected!\n";
    echo "  Protocol: " . ($crypto2['protocol'] ?? 'N/A') . "\n";
    echo "  Cipher: " . ($crypto2['cipher_name'] ?? 'N/A') . "\n";
    echo "  Session reused: " . (($crypto2['session_reused'] ?? false) ? "YES" : "NO") . "\n";
    
    $response = trim(fgets($client2));
    echo "  Server response: $response\n";
    
    fclose($client2);
    echo "Connection 2 closed.\n";
} else {
    echo "Connection 2 failed: $errstr ($errno)\n";
    echo "\n";
    echo "This is the failure we're debugging!\n";
    echo "The 'tlsv1 alert internal error' means the server rejected our session ticket.\n";
}

echo "\n";
echo "=== Summary ===\n";
echo "If second connection shows 'Session reused: YES', resumption works.\n";
echo "If second connection fails, there's an issue with SSL_CTX sharing.\n";
