<?php

$context = stream_context_create(['ssl' => ['local_cert' => __DIR__ . '/cert.pem']]);

$flags = STREAM_SERVER_BIND|STREAM_SERVER_LISTEN;
$fp = stream_socket_server("ssl://127.0.0.1:9993", $errornum, $errorstr, $flags, $context);
$conn = stream_socket_accept($fp);

for ($i = 0; $i < 32; $i++) {
	$written = fwrite($conn, str_repeat('a', 1000 * 1000));
	echo "written $written bytes\n";
	sleep(1);
}
