<?php

$port = 3306;
$servername = "127.0.0.1";
$username = "php_test";
$database = "php_test";
$password = "PHPdev0*";

$conn = new mysqli($servername, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


echo "[*] Connected to the database...\n";

// Prepare the SQL statement
$stmt = $conn->prepare("SELECT item FROM items");

if (!$stmt) {
    die("Prepared statement failed: " . $conn->error);
}

// Execute the prepared statement
$stmt->execute();

// Get the result set from the executed statement
$result = $stmt->get_result();

// Fetch and display the results
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "item: " . $row["item"] . "\n";
    }
} else {
    echo "No items found.\n";
}

// Close the statement and connection
$stmt->close();
$conn->close();