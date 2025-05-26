<?php

$s = stream_socket_server('unix:///tmp/demo.sock');
$c = stream_socket_client('unix:///tmp/demo.sock');

var_dump(
    stream_socket_get_name($s, true),
    stream_socket_get_name($c, false)
);
