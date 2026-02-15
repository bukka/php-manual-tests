#!/usr/bin/env php
<?php
/**
 * Standalone TLS Session Resumption Server
 * 
 * Usage: php server.php [port]
 * 
 * This server demonstrates external session cache with callbacks.
 * Run with gdb: gdb --args php server.php 8443
 */

$port = $argv[1] ?? 8443;
$certFile = __DIR__ . '/server_cert.pem';

// Generate a self-signed certificate if it doesn't exist
if (!file_exists($certFile)) {
    echo "Generating self-signed certificate...\n";
    
    $dn = [
        "countryName" => "US",
        "stateOrProvinceName" => "California",
        "localityName" => "San Francisco",
        "organizationName" => "Test Server",
        "organizationalUnitName" => "Development",
        "commonName" => "localhost",
        "emailAddress" => "test@example.com"
    ];
    
    $privkey = openssl_pkey_new([
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ]);
    
    $cert = openssl_csr_new($dn, $privkey, ['digest_alg' => 'sha256']);
    $cert = openssl_csr_sign($cert, null, $privkey, 365, ['digest_alg' => 'sha256']);
    
    $pem = '';
    openssl_x509_export($cert, $pem);
    openssl_pkey_export($privkey, $pemKey);
    
    file_put_contents($certFile, $pem . $pemKey);
    echo "Certificate saved to: $certFile\n";
}

// Session storage - simulating Redis/Memcached
$sessionStore = [];
$stats = [
    'new_cb_count' => 0,
    'get_cb_count' => 0,
    'remove_cb_count' => 0,
    'connections' => 0,
];

echo "=== TLS Session Resumption Server ===\n";
echo "Listening on: 127.0.0.1:$port\n";
echo "Certificate: $certFile\n";
echo "Press Ctrl+C to stop\n\n";

$flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
$ctx = stream_context_create(['ssl' => [
    'local_cert' => $certFile,
    //'verify_peer' => false,
    //'no_ticket' => true,
    'session_id_context' => 'data',
    
    'session_new_cb' => function($stream, $session) use (&$sessionStore, &$stats) {
        $key = bin2hex($session->id);
        $sessionStore[$key] = $session;
        $stats['new_cb_count']++;
        
        echo "[SESSION_NEW] Session ID: $key (length: " . strlen($sessionData) . " bytes)\n";
        echo "              Total sessions in cache: " . count($sessionStore) . "\n";
    },
    
    'session_get_cb' => function($stream, $sessionId) use (&$sessionStore, &$stats) {
        $key = bin2hex($sessionId);
        $stats['get_cb_count']++;
        
        $found = isset($sessionStore[$key]);
        echo "[SESSION_GET] Session ID: $key - " . ($found ? "FOUND" : "NOT FOUND") . "\n";
        
        return $sessionStore[$key] ?? null;
    },
    
    'session_remove_cb' => function($stream, $sessionId) use (&$sessionStore, &$stats) {
        $key = bin2hex($sessionId);
        $stats['remove_cb_count']++;
        
        echo "[SESSION_REMOVE] Session ID: $key\n";
        unset($sessionStore[$key]);
    }
]]);

$server = stream_socket_server("tls://127.0.0.1:$port", $errno, $errstr, $flags, $ctx);

if (!$server) {
    die("Failed to create server: $errstr ($errno)\n");
}

echo "Server started successfully!\n";
echo "Waiting for connections...\n\n";

// Accept connections in a loop
while (true) {
    $client = stream_socket_accept($server, -1, $peerName);
    
    if (!$client) {
        continue;
    }
    
    $request = fread($client, 2000);
    echo $request;

    $stats['connections']++;
    echo "\n=== Connection #" . $stats['connections'] . " from $peerName ===\n";
    
    // Send response
    $response = "HTTP/1.1 200 OK\r\n";
    $response .= "Content-Type: text/plain\r\n";
    $response .= "Connection: close\r\n";
    $response .= "\r\n";
    $response .= "Hello from TLS server!\n";
    $response .= "Connection: " . $stats['connections'] . "\n";
    $response .= "Session callbacks:\n";
    $response .= "  - new_cb: " . $stats['new_cb_count'] . "\n";
    $response .= "  - get_cb: " . $stats['get_cb_count'] . "\n";
    $response .= "  - remove_cb: " . $stats['remove_cb_count'] . "\n";
    
    fwrite($client, $response);
    fclose($client);
    
    echo "Response sent and connection closed\n";
    
    // Print current stats
    echo "\n[STATS] Connections: " . $stats['connections'] . 
         ", NEW callbacks: " . $stats['new_cb_count'] . 
         ", GET callbacks: " . $stats['get_cb_count'] . 
         ", REMOVE callbacks: " . $stats['remove_cb_count'] . "\n";
    echo "        Sessions in cache: " . count($sessionStore) . "\n";
}
