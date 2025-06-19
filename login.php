<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'server.php';

$login_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    // Validate input
    if (empty($email) || empty($password)) {
        $login_error = "Please enter both email and password";
    } else {
        // Fetch user from database
        $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Save user data in session
                $_SESSION['userID'] = $user['userID'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Set remember me cookie if checked
                if ($remember) {
                    // Generate a token and store in database
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                    
                    // You would need a 'remember_tokens' table in your database
                    // This is just an example - implement secure token storage
                    setcookie('remember_token', $token, $expiry, '/', '', true, true);
                }

                // Redirect to dashboard based on role
                if ($user['role'] === 'admin') {
                    header("Location: adminDash.php");
                    exit();
                } else {
                    header("Location: Dashboard.php");
                    exit();
                }
            } else {
                $login_error = "Incorrect password";
            }
        } else {
            $login_error = "User not found";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>EduChat - Login</title>
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
    }

    .form-section h2 {
      font-size: 1.8rem;
      margin-bottom: 30px;
      text-align: center;
    }

    .form-section input {
      width: 100%;
      padding: 14px;
      margin-bottom: 20px;
      border: none;
      border-radius: 30px;
      font-size: 1rem;
      outline: none;
    }

    .remember-forgot {
      display: flex;
      justify-content: space-between;
      font-size: 0.9rem;
      margin-bottom: 20px;
    }

    .remember-forgot label {
      display: flex;
      align-items: center;
    }

    .remember-forgot input[type="checkbox"] {
      width: auto;
      margin-right: 8px;
    }

    .remember-forgot a {
      color: #000;
      text-decoration: none;
      font-weight: bold;
    }

    .form-buttons {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .login-btn {
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

    .login-btn:hover {
      background-color: #a574f5;
    }

    .register-link {
      margin-top: 20px;
      font-size: 0.9rem;
      text-align: center;
    }

    .register-link a {
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
    
    .error-message {
      background-color: rgba(255, 0, 0, 0.1);
      color: #d32f2f;
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 20px;
      text-align: center;
    }
    
    .spinner {
      display: none;
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255,255,255,.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s ease-in-out infinite;
      margin-left: 10px;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    .btn-container {
      display: flex;
      align-items: center;
      justify-content: center;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="form-section">
      <h2>Welcome back!</h2>
      
      <?php if (!empty($login_error)): ?>
        <div class="error-message">
          <?php echo htmlspecialchars($login_error); ?>
        </div>
      <?php endif; ?>
      
      <!-- LOGIN FORM START -->
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" id="loginForm">
        <label for="email">Email Address</label>
        <input type="email" name="email" placeholder="Email"
          value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
          onfocus="this.placeholder=''" 
          onblur="if(this.value==='') this.placeholder='Email'" required />

          <label for="password">Password</label>
          <input type="password" name="password" placeholder="Password"
          onfocus="this.placeholder=''" 
          onblur="if(this.value==='') this.placeholder='Password'" required />

        <div class="remember-forgot">
          <label><input type="checkbox" name="remember" <?php echo (isset($_POST['remember'])) ? 'checked' : ''; ?> />Remember me</label>
        </div>

        <div class="form-buttons">
          <div class="btn-container">
            <button type="submit" class="login-btn" id="loginBtn">Sign in</button>
            <div class="spinner" id="spinner"></div>
          </div>
        </div>
      </form>
      <!-- LOGIN FORM END -->

      <div class="register-link">
        Don't have an account? <a href="register.php">Register</a>
      </div>
    </div>
    <div class="image-section">
      <div class="close-btn" onclick="window.location.href='homepage.php'">&times;</div>
    </div>
  </div>
  
  <script>
    // Show loading spinner during form submission
    document.getElementById('loginForm').addEventListener('submit', function() {
      document.getElementById('loginBtn').innerHTML = 'Signing in...';
      document.getElementById('spinner').style.display = 'inline-block';
    });
    
    // Prevent double submission
    let formSubmitted = false;
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      if (formSubmitted) {
        e.preventDefault();
        return false;
      }
      formSubmitted = true;
    });
  </script>
</body>
</html>