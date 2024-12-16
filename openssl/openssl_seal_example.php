<?php
// $data is assumed to contain the data to be sealed
$data = "test";

// fetch public keys
$pk1 = openssl_get_publickey("file://cert.crt");
$pk2 = openssl_get_publickey("file://public.key");

$pk_priv = openssl_get_privatekey("file://private_rsa_1024.key");

// seal message, only owners of $pk1 and $pk2 can decrypt $sealed with keys
// $ekeys[0] and $ekeys[1] respectively.
if (openssl_seal($data, $sealed, $ekeys, array($pk1, $pk2), 'AES256', $iv) > 0) {
    // possibly store the $sealed and $iv values and use later in openssl_open
    echo "success\n";
    if (openssl_open($sealed, $open_data, $ekeys[1], $pk_priv, 'AES256', $iv)) {
        echo "$open_data\n";
    }
}