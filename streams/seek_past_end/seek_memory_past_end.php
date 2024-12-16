<?php

echo "Read write mode\n";
$fprw = fopen("php://memory", "r+");
var_dump(fwrite($fprw, "data"));
var_dump(fseek($fprw, 20, SEEK_END));
var_dump(feof($fprw));
var_dump(ftell($fprw));
var_dump(feof($fprw));
var_dump(fread($fprw, 2));
var_dump(feof($fprw));
var_dump(fseek($fprw, 20));
var_dump(fwrite($fprw, " and more data"));
var_dump(feof($fprw));
var_dump(ftell($fprw));
var_dump(fread($fprw, 10));
var_dump(fseek($fprw, 16, SEEK_CUR));
var_dump(ftell($fprw));
var_dump(fseek($fprw, 0));
var_dump(bin2hex(stream_get_contents($fprw)));
fclose($fprw);