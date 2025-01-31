<?php
 
error_reporting(E_ALL);
require_once 'library.php';
 
header('Foo: Bar');
flush();
echo 'I worked absolutely fine';
