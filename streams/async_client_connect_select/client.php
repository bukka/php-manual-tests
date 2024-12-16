<?php
$uri = '127.0.0.1:9000'; // <-- NOTHING LISTENING ON THIS PORT!
$flags = STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT;
$socket = stream_socket_client($uri, $errNo, $errStr, $timeout=42, $flags);

usleep(50000); // just in case

var_dump($socket); // resource(5) of type (stream)

$w = [$socket];
$r = $e = [];
if (stream_select($r, $w, $e, 0, 0)) {
    $selectedSock = current($w);
    var_dump($selectedSock); // resource(5) of type (stream) ... says it's writable, BUT IT CAN'T POSSIBLY BE WRITABLE
}

$r = [$socket];
$w = $e = [];
if (stream_select($r, $w, $e, 0, 0)) {
    $selectedSock = current($r);
    var_dump($selectedSock); // resource(5) of type (stream) ... says it's readable, let's check for EOF
}

usleep(50000); // just in case

$isFeof = feof($selectedSock);
var_dump($isFeof); // bool(false) ... not EOF ... WTF?

var_dump(fread($selectedSock, 8192)); // string(0) ""

var_dump(feof($selectedSock)); // bool(TRUE) -- Now we know the truth!