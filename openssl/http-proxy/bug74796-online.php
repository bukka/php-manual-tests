<?php

stream_context_set_default([
  "http" => [
    "proxy" => "tcp://127.0.0.1:10000"
    ],
]);
//var_dump(stream_context_get_options(stream_context_get_default()));
file_get_contents("https://www.example.com/"); // fine
// var_dump(stream_context_get_options(stream_context_get_default())); // ssl:peer_name is set
// file_get_contents("https://www.yahoo.com/"); // Peer certificate CN=`*.www.yahoo.com' did not match expected CN=`www.google.com' in ...
// var_dump(stream_context_get_options(stream_context_get_default()));
