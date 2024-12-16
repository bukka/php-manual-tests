<?php

function readOneLine($connection): string {
	$line = fgets($connection);
    //var_dump($line);
	if ($line === false || $line === '') throw new RuntimeException('Failed to read line from the server.');
	return $line;
}

function read($connection, int $bytes): string {
    $bytesLeft = $bytes;
    $data = '';

    do {
        $chunk = fread($connection, min($bytesLeft, 4096));
        //var_dump($chunk);
        if ($chunk === false) throw new RuntimeException('Failed to read data from the server.');
        $data .= $chunk;
        $bytesLeft = $bytes - strlen($data);
    }
    while($bytesLeft > 0);

    return $data;
}

function write($connection, string $data): int
{
    $totalBytesWritten = 0;
    $bytesLeft = strlen($data);

    do {
        $totalBytesWritten += $bytesWritten = fwrite($connection, $data);
        if ($bytesWritten === false) throw new RuntimeException('Failed to write data to the server.');
        $bytesLeft -= $bytesWritten;
        $data = substr($data, $bytesWritten);
    }
    while($bytesLeft > 0);

    return $totalBytesWritten;
}

$host = '127.0.0.1';
$port = 6379;

$context = stream_context_create(['socket' => ['tcp_nodelay' => true]]);

$connection = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 60, STREAM_CLIENT_CONNECT, $context);

stream_set_timeout($connection, 60);

//write($connection, "*3\r\n$3\r\nSET\r\n$3\r\nkey\r\n$5\r\nvalue\r\n");

//var_dump(readOneLine($connection));

write($connection, "*2\r\n$3\r\nGET\r\n$3\r\nkey\r\n");

var_dump(readOneLine($connection));

var_dump(read($connection, 7));

write($connection, "*2\r\n$3\r\nGET\r\n$3\r\nkey\r\n");

var_dump(readOneLine($connection));

var_dump(read($connection, 7));