<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}
include 'server.php';

$quizID = $_GET['quizID'] ?? 0;

// Validate quiz ID
if (!$quizID || !is_numeric($quizID)) {
    die("Invalid quiz ID.");
}

// Fetch quiz
$stmt = $conn->prepare("SELECT title, proficiency_level FROM quiz WHERE quizID = ?");
$stmt->bind_param("i", $quizID);
$stmt->execute();
$result = $stmt->get_result();
$quiz = $result->fetch_assoc();

if (!$quiz) {
    die("Quiz not found.");
}

// Fetch all questions
$stmt = $conn->prepare("SELECT * FROM question WHERE quizID = ? ORDER BY questionID DESC");
$stmt->bind_param("i", $quizID);
$stmt->execute();
$questions = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Questions</title>
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

    .quiz-info {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 20px;
    }
    
    .quiz-title {
      font-size: 1.2rem;
      font-weight: 500;
      color: #6c52a1;
    }
    
    .level-badge {
      padding: 4px 10px;
      border-radius: 15px;
      font-size: 0.85rem;
      color: white;
      background-color: #8f75b5;
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

    .action-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .action-buttons {
      display: flex;
      gap: 10px;
    }

    .button {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 15px;
      border-radius: 6px;
      color: white;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.2s ease;
    }

    .btn-primary {
      background-color: #28a745;
    }

    .btn-primary:hover {
      background-color: #218838;
    }

    .btn-secondary {
      background-color: #6c757d;
    }

    .btn-secondary:hover {
      background-color: #565e64;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: white;
      border-radius: 8px;
      overflow: hidden;
    }

    th, td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #dcd0f3;
      color: #6c52a1;
      font-weight: 600;
    }

    tr:last-child td {
      border-bottom: none;
    }

    tr:hover {
      background-color: #f9f6ff;
    }

    .action-link {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 6px 10px;
      border-radius: 4px;
      color: white;
      text-decoration: none;
      font-size: 0.9rem;
      margin-right: 5px;
      transition: all 0.2s ease;
    }

    .action-edit {
      background-color: #6c52a1;
    }

    .action-edit:hover {
      background-color: #5b4589;
    }

    .action-delete {
      background-color: #dc3545;
    }

    .action-delete:hover {
      background-color: #c82333;
    }

    .action-manage {
      background-color: #28a745;
    }

    .action-manage:hover {
      background-color: #218838;
    }

    .question-text {
      max-width: 500px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    
    .empty-state {
      text-align: center;
      padding: 40px 0;
      color: #6c52a1;
    }
    
    .empty-state i {
      font-size: 48px;
      margin-bottom: 15px;
      opacity: 0.7;
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
    
    .type-badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 0.85rem;
      color: white;
    }
    
    .type-mc {
      background-color: #17a2b8;
    }
    
    .type-sa {
      background-color: #ffc107;
      color: #212529;
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
        <h1>Manage Questions</h1>
      </div>
      <div class="user-info">
        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <div class="user-avatar"></div>
      </div>
    </div>

    <div class="content">
      <div class="quiz-info">
        <div class="quiz-title">Quiz: <?= htmlspecialchars($quiz['title']) ?></div>
        <div class="level-badge"><?= htmlspecialchars($quiz['proficiency_level']) ?></div>
      </div>
      
      <div class="action-bar">
        <div class="action-buttons">
          <a href="addQuestion.php?quizID=<?= $quizID ?>" class="button btn-primary">
            <i class="fas fa-plus"></i> Add Question
          </a>
          <a href="manageQuiz.php" class="button btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Quizzes
          </a>
        </div>
      </div>

      <?php if ($questions->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th width="8%">ID</th>
            <th width="55%">Question</th>
            <th width="12%">Type</th>
            <th width="25%">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($q = $questions->fetch_assoc()): ?>
            <tr>
              <td><?= $q['questionID'] ?></td>
              <td class="question-text" title="<?= htmlspecialchars($q['question_text']) ?>">
                <?= htmlspecialchars($q['question_text']) ?>
              </td>
              <td>
                <?php if ($q['question_type'] === 'multiple_choice'): ?>
                  <span class="type-badge type-mc">Multiple Choice</span>
                <?php else: ?>
                  <span class="type-badge type-sa">Short Answer</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="editQuestion.php?questionID=<?= $q['questionID'] ?>" class="action-link action-edit">
                  <i class="fas fa-edit"></i> Edit
                </a>
                <a href="deleteQuestion.php?questionID=<?= $q['questionID'] ?>&quizID=<?= $quizID ?>" 
                   class="action-link action-delete" 
                   onclick="return confirm('Are you sure you want to delete this question?');">
                  <i class="fas fa-trash-alt"></i> Delete
                </a>
                <?php if ($q['question_type'] === 'multiple_choice'): ?>
                  <a href="manageAnswer.php?questionID=<?= $q['questionID'] ?>" class="action-link action-manage">
                    <i class="fas fa-list-check"></i> Answers
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-clipboard-question"></i>
        <h3>No questions found</h3>
        <p>Start by adding questions to this quiz</p>
      </div>
      <?php endif; ?>
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