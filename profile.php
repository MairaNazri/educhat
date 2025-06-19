<?php
session_start();
include 'server.php';

if (!isset($_SESSION['userID'])) {
    header("Location: login.html");
    exit();
}

$userID = $_SESSION['userID'];
$msg = "";
$error = "";

// Fetch user data
$user = $conn->query("SELECT * FROM user WHERE userID = $userID")->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $username = $conn->real_escape_string($_POST['username']);

        $checkDup = $conn->query("SELECT * FROM user WHERE (email='$email' OR username='$username') AND userID != $userID");
        if ($checkDup->num_rows > 0) {
            $error = "Email or username already exists!";
        } else {
            if (!empty($_FILES['profile_picture']['name'])) {
                $target = "uploads/" . basename($_FILES['profile_picture']['name']);
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target)) {
                    $conn->query("UPDATE user SET profile_picture='$target' WHERE userID=$userID");
                    $user['profile_picture'] = $target;
                }
            }

            $conn->query("UPDATE user SET name='$name', email='$email', username='$username' WHERE userID=$userID");
            $msg = "Profile updated!";
            $user = $conn->query("SELECT * FROM user WHERE userID = $userID")->fetch_assoc();
        }
    }

    if (isset($_POST['change_password'])) {
        $newPass = $_POST['new_password'];
        $confirmPass = $_POST['confirm_password'];

        if ($newPass !== $confirmPass) {
            $error = "Passwords do not match!";
        } elseif (
            strlen($newPass) < 8 ||
            !preg_match("/[A-Z]/", $newPass) ||
            !preg_match("/[a-z]/", $newPass) ||
            !preg_match("/[0-9]/", $newPass) ||
            !preg_match("/[\W]/", $newPass)
        ) {
            $error = "Password must be at least 8 characters long, include uppercase, lowercase, number, and special character.";
        } else {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $conn->query("UPDATE user SET password='$hashed' WHERE userID=$userID");
            $msg = "Password changed!";
        }
    }

    if (isset($_POST['delete_account'])) {
        $conn->query("DELETE FROM user WHERE userID = $userID");
        session_destroy();
        header("Location: goodbye.html");
        exit();
    }
}

