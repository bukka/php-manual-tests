<?php

$port = 3307;
$servername = "";
$username = "";
$password = "";

$conn = mysqli_init();
$conn->real_connect("127.0.0.1:$port", $username, $password, 'audit', $port, '');

$result = $conn->query("SELECT * from users");
$all_fields = $result->fetch_fields();
var_dump($result->fetch_all());
echo(get_object_vars($all_fields[0])["def"]);