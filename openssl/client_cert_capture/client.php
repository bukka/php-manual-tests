<?php
/**
 * Minimal SSL Client - Tests with and without certificates
 * 
 * Run with: php client.php
 */

$host = 'localhost';
$port = 8443;

echo "Testing SSL client connections...\n\n";

// Test 1: Without client certificate
echo "=== Test 1: NO client certificate ===\n";
testConnection($host, $port, false);

echo "\n";

// Test 2: With client certificate  
echo "=== Test 2: WITH client certificate ===\n";
testConnection($host, $port, true);

function testConnection($host, $port, $useClientCert) {
    $contextOptions = [
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ]
    ];
    
    if ($useClientCert) {
        $contextOptions['ssl']['local_cert'] = './certs/client-combined.pem';
        echo "Using client certificate: ./certs/client-combined.pem\n";
    } else {
        echo "No client certificate\n";
    }
    
    $context = stream_context_create($contextOptions);
    
    $client = stream_socket_client(
        "tcp://$host:$port",
        $errno,
        $errstr,
        10,
        STREAM_CLIENT_CONNECT,
        $context
    );
    
    if ($client === false) {
        echo "Connection failed: $errstr ($errno)\n";
        return;
    }
    
    echo "TCP connection established\n";
    
    // Enable TLS on client side
    $tlsSuccess = stream_socket_enable_crypto(
        $client,
        true,
        STREAM_CRYPTO_METHOD_TLS_CLIENT
    );
    
    if ($tlsSuccess !== true) {
        echo "TLS handshake failed\n";
        fclose($client);
        return;
    }
    
    echo "TLS handshake successful\n";
    echo "Connection complete - check server output\n";
    
    fclose($client);
}
