<?php
/**
 * TLS Session Resumption Debug Server
 * 
 * Run this first, then run the client.
 * Usage: php debug_server.php
 */

$certFile = __DIR__ . '/debug_cert.pem';

// Generate certificate if it doesn't exist
if (!file_exists($certFile)) {
    echo "Generating certificate...\n";
    $dn = [
        "countryName" => "US",
        "stateOrProvinceName" => "Test",
        "localityName" => "Test",
        "organizationName" => "Test",
        "commonName" => "localhost",
    ];
    $privkey = openssl_pkey_new([
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ]);
    $csr = openssl_csr_new($dn, $privkey);
    $cert = openssl_csr_sign($csr, null, $privkey, 365);
    
    $certOut = '';
    openssl_x509_export($cert, $certOut);
    $pkeyOut = '';
    openssl_pkey_export($privkey, $pkeyOut);
    
    file_put_contents($certFile, $certOut . $pkeyOut);
    echo "Certificate saved to: $certFile\n";
}

$port = 44330;
$timeout = 300; // 5 minutes

$flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
$ctx = stream_context_create(['ssl' => [
    'local_cert' => $certFile,
    'session_id_context' => 'test-php',
    'session_cache' => true,
    'session_cache_size' => 100,
    'session_timeout' => 300,
    'no_ticket' => true,
    // Uncomment to test with tickets disabled (session ID only):
    // 'no_ticket' => true,
]]);

echo "Starting TLS server on port $port...\n";
echo "Session cache: enabled\n";
echo "Timeout: {$timeout}s\n";
echo "\n";

$server = stream_socket_server("tls://127.0.0.1:$port", $errno, $errstr, $flags, $ctx);

if (!$server) {
    die("Failed to create server: $errstr ($errno)\n");
}

echo "Server listening on tls://127.0.0.1:$port\n";
echo "Waiting for connections (will accept 2)...\n";
echo "\n";

for ($i = 1; $i <= 2; $i++) {
    echo "--- Waiting for connection $i ---\n";
    
    $client = @stream_socket_accept($server, $timeout);
    
    if ($client) {
        $meta = stream_get_meta_data($client);
        $crypto = $meta['crypto'] ?? [];
        
        echo "Connection $i accepted!\n";
        echo "  Protocol: " . ($crypto['protocol'] ?? 'N/A') . "\n";
        echo "  Cipher: " . ($crypto['cipher_name'] ?? 'N/A') . "\n";
        echo "  Session reused: " . (($crypto['session_reused'] ?? false) ? "YES" : "NO") . "\n";
        
        fwrite($client, "Hello from server (connection $i)\n");
        fclose($client);
        
        echo "Connection $i closed.\n\n";
    } else {
        echo "Accept failed or timed out.\n";
    }
}

fclose($server);
echo "Server shutdown.\n";
