<?php

$_stream = @stream_socket_client(
  "ssl://127.0.0.1:9993",
  $error_number,
  $error_string,
  30,
  STREAM_CLIENT_CONNECT,
  stream_context_create([
    'ssl' => [
      'verify_peer' => false,
      'verify_peer_name' => false,
      'allow_self_signed' => true,
    ],
  ])
);

stream_set_timeout($_stream, 30);
stream_set_read_buffer($_stream, 0);
stream_set_write_buffer($_stream, 0);

do {
  $in = fread($_stream, 8048);
  echo "read " . strlen($in) . " bytes\n";
  sleep(1);
} while (!feof($_stream));