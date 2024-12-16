<?php

$res = openssl_pkey_new();
openssl_pkey_export($res, $privkey);
$pubkey = openssl_pkey_get_details($res)['key'] ?? null;

// Test String
$data = 'non-encrypted data';

// Encrypt Test String
openssl_public_encrypt($data, $encrypted_data, $pubkey, OPENSSL_PKCS1_PADDING);
// Decrypt Encrypted Test String
openssl_private_decrypt($encrypted_data, $decrypted_data, $privkey, OPENSSL_PKCS1_PADDING);

echo "Starting Data: $data".PHP_EOL;
echo "Decrypted Data: $decrypted_data".PHP_EOL;

// Decrypt Unencrypted Test String
if (!openssl_private_decrypt($data, $decrypted_unencrypted_data, $privkey, OPENSSL_PKCS1_PADDING)){
	echo "Could not decrypt invalid data".PHP_EOL;
}
else {
	echo "Decrypted Unencrypted Data: $decrypted_unencrypted_data" . PHP_EOL;
}
