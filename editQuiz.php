<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'server.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM quiz WHERE quizID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $quiz = $stmt->get_result()->fetch_assoc();
    
    if (!$quiz) {
        header("Location: manageQuiz.php");
        exit();
    }
} else {
    header("Location: manageQuiz.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $level = $_POST['proficiency_level'];

    $stmt = $conn->prepare("UPDATE quiz SET title=?, proficiency_level=? WHERE quizID=?");
    $stmt->bind_param("ssi", $title, $level, $id);
    
    if ($stmt->execute()) {
        header("Location: manageQuiz.php?success=updated");
    } else {
        $error = "Failed to update quiz. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Quiz - <?= htmlspecialchars($quiz['title']) ?></title>
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
      position: relative;
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
      text-decoration: none;
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
      padding: 30px;
      max-width: 800px;
    }

    .breadcrumb {
      margin-bottom: 20px;
      color: #6c52a1;
      font-size: 0.9rem;
    }

    .breadcrumb a {
      color: #6c52a1;
      text-decoration: none;
    }

    .breadcrumb a:hover {
      text-decoration: underline;
    }

    .quiz-info {
      background-color: #d4c5f9;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 30px;
      border-left: 4px solid #6c52a1;
    }

    .quiz-info h3 {
      color: #6c52a1;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .quiz-info p {
      color: #666;
      margin: 5px 0;
    }

    .form-container {
      background-color: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .form-container h2 {
      color: #6c52a1;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: #6c52a1;
      font-weight: 600;
      font-size: 1rem;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: #6c52a1;
      box-shadow: 0 0 0 3px rgba(108, 82, 161, 0.1);
    }

    .form-group input:hover,
    .form-group select:hover {
      border-color: #b28acb;
    }

    .button-group {
      display: flex;
      gap: 15px;
      margin-top: 30px;
    }

    .btn {
      padding: 12px 25px;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-weight: 500;
    }

    .btn-primary {
      background-color: #6c52a1;
      color: white;
    }

    .btn-primary:hover {
      background-color: #5a4287;
      transform: translateY(-1px);
    }

    .btn-secondary {
      background-color: #6c757d;
      color: white;
    }

    .btn-secondary:hover {
      background-color: #5a6268;
      transform: translateY(-1px);
    }

    .error-message {
      background-color: #f8d7da;
      color: #721c24;
      padding: 12px 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      border: 1px solid #f5c6cb;
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

    .back-button {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #6c52a1;
      text-decoration: none;
      margin-bottom: 20px;
      font-weight: 500;
      transition: color 0.3s ease;
    }

    .back-button:hover {
      color: #5a4287;
    }

    .form-help {
      font-size: 0.9rem;
      color: #666;
      margin-top: 5px;
    }

    @media (max-width: 768px) {
      .main {
        padding: 20px;
      }
      
      .button-group {
        flex-direction: column;
      }
      
      .btn {
        justify-content: center;
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
        <h1>Edit Quiz</h1>
      </div>
      <div class="user-info">
        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <div class="user-avatar"></div>
      </div>
    </div>

    <div class="content">
      <div class="breadcrumb">
        <a href="manageQuiz.php"><i class="fas fa-clipboard-list"></i> Manage Quizzes</a> 
        <i class="fas fa-chevron-right" style="margin: 0 8px;"></i> 
        <span>Edit Quiz</span>
      </div>

      <a href="manageQuiz.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Back to Quiz Management
      </a>

      <div class="quiz-info">
        <h3><i class="fas fa-info-circle"></i> Current Quiz Information</h3>
        <p><strong>Quiz ID:</strong> <?= $quiz['quizID'] ?></p>
        <p><strong>Current Title:</strong> <?= htmlspecialchars($quiz['title']) ?></p>
        <p><strong>Current Level:</strong> <?= htmlspecialchars($quiz['proficiency_level']) ?></p>
      </div>

      <div class="form-container">
        <h2><i class="fas fa-edit"></i> Edit Quiz Details</h2>
        
        <?php if (isset($error)): ?>
          <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
          </div>
        <?php endif; ?>

        <form method="POST">
          <div class="form-group">
            <label for="title">
              <i class="fas fa-heading"></i> Quiz Title
            </label>
            <input 
              type="text" 
              id="title"
              name="title" 
              value="<?= htmlspecialchars($quiz['title']) ?>" 
              required
              maxlength="255"
              placeholder="Enter quiz title..."
            >
            <div class="form-help">Choose a clear, descriptive title for your quiz</div>
          </div>

          <div class="form-group">
            <label for="proficiency_level">
              <i class="fas fa-layer-group"></i> Proficiency Level
            </label>
            <select id="proficiency_level" name="proficiency_level" required>
              <option value="">Select Level</option>
              <option value="Beginner" <?= $quiz['proficiency_level'] == 'Beginner' ? 'selected' : '' ?>>
                Beginner
              </option>
              <option value="Intermediate" <?= $quiz['proficiency_level'] == 'Intermediate' ? 'selected' : '' ?>>
                Intermediate
              </option>
              <option value="Advanced" <?= $quiz['proficiency_level'] == 'Advanced' ? 'selected' : '' ?>>
                Advanced
              </option>
            </select>
            <div class="form-help">Select the appropriate difficulty level for this quiz</div>
          </div>

          <div class="button-group">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> Save Changes
            </button>
            <a href="manageQuiz.php" class="btn btn-secondary">
              <i class="fas fa-times"></i> Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      sidebar.classList.toggle("collapsed");
    }
    
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
      const title = document.getElementById('title').value.trim();
      const level = document.getElementById('proficiency_level').value;
      
      if (!title) {
        e.preventDefault();
        alert('Please enter a quiz title.');
        document.getElementById('title').focus();
        return;
      }
      
      if (!level) {
        e.preventDefault();
        alert('Please select a proficiency level.');
        document.getElementById('proficiency_level').focus();
        return;
      }
    });

    // Auto-focus on title field
    document.getElementById('title').focus();
  </script>
</body>
</html>