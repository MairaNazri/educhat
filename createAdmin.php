<?php
include 'server.php';

// Admin details
$name = "Admin User";
$username = "admin";
$email = "admin@example.com";
$password = password_hash("admin123", PASSWORD_DEFAULT);  // Replace with a real password
$role = "admin";

// Check if admin already exists
$check = $conn->prepare("SELECT * FROM user WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo "Admin already exists.";
} else {
    $stmt = $conn->prepare("INSERT INTO user (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $username, $email, $password, $role);
    if ($stmt->execute()) {
        echo "Admin user created successfully!";
    } else {
        echo "Failed to create admin.";
    }
}
?>
