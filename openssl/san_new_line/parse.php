<?php

// Load the certificate
$cert = file_get_contents('certificate.crt');

$x509 = openssl_x509_parse($cert);

var_dump($x509['extensions']);
