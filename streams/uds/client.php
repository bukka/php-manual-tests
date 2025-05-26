<?php

$socketPath = '/tmp/test_socket.sock';

$fp = fsockopen("unix://$socketPath", -1, $errno, $errstr);
if (!$fp) {
    die("Client error: $errstr ($errno)\n");
}

fwrite($fp, "Ping from client\n");

while (!feof($fp)) {
    echo fgets($fp, 1024);
}

fclose($fp);

