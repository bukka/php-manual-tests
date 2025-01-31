<?php
 
error_reporting(E_ALL);
require_once 'library.php';
 
header('Foo : Bar');
flush(); // Only change
echo 'I fell to pieces in a horrendous and nonsensical way';
