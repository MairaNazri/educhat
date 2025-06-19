<?php
session_start();
require 'server.php';

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'user' || !isset($_GET['quizID'])) {
    header("Location: login.html");
    exit();
}

$quizID = intval($_GET['quizID']);
$userID = $_SESSION['userID'];

// Get user's proficiency level
$userQuery = $conn->prepare("SELECT proficiency_level FROM user WHERE userID = ?");
$userQuery->bind_param("i", $userID);
$userQuery->execute();
$userResult = $userQuery->get_result();
if ($userResult->num_rows === 0) {
    die("User not found.");
}
$user = $userResult->fetch_assoc();
$proficiency = $user['proficiency_level'];

// Get quiz info
$quizInfoQuery = $conn->prepare("SELECT title FROM quiz WHERE quizID = ?");
$quizInfoQuery->bind_param("i", $quizID);
$quizInfoQuery->execute();
$quizResult = $quizInfoQuery->get_result();
$quizInfo = $quizResult->fetch_assoc();
$quizTitle = $quizInfo['title'];

// Get all quizzes in the user's level, ordered
$quizListQuery = $conn->prepare("SELECT quizID FROM quiz WHERE proficiency_level = ? ORDER BY quizID ASC");
$quizListQuery->bind_param("s", $proficiency);
$quizListQuery->execute();
$quizListResult = $quizListQuery->get_result();

$allQuizzes = [];
while ($row = $quizListResult->fetch_assoc()) {
    $allQuizzes[] = $row['quizID'];
}

// Get user's passed quizzes
$completedQuery = $conn->prepare("SELECT quizID FROM quizresult WHERE userID = ? AND passed = 1");
$completedQuery->bind_param("i", $userID);
$completedQuery->execute();
$completedResult = $completedQuery->get_result();

$completed = [];
while ($row = $completedResult->fetch_assoc()) {
    $completed[] = $row['quizID'];
}

// Check if current quiz is allowed (sequential access)
$allowed = true;
foreach ($allQuizzes as $q) {
    if ($q == $quizID) break;
    if (!in_array($q, $completed)) {
        $allowed = false;
        break;
    }
}

if (!$allowed) {
    header("Location: quizSelect.php?error=sequence");
    exit;
}

// Get questions
$questionQuery = $conn->prepare("SELECT * FROM question WHERE quizID = ?");
$questionQuery->bind_param("i", $quizID);
$questionQuery->execute();
$questionResult = $questionQuery->get_result();

