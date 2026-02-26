<?php
$host = "localhost";
$username = "root";
$password = ""; // XAMPP default has no password
$database = "ecrime_sentinal";
$port = 3306; // ✅ Correct default port

$conn = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
