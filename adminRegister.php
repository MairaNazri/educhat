<?php
session_start();
include 'server.php'; // Make sure this connects to your DB

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role = 'admin';

    // Check if email or username already exists
    $check = $conn->prepare("SELECT * FROM user WHERE email = ? OR username = ?");
    $check->bind_param("ss", $email, $username);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('An account with this email or username already exists.');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO user (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $password, $role);

        if ($stmt->execute()) {
            // Log in the admin immediately
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;

            // Get userID for session
            $getID = $conn->prepare("SELECT userID FROM user WHERE email = ?");
            $getID->bind_param("s", $email);
            $getID->execute();
            $res = $getID->get_result();
            if ($res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $_SESSION['userID'] = $row['userID'];
            }

            echo "<script>alert('Admin registered successfully! Redirecting to admin dashboard...'); window.location.href = 'adminDash.php';</script>";
        } else {
            echo "<script>alert('Registration failed. Please try again.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eee;
        }
        .register-box {
            width: 400px;
            margin: 100px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #6a0dad;
        }
        input[type=text], input[type=email], input[type=password] {
            width: 100%;
            padding: 12px;
            margin: 10px 0 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        button {
            width: 100%;
            background-color: #6a0dad;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover {
            background-color: #580ca8;
        }
    </style>
</head>
<body>
    <div class="register-box">
        <h2>Register Admin</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Admin Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Register Admin</button>
        </form>
    </div>
</body>
</html>
