<?php

$path = __DIR__ . '/seek_file.txt';

file_put_contents($path, 'data');

echo "Read mode\n";
$fpr = fopen($path, "r");
var_dump(fseek($fpr, -2));
var_dump(ftell($fpr));
var_dump(fseek($fpr, 20));
var_dump(feof($fpr));
var_dump(ftell($fpr));
var_dump(feof($fpr));
var_dump(fread($fpr, 2));
var_dump(feof($fpr));
var_dump(fseek($fpr, 24));
var_dump(feof($fpr));
var_dump(ftell($fpr));
fclose($fpr);

echo "Read write mode\n";
$fprw = fopen($path, "r+");
var_dump(fseek($fprw, 20));
var_dump(feof($fprw));
var_dump(ftell($fprw));
var_dump(feof($fprw));
var_dump(fread($fprw, 2));
var_dump(feof($fprw));
var_dump(fseek($fprw, 100));
var_dump(fwrite($fprw, " and more data"));
var_dump(feof($fprw));
var_dump(ftell($fprw));
var_dump(fread($fprw, 10));
fclose($fprw);

var_dump(bin2hex(file_get_contents($path)));