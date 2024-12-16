<?php

$cert_store = file_get_contents(__DIR__ . "/keystore-rc2.p12");

// Define paths to the private key and certificate files
$privateKeyPath = __DIR__ . '/myKey.pem';
$certificatePath = __DIR__ . '/cert.pem';
$p12Path = __DIR__ . '/result.p12';

// Load the private key and certificate
$privateKey = file_get_contents($privateKeyPath);
$certificate = file_get_contents($certificatePath);

// Set the passphrase for the PKCS#12 file (optional)
$password = 'secret';

// Create the PKCS#12 file
$pkcs12 = null;
$exported = openssl_pkcs12_export($certificate, $pkcs12, $privateKey, $password);

if ($exported) {
    // Write the PKCS#12 data to a file
    file_put_contents($p12Path, $pkcs12);
    echo "PKCS#12 file has been created successfully.";
} else {
    echo "Failed to create the PKCS#12 file.";
}
