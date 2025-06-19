<?php
session_start();
require 'server.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: login.html");
    exit();
}

if (!isset($_SESSION['userID']) || !isset($_POST['quizID'])) {
    die("Invalid access.");
}

$userID = $_SESSION['userID'];
$quizID = intval($_POST['quizID']);

// Get all questions for the quiz
$questionStmt = $conn->prepare("SELECT * FROM question WHERE quizID = ?");
$questionStmt->bind_param("i", $quizID);
$questionStmt->execute();
$questionResult = $questionStmt->get_result();

$questions = [];
while ($row = $questionResult->fetch_assoc()) {
    $questions[] = $row;
}

$score = 0;
$total = count($questions);

foreach ($questions as $q) {
    $questionID = $q['questionID'];
    $fieldName = 'question_' . $questionID;

    if (!isset($_POST[$fieldName])) {
        continue; // Skip unanswered questions (shouldn't happen with required)
    }

    $selectedAnswerID = $_POST[$fieldName];

    $answerStmt = $conn->prepare("SELECT status FROM qanswer WHERE answerID = ?");
    $answerStmt->bind_param("i", $selectedAnswerID);
    $answerStmt->execute();
    $answerResult = $answerStmt->get_result();
    $answerData = $answerResult->fetch_assoc();

    if ($answerData && $answerData['status'] == 1) {
        $score++;
    }
}

// Calculate result
$passMark = ceil($total * 0.6); // 60%
$passed = $score >= $passMark ? 1 : 0;
$percentage = $total > 0 ? round(($score / $total) * 100) : 0;

// Save or update quiz result
$checkStmt = $conn->prepare("SELECT * FROM quizresult WHERE userID = ? AND quizID = ?");
$checkStmt->bind_param("ii", $userID, $quizID);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $updateStmt = $conn->prepare("UPDATE quizresult SET score = ?, passed = ?, completed_date = NOW() WHERE userID = ? AND quizID = ?");
    $updateStmt->bind_param("iiii", $score, $passed, $userID, $quizID);
    $updateStmt->execute();
} else {
    $insertStmt = $conn->prepare("INSERT INTO quizresult (userID, quizID, score, passed, completed_date) VALUES (?, ?, ?, ?, NOW())");
    $insertStmt->bind_param("iiii", $userID, $quizID, $score, $passed);
    $insertStmt->execute();
}

