<?php
/**
 * Minimal SSL Server with Client Certificate Capture
 * 
 * This demonstrates the exact issue from your original question.
 * Run with: php server.php
 */

$port = 8443;

echo "Starting SSL server on port $port...\n";

// Check if CA file exists first
if (!file_exists('./certs/ca-cert.pem')) {
    die("❌ CA certificate not found: ./certs/ca-cert.pem\n");
}

$context = stream_context_create([
    'ssl' => [
        'allow_self_signed' => true,
        'SNI_enabled'       => true,
        'SNI_server_certs'  => ['localhost' => './certs/server-combined.pem'],
        'capture_peer_cert' => true,
        'verify_peer'       => true,
        'verify_peer_name'  => false,
        'cafile'           => './certs/ca-cert.pem',  // CA to verify client certs
        'verify_depth'      => 3,
    ]
]);

$socket = stream_socket_server(
    'tcp://[::]:' . $port,
    $errno,
    $errstr,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
    $context,
);

if ($socket === false) {
    throw new \Exception($errstr, $errno);
} else {
    echo "Server listening on tcp://[::]:$port\n";
    echo "Waiting for connections...\n\n";
    
    while ($conn = stream_socket_accept($socket, -1, $peername)) {
        echo "Connection from: $peername\n";
        
        $tlsSuccess = stream_socket_enable_crypto(
            $conn,
            true,
            STREAM_CRYPTO_METHOD_TLS_SERVER
        );
        
        if ($tlsSuccess !== true) {
            echo "TLS handshake failed\n";
            fclose($conn);
            continue;
        }
        
        echo "TLS handshake successful\n";
        
        $options = stream_context_get_options($conn);
        
        echo "Context options:\n";
        var_dump($options);
        
        if (isset($options['ssl']['peer_certificate'])) {
            echo "\n✅ CLIENT CERTIFICATE RECEIVED!\n";
            $cert = $options['ssl']['peer_certificate'];
            $certData = openssl_x509_parse($cert);
            echo "Subject: " . json_encode($certData['subject']) . "\n";
        } else {
            echo "\n❌ No client certificate received\n";
        }
        
        echo str_repeat("-", 50) . "\n\n";
        
        fclose($conn);
    }
}
