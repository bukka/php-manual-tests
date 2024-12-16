<?php
var_dump(extension_loaded('gnupg'));

$fingerprint = '88C5C9C1E9A49007C59808879046CE6FDD690424';
$passphrase = 'JohnJones';
$plaintext = 'hello';
//$gpg_binary = '/usr/bin/gpg';
$gpg_binary = '/usr/local/gnupg/2.3/bin/gpg';
//$gpg_binary = '/usr/local/bin/gpg23';

$gpg = new \Gnupg(['home_dir' => __DIR__ . '/gpg', 'file_name' => $gpg_binary]);
$gpg->seterrormode(\Gnupg::ERROR_EXCEPTION);
var_dump($gpg->keyinfo(''));
$gpg->addencryptkey('88C5C9C1E9A49007C59808879046CE6FDD690424');
$enc = $gpg->encrypt($plaintext);

$gpg = new \Gnupg(['home_dir' => __DIR__ . '/gpg', 'file_name' => $gpg_binary]);
$gpg->seterrormode(\Gnupg::ERROR_EXCEPTION);
$gpg->adddecryptkey($fingerprint, $passphrase);
$ret = $gpg->decrypt($enc);
var_dump($ret);
