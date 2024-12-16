<?php
var_dump(extension_loaded('gnupg'));

//putenv('GNUPGHOME=./gpg');

$gpg = new \Gnupg(['home_dir' => __DIR__ . '/gpg']);
$gpg->seterrormode(\Gnupg::ERROR_EXCEPTION);

var_dump(
    $gpg->verify(
        file_get_contents('composer-require-checker.phar'),
        file_get_contents('composer-require-checker.phar.asc')
    )
);
 
