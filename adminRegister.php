<?php
session_start();
include 'server.php'; // Make sure this file contains your DB connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['User_Username'], $_POST['User_Password'], $_POST['User_Role'])) {
        $username = $_POST['User_Username'];
        $password = password_hash($_POST['User_Password'], PASSWORD_BCRYPT);
        $role = $_POST['User_Role']; // Can be 'user' or 'admin'

        $sql = "INSERT INTO users (User_Username, User_Password, User_Role) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $password, $role);

        if ($stmt->execute()) {
            header("Location: login.php"); // Redirect to login page after successful registration
        } else {
            $error = "Error: " . $stmt->error;
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Mental Health App</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to bottom, #fbc2eb, #a6c1ee);
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .container h1 {
            margin-bottom: 15px;
            color: #6a11cb;
        }
        .form-group {
            text-align: left;
            margin-bottom: 15px;
        }
        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
        }
        .submit-button {
            width: 100%;
            padding: 12px;
            background: #6a11cb;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
        }
        .submit-button:hover {
            background: #4b00b6;
        }
        .register-link {
            margin-top: 15px;
            font-size: 0.9rem;
        }
        .register-link a {
            color: #6a11cb;
            text-decoration: none;
            font-weight: 600;
        }
        .error {
            color: red;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Register</h1>
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="User_Username">Username</label>
                <input type="text" id="User_Username" name="User_Username" required>
            </div>
            <div class="form-group">
                <label for="User_Password">Password</label>
                <input type="password" id="User_Password" name="User_Password" required>
            </div>
            <div class="form-group">
                <label for="User_Role">Role</label>
                <select id="User_Role" name="User_Role" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="submit-button">Register</button>
        </form>
    </div>
</body>
</html>