<?php
include 'server.php';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get data from form
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate input
    $errors = [];
    // Name validation - only letters and spaces
    if (empty($name)) {
        $errors[] = "Name is required";
    } elseif (!preg_match("/^[a-zA-Z ]*$/", $name)) {
        $errors[] = "Name should only contain letters";
    }
    
    // Username validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 4) {
        $errors[] = "Username must be at least 4 characters";
    }
    
    // Email validation
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    // Password validation
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match("#[0-9]+#", $password) || !preg_match("#[A-Z]+#", $password) || !preg_match("#[a-z]+#", $password)) {
        $errors[] = "Password must include uppercase, lowercase, and numbers";
    }

    // If no validation errors, proceed with registration
    if (empty($errors)) {
        // Check if user already exists by username or email
        $check = $conn->prepare("SELECT * FROM user WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "User already exists! Please use a different username or email.";
            $messageType = "error";
        } else {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert into database
            $role = 'user';
            $stmt = $conn->prepare("INSERT INTO user (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $username, $email, $hashedPassword, $role);

            if ($stmt->execute()) {
                $message = "Registration successful! You can now log in.";
                $messageType = "success";
                header("refresh:2;url=login.php"); // Changed to login.php
            } else {
                $message = "Something went wrong. Please try again.";
                $messageType = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>EduChat - Register</title>
  <style>
    * {
      box-sizing: border-box;
      font-family: 'Segoe UI', sans-serif;
    }

    body {
      margin: 0;
      background-color: #e9dcf7;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .container {
      display: flex;
      width: 90%;
      max-width: 1000px;
      height: 600px;
      background-color: #d8c7ff;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .form-section {
      flex: 0.8;
      padding: 60px 30px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      background-color: #e6d4ff;
      position: relative;
      overflow-y: auto;
    }

    .form-section h2 {
      font-size: 1.8rem;
      margin-bottom: 20px;
      text-align: center;
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: #444;
    }

    .form-section input {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 30px;
      font-size: 1rem;
      outline: none;
    }

    .form-buttons {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-top: 15px;
    }

    .create-btn {
      background-color: #c199fc;
      color: white;
      font-weight: bold;
      cursor: pointer;
      border: none;
      padding: 14px 30px;
      border-radius: 30px;
      transition: 0.3s;
      width: 100%;
      max-width: 250px;
    }

    .create-btn:hover {
      background-color: #a574f5;
    }

    .signin-link {
      margin-top: 20px;
      font-size: 0.9rem;
      text-align: center;
    }

    .signin-link a {
      color: #000;
      text-decoration: none;
      font-weight: bold;
    }

    .image-section {
      flex: 1.2;
      background: url('book.png') no-repeat center;
      background-size: cover;
      position: relative;
    }

    .close-btn {
      position: absolute;
      top: 20px;
      right: 20px;
      width: 35px;
      height: 35px;
      background: white;
      color: #000;
      border-radius: 50%;
      font-size: 18px;
      line-height: 35px;
      text-align: center;
      cursor: pointer;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
      font-weight: bold;
      user-select: none;
    }
    
    .message {
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 5px;
      text-align: center;
    }
    
    .error {
      background-color: #ffdddd;
      color: #f44336;
    }
    
    .success {
      background-color: #ddffdd;
      color: #4CAF50;
    }
    
    .password-strength {
      height: 5px;
      margin-top: 8px;
      border-radius: 5px;
      background: #ddd;
      position: relative;
      overflow: hidden;
    }
    
    .password-strength-bar {
      height: 100%;
      width: 0%;
      transition: width 0.3s, background-color 0.3s;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="form-section">
      <h2>Create an account</h2>
      
      <?php if (isset($message)): ?>
        <div class="message <?php echo $messageType; ?>">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>
      
      <?php if (isset($errors) && !empty($errors)): ?>
        <div class="message error">
          <?php foreach($errors as $error): ?>
            <div><?php echo $error; ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        <div class="form-group">
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" required
            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" />
        </div>
        
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" required
            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" />
        </div>
        
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" required
            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required />
          <div class="password-strength">
            <div class="password-strength-bar" id="strengthBar"></div>
          </div>
        </div>

        <div class="form-buttons">
          <button type="submit" class="create-btn" id="submitBtn">Create account</button>
        </div>
      </form>
      <div class="signin-link">
        Have an account? <a href="login.php">Sign in</a>
      </div>
    </div>
    <div class="image-section">
      <div class="close-btn" onclick="window.location.href='homepage.php'">&times;</div>
    </div>
  </div>
  
  <script>
    // Password strength meter
    const password = document.getElementById('password');
    const strengthBar = document.getElementById('strengthBar');
    const submitBtn = document.getElementById('submitBtn');
    
    password.addEventListener('input', function() {
      const val = password.value;
      let strength = 0;
      
      // Length check
      if (val.length >= 8) strength += 25;
      
      // Character type checks
      if (val.match(/[a-z]+/)) strength += 25;
      if (val.match(/[A-Z]+/)) strength += 25;
      if (val.match(/[0-9]+/)) strength += 25;
      
      // Update strength bar
      strengthBar.style.width = strength + '%';
      
      // Color based on strength
      if (strength < 50) {
        strengthBar.style.backgroundColor = '#f44336'; // Red
      } else if (strength < 75) {
        strengthBar.style.backgroundColor = '#FFA500'; // Orange
      } else {
        strengthBar.style.backgroundColor = '#4CAF50'; // Green
      }
    });
    
    // Form validation for name
    document.getElementById('name').addEventListener('input', function() {
      this.value = this.value.replace(/[0-9]/g, '');
    });
  </script>
</body>
</html>