<?php

function arrayRecursiveDiff($aArray1, $aArray2) { 
    $aReturn = array(); 
   
    foreach ($aArray1 as $mKey => $mValue) { 
        if (array_key_exists($mKey, $aArray2)) { 
            if (is_array($mValue)) { 
                $aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray2[$mKey]); 
                if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; } 
            } else { 
                if ($mValue != $aArray2[$mKey]) { 
                    $aReturn[$mKey] = $mValue; 
                } 
            } 
        } else { 
            $aReturn[$mKey] = $mValue; 
        } 
    } 

    return $aReturn; 
}


$plain1   = tempnam(__DIR__, 'openssl_test');
$plain2   = tempnam(__DIR__, 'openssl_test');
$enc      = tempnam(__DIR__, 'openssl_test');

file_put_contents($plain1, 'hello');

// generate key/crt
$key = openssl_pkey_new();
$csr = openssl_csr_new(array(), $key);
$crt = openssl_csr_sign($csr, null, $key, 365 * 100);

// first encrypt
var_dump(openssl_pkcs7_encrypt($plain1, $enc, $crt, array('foo' => 'bar')));
var_dump(openssl_pkcs7_decrypt($enc, $plain2, $crt, $key));

// can't decrypt
echo openssl_error_string(); // error:21070073:PKCS7 routines:PKCS7_dataDecode:no recipient matches certificate
var_dump(file_get_contents($plain2)); // empty

// reload the crt
openssl_x509_export($crt, $crt_data);
$crt2 = openssl_x509_read($crt_data);

// decryption works
openssl_pkcs7_encrypt($plain1, $enc, $crt2, array('foo' => 'bar'));
openssl_pkcs7_decrypt($enc, $plain2, $crt2, $key);

var_dump(file_get_contents($plain2)); // hello

// first encrypt aain
openssl_pkcs7_encrypt($plain1, $enc, $crt, array('foo' => 'bar'));
openssl_pkcs7_decrypt($enc, $plain2, $crt, $key);

// still can't decrypt
echo openssl_error_string(); // error:21070073:PKCS7 routines:PKCS7_dataDecode:no recipient matches certificate
var_dump(file_get_contents($plain2)); // empty

// crt are identical
var_dump(arrayRecursiveDiff(openssl_x509_parse($crt), openssl_x509_parse($crt2)));

unlink($plain1);
unlink($plain2);
unlink($enc);
