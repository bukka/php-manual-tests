<?php
$path = "foobar://google.com/../fseek_stdin.php";
var_dump(file_exists($path));
var_dump(file_get_contents($path, false, null, 0, 10));
