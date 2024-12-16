<?php

$gpg_binary = '/usr/bin/gpg';
#$gpg_binary = '/usr/local/gnupg/2.3/bin/gpg';

$gpg = new \Gnupg(['home_dir' => __DIR__ . '/gpg', 'file_name' => $gpg_binary]);
//$gpg->seterrormode(\Gnupg::ERROR_EXCEPTION);
$gpg->adddecryptkey('DDAD2AB87FDB228BC5E9E4AF0E55BD6058B0636E', 'test');
$ret = $gpg->decrypt(file_get_contents(__DIR__ . '/sample.gpg'));
print_r($gpg->geterrorinfo());