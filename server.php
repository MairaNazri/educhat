<?php
$servername = "localhost";       // keep as 'localhost'
$username = "root";              // XAMPP default
$password = "";                  // XAMPP default is empty
$dbname = "chatbot";             // <-- your actual DB name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
