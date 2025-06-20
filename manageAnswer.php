<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'server.php';

$questionID = $_GET['questionID'] ?? 0;

// Validate input
if (!$questionID || !is_numeric($questionID)) {
    die("Invalid or missing question ID.");
}

// Fetch the question and quiz info
$stmt = $conn->prepare("SELECT q.question_text, q.quizID, qz.title AS quiz_title 
                        FROM question q
                        JOIN quiz qz ON q.quizID = qz.quizID 
                        WHERE q.questionID = ?");
$stmt->bind_param("i", $questionID);
$stmt->execute();
$result = $stmt->get_result();
$question = $result->fetch_assoc();

if (!$question) {
    die("Question with ID $questionID not found in the database.");
}

$quizID = $question['quizID'];

// Handle new answer submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['answerID'])) {
        // Delete answer
        $answerID = $_POST['answerID'];
        $stmt = $conn->prepare("DELETE FROM qanswer WHERE answerID = ? AND questionID = ?");
        $stmt->bind_param("ii", $answerID, $questionID);
        $stmt->execute();
        header("Location: manageAnswer.php?questionID=$questionID");
        exit();
    } else {
        // Add new answer
        $answer_text = trim($_POST['answer_text']);
        $status = isset($_POST['status']) ? 1 : 0;

        if (!empty($answer_text)) {
            $stmt = $conn->prepare("INSERT INTO qanswer (questionID, answer_text, status) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $questionID, $answer_text, $status);
            $stmt->execute();
            header("Location: manageAnswer.php?questionID=$questionID");
            exit();
        }
    }
}

// Fetch existing answers
$stmt = $conn->prepare("SELECT * FROM qanswer WHERE questionID = ? ORDER BY status DESC");
$stmt->bind_param("i", $questionID);
$stmt->execute();
$answers = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Answers</title>
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

    .question-info {
      margin-bottom: 20px;
      padding: 15px;
      background-color: #d4c0f0;
      border-radius: 8px;
    }

    .question-info h3 {
      color: #6c52a1;
      margin-bottom: 5px;
    }

    .question-info p {
      font-size: 1.1rem;
      color: #333;
    }

    form {
      margin-bottom: 20px;
      background-color: #f2eafb;
      padding: 15px;
      border-radius: 8px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #6c52a1;
    }

    input[type="text"] {
      width: 100%;
      padding: 10px;
      border: 1px solid #b28acb;
      border-radius: 6px;
      font-size: 1rem;
      margin-bottom: 15px;
    }

    .checkbox-container {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }

    input[type="checkbox"] {
      margin-right: 8px;
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

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: white;
      margin-bottom: 20px;
    }

    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ccc;
    }

    th {
      background-color: #dcd0f3;
      color: #6c52a1;
    }

    .correct {
      color: #198754;
      font-weight: bold;
    }

    .incorrect {
      color: #dc3545;
    }

    .action-link {
      color: #6c52a1;
      margin: 0 5px;
      text-decoration: none;
    }

    .back-link {
      display: inline-block;
      padding: 10px 15px;
      background-color: #6c52a1;
      color: white;
      text-decoration: none;
      border-radius: 6px;
      margin-top: 10px;
    }

    .back-link:hover {
      background-color: #563d7c;
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
        <h1>Manage Answers</h1>
      </div>
      <div class="user-info">
        <span><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></span>
        <div class="user-avatar"></div>
      </div>
    </div>

    <div class="content">
      <div class="question-info">
        <h3>Quiz: <?= htmlspecialchars($question['quiz_title']) ?></h3>
        <p>Question: <?= htmlspecialchars($question['question_text']) ?></p>
      </div>

      <form method="post">
        <h3><i class="fas fa-plus-circle"></i> Add New Answer</h3>
        <label for="answer_text">Answer Text:</label>
        <input type="text" name="answer_text" id="answer_text" required>

        <div class="checkbox-container">
          <input type="checkbox" name="status" id="status">
          <label for="status">Mark as Correct Answer</label>
        </div>

        <button type="submit">Add Answer</button>
      </form>

      <h3><i class="fas fa-list"></i> Existing Answers</h3>
      <?php if ($answers->num_rows > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Answer</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($a = $answers->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($a['answer_text']) ?></td>
                <td>
                  <?php if ($a['status']): ?>
                    <span class="correct"><i class="fas fa-check-circle"></i> Correct</span>
                  <?php else: ?>
                    <span class="incorrect"><i class="fas fa-times-circle"></i> Incorrect</span>
                  <?php endif; ?>
                </td>
                <td>
                  <form method="post" style="padding: 0; margin: 0; background: none; display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="answerID" value="<?= $a['answerID'] ?>">
                    <button type="submit" style="background: none; border: none; color: #dc3545; cursor: pointer;" 
                            onclick="return confirm('Are you sure you want to delete this answer?')">
                      <i class="fas fa-trash"></i> Delete
                    </button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p style="margin: 15px 0;">No answers added yet.</p>
      <?php endif; ?>

      <a href="manageQuestion.php?quizID=<?= $quizID ?>" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Questions
      </a>
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