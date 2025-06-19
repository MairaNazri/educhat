<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}
include 'server.php';

$toast_message = "";
$toast_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $level = $_POST['proficiency_level'];

    if (!empty($title) && !empty($level)) {
        // Check if quiz title already exists
        $check_stmt = $conn->prepare("SELECT quizID FROM quiz WHERE title = ?");
        $check_stmt->bind_param("s", $title);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $toast_message = "Quiz title already exists!";
            $toast_type = "error";
        } else {
            // Insert new quiz
            try {
                $stmt = $conn->prepare("INSERT INTO quiz (title, proficiency_level) VALUES (?, ?)");
                $stmt->bind_param("ss", $title, $level);
                
                if ($stmt->execute()) {
                    $_SESSION['toast_message'] = "Quiz created successfully!";
                    $_SESSION['toast_type'] = "success";
                    header("Location: manageQuiz.php");
                    exit();
                } else {
                    $toast_message = "Database error: " . $stmt->error;
                    $toast_type = "error";
                }
            } catch (Exception $e) {
                $toast_message = "Database error: " . $e->getMessage();
                $toast_type = "error";
            }
        }
    } else {
        $toast_message = "All fields are required!";
        $toast_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add New Quiz</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', sans-serif;
    }

    body {
      background-color: #dcd0f3;
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: 240px;
      background-color: #b28acb;
      color: white;
      padding: 20px;
      display: flex;
      flex-direction: column;
      transition: width 0.3s ease;
    }

    .sidebar.collapsed {
      width: 70px;
    }

    .sidebar h2 {
      font-size: 1.6rem;
      color: #e5ddf4;
      margin-bottom: 30px;
      text-align: center;
    }

    .sidebar.collapsed h2 {
      display: none;
    }

    .nav-link {
      position: relative;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 15px;
      border-radius: 10px;
      cursor: pointer;
      color: #f2e9ff;
      font-size: 1rem;
      transition: background 0.3s ease;
    }

    .nav-link:hover,
    .nav-link.active {
      background-color: #8f75b5;
    }

    .nav-link i {
      min-width: 20px;
      text-align: center;
    }

    .sidebar.collapsed .nav-link {
      justify-content: center;
      padding: 10px 0;
    }

    .sidebar.collapsed .nav-link span {
      display: none;
    }

    .main {
      flex: 1;
      padding: 40px;
      background-color: #c6aef0;
      transition: margin-left 0.3s ease;
      width: 100%;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .header h1 {
      font-size: 1.8rem;
      color: #6c52a1;
      margin-left: 10px;
    }

    .user-info {
      font-size: 1rem;
      color: #333;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .user-avatar {
      width: 35px;
      height: 35px;
      background-color: #ccc;
      border-radius: 50%;
    }

    .toggle-btn {
      font-size: 24px;
      cursor: pointer;
      color: #6c52a1;
    }

    .content {
      background-color: #e8dcfa;
      border-radius: 15px;
      padding: 20px;
      max-width: 600px;
      margin: auto;
    }

    .content h2 {
      color: #6c52a1;
      margin-bottom: 20px;
    }

    form {
      display: flex;
      flex-direction: column;
    }

    label {
      margin-top: 10px;
      font-weight: 500;
      color: #4b3676;
    }

    input[type="text"],
    select {
      margin-top: 5px;
      padding: 10px;
      border: 1px solid #aaa;
      border-radius: 6px;
      font-size: 1rem;
    }

    input[type="text"].invalid,
    select.invalid {
      border-color: #dc3545;
      background-color: #fff8f8;
    }

    .error-message {
      color: #dc3545;
      font-size: 0.85rem;
      margin-top: 4px;
      display: none;
    }

    button[type="submit"] {
      margin-top: 20px;
      padding: 10px;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1rem;
      transition: background-color 0.3s;
    }

    button[type="submit"]:hover:not(:disabled) {
      background-color: #218838;
    }

    button[type="submit"]:disabled {
      background-color: #94d3a2;
      cursor: not-allowed;
    }

    .back-link {
      display: inline-block;
      margin-top: 20px;
      text-decoration: none;
      color: #6c52a1;
    }

    .nav-link[data-tooltip]:hover::after {
      content: attr(data-tooltip);
      position: absolute;
      left: 100%;
      top: 50%;
      transform: translateY(-50%);
      background-color: #6c52a1;
      color: white;
      padding: 4px 8px;
      border-radius: 4px;
      white-space: nowrap;
      margin-left: 10px;
      font-size: 0.85rem;
      z-index: 10;
    }

    /* Toast notification styles */
    .toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1000;
    }

    .toast {
      display: flex;
      align-items: center;
      padding: 12px 16px;
      margin-bottom: 10px;
      border-radius: 8px;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
      color: white;
      font-weight: 500;
      min-width: 250px;
      max-width: 350px;
      animation: slideIn 0.3s ease-out forwards;
      opacity: 0;
      transform: translateX(50px);
    }

    .toast.success {
      background-color: #28a745;
    }

    .toast.error {
      background-color: #dc3545;
    }

    .toast-icon {
      margin-right: 12px;
      font-size: 1.2rem;
    }

    .toast-message {
      flex-grow: 1;
    }

    .toast-close {
      cursor: pointer;
      margin-left: 8px;
      opacity: 0.7;
    }

    .toast-close:hover {
      opacity: 1;
    }

    @keyframes slideIn {
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes fadeOut {
      from {
        opacity: 1;
      }
      to {
        opacity: 0;
      }
    }
  </style>
</head>
<body>

  <div class="sidebar" id="sidebar">
    <h2>EduChat<br>Admin</h2>

    <div class="nav-link" onclick="location.href='adminDash.php'" data-tooltip="Dashboard">
      <i class="fas fa-home"></i> <span>Dashboard</span>
    </div>

    <div class="nav-link active" data-tooltip="Manage Quizzes">
      <i class="fas fa-clipboard-list"></i> <span>Manage Quizzes</span>
    </div>

    <div class="nav-link" onclick="location.href='manageChatbot.php'" data-tooltip="Manage Chatbot">
      <i class="fas fa-comments"></i> <span>Manage Chatbot</span>
    </div>

    <div class="nav-link" onclick="location.href='manageUser.php'" data-tooltip="User Management">
      <i class="fas fa-user"></i> <span>User Management</span>
    </div>

    <a href="logout.php" class="nav-link" data-tooltip="Logout" style="margin-top: auto;">
      <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
    </a>
  </div>

  <div class="main" id="main">
    <div class="header">
      <div style="display: flex; align-items: center;">
        <div class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></div>
        <h1>Add New Quiz</h1>
      </div>
      <div class="user-info">
        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <div class="user-avatar"></div>
      </div>
    </div>

    <div class="content">
      <h2>Create New Quiz</h2>
      <form method="post" id="quizForm">
        <label for="title">Quiz Title:</label>
        <input type="text" name="title" id="title" required>
        <div class="error-message" id="title-error">Please enter a valid quiz title (3-50 characters).</div>

        <label for="proficiency_level">Proficiency Level:</label>
        <select name="proficiency_level" id="proficiency_level" required>
          <option value="">Select level</option>
          <option value="Beginner">Beginner</option>
          <option value="Intermediate">Intermediate</option>
          <option value="Advanced">Advanced</option>
        </select>
        <div class="error-message" id="level-error">Please select a proficiency level.</div>

        <button type="submit" id="submitBtn" disabled>Create Quiz</button>
      </form>

      <a class="back-link" href="manageQuiz.php">← Back to Quiz List</a>
    </div>
  </div>

  <!-- Toast notification container -->
  <div class="toast-container" id="toastContainer"></div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      sidebar.classList.toggle("collapsed");
    }

    // Form validation functions
    const titleInput = document.getElementById('title');
    const levelSelect = document.getElementById('proficiency_level');
    const submitBtn = document.getElementById('submitBtn');
    const titleError = document.getElementById('title-error');
    const levelError = document.getElementById('level-error');

    // Function to validate form
    function validateForm() {
      let isValid = true;
      
      // Validate title
      if (!titleInput.value || titleInput.value.trim().length < 3 || titleInput.value.trim().length > 50) {
        titleInput.classList.add('invalid');
        titleError.style.display = 'block';
        isValid = false;
      } else {
        titleInput.classList.remove('invalid');
        titleError.style.display = 'none';
      }
      
      // Validate proficiency level
      if (!levelSelect.value) {
        levelSelect.classList.add('invalid');
        levelError.style.display = 'block';
        isValid = false;
      } else {
        levelSelect.classList.remove('invalid');
        levelError.style.display = 'none';
      }
      
      // Enable/disable submit button
      submitBtn.disabled = !isValid;
      
      return isValid;
    }

    // Add event listeners for real-time validation
    titleInput.addEventListener('input', validateForm);
    levelSelect.addEventListener('change', validateForm);

    // Toast notification function
    function showToast(message, type) {
      const toastContainer = document.getElementById('toastContainer');
      
      // Create toast element
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      
      // Set icon based on type
      let icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
      
      toast.innerHTML = `
        <div class="toast-icon"><i class="fas ${icon}"></i></div>
        <div class="toast-message">${message}</div>
        <div class="toast-close" onclick="this.parentElement.style.animation='fadeOut 0.3s forwards'"><i class="fas fa-times"></i></div>
      `;
      
      // Add to container
      toastContainer.appendChild(toast);
      
      // Remove toast after 5 seconds
      setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s forwards';
        setTimeout(() => {
          toastContainer.removeChild(toast);
        }, 300);
      }, 5000);
    }

    // Show toast if PHP set a message
    <?php if (!empty($toast_message)): ?>
    document.addEventListener('DOMContentLoaded', function() {
      showToast('<?php echo addslashes($toast_message); ?>', '<?php echo $toast_type; ?>');
    });
    <?php endif; ?>
  </script>
</body>
</html>