$quizCount = $conn->query("SELECT COUNT(*) AS total FROM quizresult WHERE userID = $userID")->fetch_assoc()['total'];
$chatCount = $conn->query("SELECT COUNT(*) AS total FROM chatbot_interaction WHERE userID = $userID")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>User Profile</title>
  <link rel="stylesheet" href="styles.css" />
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      background: #f4f3fa;
      display: flex;
    }

    .sidebar {
      width: 240px;
      background: #6a0dad;
      color: white;
      height: 100vh;
      position: fixed;
      transition: 0.3s;
      overflow-y: auto;
    }

    .sidebar.collapsed {
      width: 60px;
    }

    .sidebar h2 {
      text-align: center;
      padding: 1rem 0;
      font-size: 24px;
      margin: 0;
      background: #5b08a7;
    }

    .sidebar ul {
      list-style: none;
      padding: 0;
    }

    .sidebar ul li {
      padding: 15px 20px;
    }

    .sidebar ul li a {
      color: white;
      text-decoration: none;
      display: block;
    }

    .sidebar ul li:hover {
      background-color: #500c92;
    }

    .main {
      margin-left: 240px;
      padding: 20px;
      width: 100%;
    }

    .top-box {
      background: #fff;
      padding: 20px;
      border-radius: 15px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .top-box img {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 10px;
    }

    .form-section {
      margin-top: 30px;
      background: #fff;
      padding: 20px;
      border-radius: 15px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .form-section h3 {
      margin-top: 0;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      font-weight: bold;
      margin-bottom: 5px;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"],
    .form-group input[type="file"] {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }

    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      background: #6a0dad;
      color: white;
      cursor: pointer;
    }

    .btn:hover {
      background: #500c92;
    }

    .success-message {
      margin-top: 15px;
      padding: 10px;
      background-color: #27ae60;
      color: white;
      border-radius: 5px;
    }

    .delete-btn {
      background-color: #e74c3c;
    }

    .delete-btn:hover {
      background-color: #c0392b;
    }
  </style>
</head>
<body>
  <div class="sidebar" id="sidebar">
    <h2>EduChat</h2>
    <ul>
      <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
      <li><a href="chatbotTopic.php"><i class="fas fa-comments"></i> Chatbot</a></li>
      <li><a href="quizSelect.php"><i class="fas fa-pen"></i> Quiz</a></li>
      <li><a href="flashcardDash.php"><i class="fas fa-clone"></i> Flashcards</a></li>
      <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
    </ul>
  </div>

  <div class="main">
    <div class="top-box">
      <img src="<?php echo $user['profile_picture'] ?? 'default-avatar.png'; ?>" alt="Profile Picture" />
      <div>
        <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
        <p>Total Quizzes: <?php echo $quizCount; ?> | Chatbot Sessions: <?php echo $chatCount; ?></p>
      </div>
    </div>

    <?php if ($msg): ?>
      <div class="success-message"><?php echo $msg; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="success-message" style="background-color: #e74c3c;"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="form-section">
      <h3>Update Profile</h3>
      <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label for="name">Name</label>
          <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required />
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required />
        </div>
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required />
        </div>
        <div class="form-group">
          <label for="profile_picture">Profile Picture</label>
          <input type="file" name="profile_picture" accept="image/*" onchange="previewImage(event)" />
          <br><br>
          <img id="preview" src="<?php echo $user['profile_picture'] ?? 'default-avatar.png'; ?>" alt="Preview" style="max-width: 150px; border-radius: 10px;" />
        </div>
        <button type="submit" name="update_profile" class="btn"><i class="fas fa-save"></i> Save Changes</button>
      </form>
    </div>

    <div class="form-section">
      <h3>Change Password</h3>
      <form method="POST">
        <div class="form-group">
          <label for="new_password">New Password</label>
          <input type="password" name="new_password" id="new_password" required />
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input type="password" name="confirm_password" id="confirm_password" required />
        </div>
        <div class="form-group">
          <label>Password Strength:</label>
          <div id="strengthMessage" style="font-weight: bold;"></div>
        </div>
        <button type="submit" name="change_password" class="btn"><i class="fas fa-lock"></i> Update Password</button>
      </form>
    </div>

    <div class="form-section">
      <h3>Delete Account</h3>
      <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account?');">
        <button type="submit" name="delete_account" class="btn delete-btn"><i class="fas fa-trash"></i> Delete Account</button>
      </form>
    </div>
  </div>

  <script>
    function previewImage(event) {
      const preview = document.getElementById('preview');
      preview.src = URL.createObjectURL(event.target.files[0]);
    }

    const newPasswordInput = document.getElementById('new_password');
    const strengthMessage = document.getElementById('strengthMessage');

    newPasswordInput.addEventListener('input', function () {
      const value = newPasswordInput.value;
      let strength = 0;

      if (value.length >= 8) strength++;
      if (/[A-Z]/.test(value)) strength++;
      if (/[a-z]/.test(value)) strength++;
      if (/[0-9]/.test(value)) strength++;
      if (/[\W]/.test(value)) strength++;

      switch (strength) {
        case 5:
          strengthMessage.textContent = "Very Strong";
          strengthMessage.style.color = "green";
          break;
        case 4:
          strengthMessage.textContent = "Strong";
          strengthMessage.style.color = "darkgreen";
          break;
        case 3:
          strengthMessage.textContent = "Moderate";
          strengthMessage.style.color = "orange";
          break;
        case 2:
          strengthMessage.textContent = "Weak";
          strengthMessage.style.color = "orangered";
          break;
        default:
          strengthMessage.textContent = "Very Weak";
          strengthMessage.style.color = "red";
      }
    });
  </script>
</body>
</html>
