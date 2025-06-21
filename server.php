<?php
$servername = "localhost";       // keep as 'localhost'
$username = "db";              // XAMPP default
$password = "0U8euUMg+2!v7Z";                  // XAMPP default is empty
$dbname = "educhats_db";             // <-- your actual DB name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