$questions = [];
while ($q = $questionResult->fetch_assoc()) {
    $questions[] = $q;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Take Quiz - <?php echo htmlspecialchars($quizTitle); ?></title>
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

    .quiz-header {
      background-color: #f5f2ff;
      padding: 20px;
      border-radius: 15px;
      margin-bottom: 30px;
    }

    .quiz-header h2 {
      font-size: 1.5rem;
      margin-bottom: 10px;
      color: #333;
    }

    .quiz-header p {
      color: #666;
      margin-bottom: 5px;
    }

    .question-container {
      background-color: #f5f2ff;
      padding: 20px;
      border-radius: 15px;
      margin-bottom: 30px;
    }

    .question {
      background-color: white;
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .question-text {
      font-size: 1.1rem;
      font-weight: 600;
      color: #333;
      margin-bottom: 15px;
    }

    .answer-options {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .answer-option {
      padding: 10px 15px;
      background-color: #efe8fd;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
    }

    .answer-option:hover {
      background-color: #dfd3f3;
    }

    .answer-option input[type="radio"] {
      margin-right: 10px;
    }

    .answer-option label {
      cursor: pointer;
      flex: 1;
    }

    .submit-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 20px;
    }

    .submit-btn {
      background-color: #8a75c9;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 10px;
      cursor: pointer;
      font-weight: bold;
      font-size: 1rem;
      transition: background 0.2s ease;
    }

    .submit-btn:hover {
      background-color: #6f5baa;
    }

    .cancel-btn {
      background-color: #e0e0e0;
      color: #666;
      border: none;
      padding: 12px 24px;
      border-radius: 10px;
      cursor: pointer;
      font-weight: bold;
      transition: background 0.2s ease;
    }

    .cancel-btn:hover {
      background-color: #d0d0d0;
    }

    .progress-tracker {
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
    }

    .progress-dot {
      width: 12px;
      height: 12px;
      background-color: #ddd;
      border-radius: 50%;
      cursor: pointer;
    }

    .progress-dot.active {
      background-color: #8a75c9;
    }

    @media screen and (max-width: 768px) {
      .main {
        padding: 20px;
      }
      
      .submit-container {
        flex-direction: column;
        gap: 10px;
      }
      
      .submit-btn, .cancel-btn {
        width: 100%;
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
        <a class="nav-item" href="chatbotLearn.php"><i class="fas fa-comment"></i><span>Chatbot</span></a>
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
    <div class="quiz-header">
      <h2><?php echo htmlspecialchars($quizTitle); ?></h2>
      <p>Proficiency Level: <?php echo ucfirst(htmlspecialchars($proficiency)); ?></p>
      <p>Total Questions: <?php echo count($questions); ?></p>
    </div>

    <form action="submitQuiz.php" method="POST" id="quizForm">
      <input type="hidden" name="quizID" value="<?php echo $quizID; ?>">
      
      <div class="question-container">
        <div class="progress-tracker">
          <?php for ($i = 0; $i < count($questions); $i++): ?>
            <div class="progress-dot <?php echo ($i === 0) ? 'active' : ''; ?>" 
                 onclick="showQuestion(<?php echo $i; ?>)" 
                 id="dot-<?php echo $i; ?>"></div>
          <?php endfor; ?>
        </div>

        <?php foreach ($questions as $index => $q): ?>
          <div class="question" id="question-<?php echo $index; ?>" style="display: <?php echo ($index === 0) ? 'block' : 'none'; ?>">
            <div class="question-text">
              <span class="question-number"><?php echo $index + 1; ?>.</span> 
              <?php echo htmlspecialchars($q['question_text']); ?>
            </div>
            
            <div class="answer-options">
              <?php
              $questionID = $q['questionID'];
              $answerQuery = $conn->prepare("SELECT * FROM qanswer WHERE questionID = ?");
              $answerQuery->bind_param("i", $questionID);
              $answerQuery->execute();
              $answerResult = $answerQuery->get_result();
              while ($a = $answerResult->fetch_assoc()):
              ?>
                <div class="answer-option">
                  <input type="radio" 
                         id="answer-<?php echo $a['answerID']; ?>" 
                         name="question_<?php echo $questionID; ?>" 
                         value="<?php echo $a['answerID']; ?>" 
                         required
                         onchange="updateProgressDot(<?php echo $index; ?>)">
                  <label for="answer-<?php echo $a['answerID']; ?>"><?php echo htmlspecialchars($a['answer_text']); ?></label>
                </div>
              <?php endwhile; ?>
            </div>
            
            <div class="submit-container">
              <?php if ($index > 0): ?>
                <button type="button" class="cancel-btn" onclick="showQuestion(<?php echo $index - 1; ?>)">Previous</button>
              <?php else: ?>
                <button type="button" class="cancel-btn" onclick="window.location.href='quizSelect.php'">Cancel</button>
              <?php endif; ?>
              
              <?php if ($index < count($questions) - 1): ?>
                <button type="button" class="submit-btn" onclick="showQuestion(<?php echo $index + 1; ?>)">Next</button>
              <?php else: ?>
                <button type="submit" class="submit-btn">Submit Quiz</button>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </form>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('collapsed');
    }
    
    function showQuestion(index) {
      // Hide all questions
      const questions = document.querySelectorAll('.question');
      questions.forEach(q => q.style.display = 'none');
      
      // Show selected question
      document.getElementById('question-' + index).style.display = 'block';
      
      // Update active dot
      const dots = document.querySelectorAll('.progress-dot');
      dots.forEach(dot => dot.classList.remove('active'));
      document.getElementById('dot-' + index).classList.add('active');
      
      // Scroll to top of question
      window.scrollTo(0, 0);
    }
    
    function updateProgressDot(index) {
      document.getElementById('dot-' + index).style.backgroundColor = '#6fcf97';
    }
    
    // Form validation before submission
    document.getElementById('quizForm').addEventListener('submit', function(e) {
      const form = this;
      const questions = <?php echo count($questions); ?>;
      let allAnswered = true;
      
      for (let i = 0; i < questions; i++) {
        const questionInputs = form.querySelectorAll('#question-' + i + ' input[type="radio"]:checked');
        if (questionInputs.length === 0) {
          allAnswered = false;
          break;
        }
      }
      
      if (!allAnswered) {
        e.preventDefault();
        alert('Please answer all questions before submitting.');
        
        // Find the first unanswered question
        for (let i = 0; i < questions; i++) {
          const questionInputs = form.querySelectorAll('#question-' + i + ' input[type="radio"]:checked');
          if (questionInputs.length === 0) {
            showQuestion(i);
            break;
          }
        }
      }
    });
  </script>

</body>
</html>