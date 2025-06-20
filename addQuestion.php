<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'server.php';

$quizID = $_GET['quizID'] ?? 0;

// Get quiz title for reference
$quiz_stmt = $conn->prepare("SELECT title FROM quiz WHERE quizID = ?");
$quiz_stmt->bind_param("i", $quizID);
$quiz_stmt->execute();
$quiz_result = $quiz_stmt->get_result();
$quiz_title = ($quiz_result->num_rows > 0) ? $quiz_result->fetch_assoc()['title'] : 'Unknown Quiz';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $text = $_POST['question_text'];
    $type = "multiple_choice"; // fixed question type

    if (!empty($text)) {
        $stmt = $conn->prepare("INSERT INTO question (quizID, question_text, question_type) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $quizID, $text, $type);
        $stmt->execute();
        header("Location: manageQuestion.php?quizID=$quizID");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add Question</title>
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
    }

    .quiz-info {
      margin-bottom: 20px;
      padding: 10px;
      background-color: #d4c0f0;
      border-radius: 8px;
    }

    form {
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #6c52a1;
    }

    textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #b28acb;
      border-radius: 6px;
      font-size: 1rem;
      margin-bottom: 15px;
    }

    button {
      padding: 10px 20px;
      background-color: #8f75b5;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1rem;
      transition: background 0.2s ease;
    }

    button:hover {
      background-color: #6c52a1;
    }

    .back-link {
      display: inline-block;
      margin-top: 15px;
      color: #6c52a1;
      text-decoration: none;
      font-weight: 500;
    }

    .back-link:hover {
      text-decoration: underline;
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
        <h1>Add Question</h1>
      </div>
      <div class="user-info">
        <span><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></span>
        <div class="user-avatar"></div>
      </div>
    </div>

    <div class="content">
      <div class="quiz-info">
        <h3>Adding Question to: <?= htmlspecialchars($quiz_title) ?> (Quiz ID: <?= $quizID ?>)</h3>
      </div>

      <form method="post">
        <label for="question_text">Question Text:</label>
        <textarea name="question_text" id="question_text" rows="4" required></textarea>

        <!-- Hidden fixed question type -->
        <input type="hidden" name="question_type" value="multiple_choice" />

        <button type="submit">Save Question</button>
        <a class="back-link" href="manageQuestion.php?quizID=<?= $quizID ?>">
          <i class="fas fa-arrow-left"></i> Back to Questions
        </a>
      </form>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      sidebar.classList.toggle("collapsed");
    }
  </script>
</body>
</html>
