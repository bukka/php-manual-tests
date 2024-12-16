<?php

$port = 3306;
$port = 33305;
$servername = "127.0.0.1";
$username = "php_test";
$database = "php_test";
$password = "PHPdev0*";

$conn = new mysqli($servername, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "[*] Connected to the database...\n";

$field = $argv[1] ?? 'strval';

// Construct the SQL query directly
$sql = "SELECT strval, $field FROM `data`";

// Execute the query
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "data for $field: " . $row[$field] . "\n";
    }
} else {
    echo "No items found.\n";
}

// Close the connection
$conn->close();