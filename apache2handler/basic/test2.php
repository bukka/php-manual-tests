<?php
 
error_reporting(E_ALL);
require_once 'library.php';
 
header('Foo : Bar'); // Only change
echo 'I went wrong but did so gracefully';
