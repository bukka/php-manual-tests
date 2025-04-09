<?php

stream_context_set_default([
  "http" => [
    "proxy" => "tcp://127.0.0.1:10001"
    ],
]);
var_dump(stream_context_get_options(stream_context_get_default()));
var_dump(file_get_contents("https://www.google.com/")); // fine
var_dump(stream_context_get_options(stream_context_get_default())); // ssl:peer_name is set
var_dump(file_get_contents("https://www.yahoo.com/")); // Peer certificate CN=`*.www.yahoo.com' did not match expected CN=`www.google.com' in ...
var_dump(stream_context_get_options(stream_context_get_default()));
