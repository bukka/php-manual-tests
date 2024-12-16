<?php
$infile = __DIR__ . "/plain.txt";
$outfile = __DIR__ . "/out.cms";;
$vout = $outfile . '.vout';

if ($outfile === false) {
    die("failed to get a temporary filename!");
}

$privkey = "file://" . __DIR__ . "/private_rsa_1024.key";
$single_cert = "file://" . __DIR__ . "/cert.crt";
$assoc_headers = array("To" => "test@test", "Subject" => "testing openssl_cms_sign()");
$headers = array("test@test", "testing openssl_cms_sign()");
$empty_headers = array();
$wrong = "wrong";
$empty = "";


// test three forms of detached signatures:
// PEM first
print("\nPEM Detached:\n");
var_dump(openssl_cms_sign($infile, $outfile, openssl_x509_read($single_cert), $privkey, $headers,
             OPENSSL_CMS_DETACHED|OPENSSL_CMS_BINARY,OPENSSL_ENCODING_PEM));
ini_set('open_basedir', __DIR__);
var_dump(openssl_cms_verify($infile,OPENSSL_CMS_NOVERIFY|OPENSSL_CMS_DETACHED|OPENSSL_CMS_BINARY,
         NULL, array(), NULL, $vout, NULL, "../test.cms", OPENSSL_ENCODING_PEM));
while ($msg = openssl_error_string())
    echo $msg . "\n";
print("\nValidated content:\n");
readfile($vout);
if (file_exists($outfile)) {
    echo "true\n";
    unlink($outfile);
}

if (file_exists($vout)) {
    echo "true\n";
    unlink($vout);
}