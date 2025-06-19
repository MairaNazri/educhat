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
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Profile</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', sans-serif;
    }

    body {
      background-color: #d9c8f4;
      display: flex;
      min-height: 100vh;
      transition: all 0.3s ease;
      overflow-x: hidden;
    }

    .sidebar {
      width: 240px;
      background-color: #b187d6;
      padding: 20px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      border-top-right-radius: 20px;
      border-bottom-right-radius: 20px;
      transition: width 0.3s ease;
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      z-index: 10;
    }

    .sidebar.collapsed {
      width: 70px;
    }

    .sidebar .logo {
      font-size: 1.8rem;
      font-weight: bold;
      background-color: black;
      padding: 10px;
      border-radius: 5px;
      text-align: center;
      color: white;
      margin-bottom: 20px;
    }

    .toggle-btn {
      color: white;
      cursor: pointer;
      font-size: 1.2rem;
      text-align: right;
      margin-bottom: 20px;
    }

    .nav-section {
      display: flex;
      flex-direction: column;
      gap: 25px;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 1rem;
      padding: 10px;
      border-radius: 8px;
      cursor: pointer;
      color: white;
      transition: background 0.2s ease;
      text-decoration: none;
    }

    .nav-item:hover {
      background-color: #6e50a1;
    }

    .nav-item.active {
      background-color: #6e50a1;
    }

    .nav-item i {
      min-width: 20px;
      text-align: center;
    }

    .logout-btn {
      background-color: white;
      color: #6e50a1;
      font-weight: bold;
      border: none;
      padding: 10px;
      border-radius: 10px;
      cursor: pointer;
      margin-top: 30px;
      transition: 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .logout-btn:hover {
      background-color: #f3e6ff;
    }

    .main {
      flex: 1;
      padding: 40px;
      background-color: #e5dffc;
      margin-left: 240px;
      transition: margin-left 0.3s ease;
      width: 100%;
    }

    .sidebar.collapsed ~ .main {
      margin-left: 70px;
    }

    .collapsed .nav-item span,
    .collapsed .logo,
    .collapsed .logout-btn span {
      display: none;
    }

    .collapsed .nav-item,
    .collapsed .logout-btn {
      justify-content: center;
    }

    .collapsed .logout-btn {
      padding: 10px 0;
    }

    .top-box {
      background-color: #f5f2ff;
      padding: 30px;
      border-radius: 15px;
      margin-bottom: 30px;
      display: flex;
      align-items: center;
      gap: 25px;
    }

    .profile-image {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 15px;
      border: 3px solid #6e50a1;
    }

    .profile-info h2 {
      font-size: 1.8rem;
      margin-bottom: 10px;
      color: #333;
    }

    .profile-stats {
      color: #666;
      font-size: 1rem;
    }

    .form-section {
      background-color: #f5f2ff;
      padding: 25px;
      border-radius: 15px;
      margin-bottom: 25px;
    }

    .form-section h3 {
      font-size: 1.3rem;
      margin-bottom: 20px;
      color: #333;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .form-section h3 i {
      color: #6e50a1;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      color: #333;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"],
    .form-group input[type="file"] {
      width: 100%;
      padding: 12px 15px;
      border-radius: 10px;
      border: 2px solid #ddd;
      font-size: 1rem;
      transition: border-color 0.3s ease;
      background-color: white;
    }

    .form-group input:focus {
      outline: none;
      border-color: #6e50a1;
    }

    .btn {
      padding: 12px 25px;
      border: none;
      border-radius: 10px;
      background-color: #8a75c9;
      color: white;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      transition: background-color 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn:hover {
      background-color: #6f5baa;
    }

    .btn i {
      font-size: 0.9rem;
    }

    .delete-btn {
      background-color: #e74c3c;
    }

    .delete-btn:hover {
      background-color: #c0392b;
    }

    .success-message {
      margin-bottom: 20px;
      padding: 15px;
      background-color: #27ae60;
      color: white;
      border-radius: 10px;
      font-weight: 500;
    }

    .error-message {
      margin-bottom: 20px;
      padding: 15px;
      background-color: #e74c3c;
      color: white;
      border-radius: 10px;
      font-weight: 500;
    }

    .image-preview-container {
      margin-top: 15px;
    }

    .image-preview {
      max-width: 200px;
      max-height: 200px;
      border-radius: 10px;
      border: 2px solid #ddd;
    }

    .password-strength {
      margin-top: 10px;
    }

    .strength-indicator {
      font-weight: 600;
      font-size: 0.9rem;
    }

    .delete-section {
      border: 2px solid #e74c3c;
      background-color: #fdf2f2;
    }

    @media screen and (max-width: 768px) {
      .main {
        padding: 20px;
        margin-left: 0;
      }
      
      .sidebar {
        transform: translateX(-100%);
      }
      
      .sidebar.show {
        transform: translateX(0);
      }
      
      .top-box {
        flex-direction: column;
        text-align: center;
        gap: 15px;
      }
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div>
      <div class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></div>
      <div class="logo">EDUCHAT</div>
      <div class="nav-section">
        <a class="nav-item" href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a>
        <a class="nav-item" href="chatbotTopic.php"><i class="fas fa-comment"></i><span>Chatbot</span></a>
        <a class="nav-item" href="quizSelect.php"><i class="fas fa-clipboard"></i><span>Quiz</span></a>
        <a class="nav-item" href="flashcardDash.php"><i class="fas fa-clone"></i><span>Flashcards</span></a>
        <a class="nav-item active" href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a>
      </div>
    </div>
    <button class="logout-btn" onclick="window.location.href='logout.php'">
      <i class="fas fa-sign-out-alt"></i><span>Logout</span>
    </button>
  </div>

  <!-- Main Content -->
  <div class="main" id="main">
    <!-- Profile Header -->
    <div class="top-box">
      <img src="<?php echo $user['profile_picture'] ?? 'default-avatar.png'; ?>" alt="Profile Picture" class="profile-image" />
      <div class="profile-info">
        <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
        <p class="profile-stats">Total Quizzes: <?php echo $quizCount; ?> | Chatbot Sessions: <?php echo $chatCount; ?></p>
      </div>
    </div>

    <!-- Messages -->
    <?php if ($msg): ?>
      <div class="success-message">
        <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="error-message">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <!-- Update Profile Form -->
    <div class="form-section">
      <h3><i class="fas fa-user-edit"></i> Update Profile</h3>
      <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label for="name">Full Name</label>
          <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required />
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required />
        </div>
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required />
        </div>
        <div class="form-group">
          <label for="profile_picture">Profile Picture</label>
          <input type="file" name="profile_picture" accept="image/*" onchange="previewImage(event)" />
          <div class="image-preview-container">
            <img id="preview" src="<?php echo $user['profile_picture'] ?? 'default-avatar.png'; ?>" alt="Preview" class="image-preview" />
          </div>
        </div>
        <button type="submit" name="update_profile" class="btn">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </form>
    </div>

    <!-- Change Password Form -->
    <div class="form-section">
      <h3><i class="fas fa-lock"></i> Change Password</h3>
      <form method="POST">
        <div class="form-group">
          <label for="new_password">New Password</label>
          <input type="password" name="new_password" id="new_password" required />
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input type="password" name="confirm_password" id="confirm_password" required />
        </div>
        <div class="form-group password-strength">
          <label>Password Strength:</label>
          <div id="strengthMessage" class="strength-indicator"></div>
        </div>
        <button type="submit" name="change_password" class="btn">
          <i class="fas fa-key"></i> Update Password
        </button>
      </form>
    </div>

    <!-- Delete Account Form -->
    <div class="form-section delete-section">
      <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
      <p style="margin-bottom: 15px; color: #666;">Once you delete your account, there is no going back. Please be certain.</p>
      <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
        <button type="submit" name="delete_account" class="btn delete-btn">
          <i class="fas fa-trash"></i> Delete Account
        </button>
      </form>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('collapsed');
    }

    function previewImage(event) {
      const preview = document.getElementById('preview');
      if (event.target.files[0]) {
        preview.src = URL.createObjectURL(event.target.files[0]);
      }
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
          strengthMessage.style.color = "#27ae60";
          break;
        case 4:
          strengthMessage.textContent = "Strong";
          strengthMessage.style.color = "#2ecc71";
          break;
        case 3:
          strengthMessage.textContent = "Moderate";
          strengthMessage.style.color = "#f39c12";
          break;
        case 2:
          strengthMessage.textContent = "Weak";
          strengthMessage.style.color = "#e67e22";
          break;
        default:
          strengthMessage.textContent = "Very Weak";
          strengthMessage.style.color = "#e74c3c";
      }
    });

    // Mobile responsive sidebar
    if (window.innerWidth <= 768) {
      document.getElementById('sidebar').classList.add('collapsed');
    }
  </script>

</body>
</html>