// Get quiz name
$quizNameStmt = $conn->prepare("SELECT title FROM quiz WHERE quizID = ?");
$quizNameStmt->bind_param("i", $quizID);
$quizNameStmt->execute();
$quizNameResult = $quizNameStmt->get_result();
$quizData = $quizNameResult->fetch_assoc();
$quizName = $quizData ? $quizData['title'] : "Quiz";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Quiz Result - EDUCHAT</title>
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

    .nav-item:hover,
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

    .result-box {
      background-color: #f5f2ff;
      padding: 30px;
      border-radius: 15px;
      margin-bottom: 30px;
      text-align: center;
    }

    .result-box h2 {
      font-size: 1.8rem;
      margin-bottom: 15px;
      color: #6e50a1;
    }

    .result-box h3 {
      font-size: 1.5rem;
      margin-bottom: 20px;
    }

    .score-display {
      font-size: 3rem;
      font-weight: bold;
      margin: 20px 0;
      color: #6e50a1;
    }

    .progress {
      background-color: #ddd;
      border-radius: 20px;
      overflow: hidden;
      height: 20px;
      margin: 20px auto;
      max-width: 400px;
    }

    .progress-bar {
      height: 100%;
      background-color: <?php echo $passed ? '#6fcf97' : '#e74c3c'; ?>;
      width: <?php echo $percentage; ?>%;
      transition: width 1s ease-in-out;
    }

    .result-message {
      font-size: 1.5rem;
      margin: 20px 0;
      padding: 15px;
      border-radius: 10px;
      background-color: <?php echo $passed ? '#d4edda' : '#f8d7da'; ?>;
      color: <?php echo $passed ? '#155724' : '#721c24'; ?>;
    }

    .action-buttons {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 30px;
    }

    .action-btn {
      background-color: #8a75c9;
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 10px;
      cursor: pointer;
      font-weight: bold;
      font-size: 1rem;
      text-decoration: none;
      transition: background 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .action-btn:hover {
      background-color: #6f5baa;
    }

    .details-section {
      background-color: #f5f2ff;
      padding: 20px;
      border-radius: 15px;
      margin-top: 20px;
    }

    .details-section h3 {
      font-size: 1.3rem;
      margin-bottom: 15px;
      color: #6e50a1;
    }

    .stat-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
    }

    .stat-card {
      background-color: white;
      padding: 15px;
      border-radius: 12px;
      text-align: center;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .stat-card span {
      display: block;
    }

    .stat-card .stat-label {
      font-size: 0.9rem;
      color: #666;
      margin-bottom: 5px;
    }

    .stat-card .stat-value {
      font-size: 1.5rem;
      font-weight: bold;
      color: #6e50a1;
    }

    @media screen and (max-width: 768px) {
      .main {
        padding: 20px;
        margin-left: 70px;
      }
      
      .sidebar {
        width: 70px;
      }
      
      .sidebar .nav-item span,
      .sidebar .logo,
      .sidebar .logout-btn span {
        display: none;
      }
      
      .sidebar .nav-item,
      .sidebar .logout-btn {
        justify-content: center;
      }
      
      .action-buttons {
        flex-direction: column;
        align-items: center;
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
        <a class="nav-item active" href="quizSelect.php"><i class="fas fa-clipboard"></i><span>Quiz</span></a>
        <a class="nav-item" href="flashcardDash.php"><i class="fas fa-clone"></i><span>Flashcards</span></a>
        <a class="nav-item" href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a>
      </div>
    </div>
    <button class="logout-btn" onclick="window.location.href='logout.php'">
      <i class="fas fa-sign-out-alt"></i><span>Logout</span>
    </button>
  </div>

  <!-- Main Content -->
  <div class="main" id="main">
    <div class="result-box">
      <h2><?php echo htmlspecialchars($quizName); ?> Results</h2>
      <h3>Your Performance</h3>
      
      <div class="score-display">
        <?php echo $score . " / " . $total; ?>
        <div class="progress">
          <div class="progress-bar"></div>
        </div>
        <div><?php echo $percentage; ?>%</div>
      </div>
      
      <div class="result-message">
        <?php if ($passed): ?>
          <i class="fas fa-trophy"></i> Congratulations, you passed!
        <?php else: ?>
          <i class="fas fa-exclamation-circle"></i> You didn't pass this time. Keep practicing!
        <?php endif; ?>
      </div>
      
      <div class="action-buttons">
        <a href="quizSelect.php" class="action-btn">
          <i class="fas fa-list"></i> Quiz List
        </a>
        <?php if (!$passed): ?>
          <form action="quizSelect.php" method="POST" style="display: inline-block;">
            <input type="hidden" name="quizID" value="<?php echo $quizID; ?>">
            <button type="submit" class="action-btn">
              <i class="fas fa-redo"></i> Try Again
            </button>
          </form>
        <?php endif; ?>
        <a href="dashboard.php" class="action-btn">
          <i class="fas fa-home"></i> Dashboard
        </a>
      </div>
    </div>
    
    <div class="details-section">
      <h3>Performance Details</h3>
      <div class="stat-grid">
        <div class="stat-card">
          <span class="stat-label">Correct Answers</span>
          <span class="stat-value"><?php echo $score; ?></span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Total Questions</span>
          <span class="stat-value"><?php echo $total; ?></span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Score Percentage</span>
          <span class="stat-value"><?php echo $percentage; ?>%</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Pass Mark</span>
          <span class="stat-value"><?php echo $passMark; ?> (60%)</span>
        </div>
      </div>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('collapsed');
    }
    
    // Animate the progress bar on page load
    window.onload = function() {
      setTimeout(function() {
        document.querySelector('.progress-bar').style.width = '<?php echo $percentage; ?>%';
      }, 300);
    }
  </script>

</body>
</html>