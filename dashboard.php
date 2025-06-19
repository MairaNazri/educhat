<?php
session_start();
include 'server.php'; // Include database connection

// Check if user is logged in and has correct role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// Get user ID from session
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];

// Fetch user data
$userQuery = "SELECT * FROM user WHERE userID = $userID";
$userResult = $conn->query($userQuery);
$user = $userResult->fetch_assoc();

// Calculate learning statistics
$quizCount = $conn->query("SELECT COUNT(*) AS total FROM quizresult WHERE userID = $userID")->fetch_assoc()['total'];
$chatCount = $conn->query("SELECT COUNT(*) AS total FROM chatbot_interaction WHERE userID = $userID")->fetch_assoc()['total'];

// Calculate average quiz score
$avgScoreQuery = "SELECT AVG(score) AS avg_score FROM quizresult WHERE userID = $userID";
$avgScoreResult = $conn->query($avgScoreQuery);
$avgScore = $avgScoreResult->fetch_assoc()['avg_score'] ?? 0;

// Get recent quiz results for progress calculation
$recentQuizQuery = "SELECT score FROM quizresult WHERE userID = $userID ORDER BY quiz_date DESC LIMIT 5";
$recentScores = [];
$recentQuizQuery = "SELECT score FROM quizresult WHERE userID = $userID ORDER BY completed_date DESC LIMIT 5";
$recentQuizResult = $conn->query($recentQuizQuery);

if ($recentQuizResult) {
    while ($row = $recentQuizResult->fetch_assoc()) {
        $recentScores[] = $row['score'];
    }
} else {
    echo "<p style='color:red;'>Query Error: " . $conn->error . "</p>";
}


// Calculate progress percentage (based on average score)
$progressPercentage = min(100, max(0, $avgScore));

// Calculate learning streak (simplified - count consecutive days with activity)
$streakQuery = "SELECT DISTINCT DATE(completed_date) as activity_date FROM quizresult WHERE userID = $userID 
                UNION 
                SELECT DISTINCT DATE(timestamp) as activity_date FROM chatbot_interaction WHERE userID = $userID 
                ORDER BY activity_date DESC LIMIT 30";
$streakResult = $conn->query($streakQuery);
$activityDates = [];
while ($row = $streakResult->fetch_assoc()) {
    $activityDates[] = $row['activity_date'];
}

// Calculate streak
$streak = 0;
$currentDate = new DateTime();
for ($i = 0; $i < count($activityDates); $i++) {
    $activityDate = new DateTime($activityDates[$i]);
    $daysDiff = $currentDate->diff($activityDate)->days;
    
    if ($daysDiff == $i) {
        $streak++;
    } else {
        break;
    }
}

// Get pending flashcards count (assuming you have a flashcards table)
$flashcardQuery = "SELECT COUNT(*) AS pending FROM flashcard WHERE userID = $userID";
$flashcardResult = $conn->query($flashcardQuery);
$pendingFlashcards = $flashcardResult ? $flashcardResult->fetch_assoc()['pending'] : 5; // Default to 5 if table doesn't exist

// Get available quiz questions count (assuming you have a questions table)
$questionQuery = "SELECT COUNT(*) AS total FROM question";
$questionResult = $conn->query($questionQuery);
$availableQuestions = $questionResult ? $questionResult->fetch_assoc()['total'] : 10; // Default to 10 if table doesn't exist
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Dashboard</title>
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

    .top-box {
      background-color: #f5f2ff;
      padding: 20px;
      border-radius: 15px;
      margin-bottom: 30px;
    }

    .top-box h2 {
      font-size: 1.5rem;
      margin-bottom: 10px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px;
      margin: 15px 0;
    }

    .stat-item {
      background-color: white;
      padding: 15px;
      border-radius: 10px;
      text-align: center;
    }

    .stat-number {
      font-size: 1.5rem;
      font-weight: bold;
      color: #6e50a1;
    }

    .stat-label {
      font-size: 0.9rem;
      color: #666;
      margin-top: 5px;
    }

    .progress {
      background-color: #ddd;
      border-radius: 20px;
      overflow: hidden;
      height: 10px;
      margin: 10px 0;
    }

    .progress-bar {
      height: 100%;
      background-color: #6fcf97;
      transition: width 0.3s ease;
    }

    .suggested-box {
      background-color: #f5f2ff;
      padding: 20px;
      border-radius: 15px;
    }

    .suggested-box h3 {
      font-size: 1.3rem;
      margin-bottom: 20px;
    }

    .suggested-item {
      border: 1px solid #aaa;
      padding: 15px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 15px;
    }

    .suggested-item i {
      font-size: 1.5rem;
      margin-right: 15px;
      color: #333;
    }

    .suggested-info {
      display: flex;
      flex-direction: column;
    }

    .suggested-info span:first-child {
      font-weight: bold;
    }

    .start-btn {
      background-color: #8a75c9;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 10px;
      cursor: pointer;
      font-weight: bold;
    }

    .start-btn:hover {
      background-color: #6f5baa;
    }

    @media screen and (max-width: 768px) {
      .main {
        padding: 20px;
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
        gap: 10px;
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
        <a class="nav-item active"><i class="fas fa-home"></i><span>Dashboard</span></a>
        <a class="nav-item" href="chatbotTopic.php"><i class="fas fa-comment"></i><span>Chatbot</span></a>
        <a class="nav-item" href="quizSelect.php"><i class="fas fa-clipboard"></i><span>Quiz</span></a>
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
    <div class="top-box">
      <h2>Welcome Back <?php echo htmlspecialchars($user['name'] ?? $user['username']); ?></h2>
      <p>Today's Learning Goal</p>
      
      <!-- Learning Statistics -->
      <div class="stats-grid">
        <div class="stat-item">
          <div class="stat-number"><?php echo $quizCount; ?></div>
          <div class="stat-label">Quizzes Completed</div>
        </div>
        <div class="stat-item">
          <div class="stat-number"><?php echo $chatCount; ?></div>
          <div class="stat-label">Chat Sessions</div>
        </div>
        <div class="stat-item">
          <div class="stat-number"><?php echo number_format($avgScore, 1); ?>%</div>
          <div class="stat-label">Average Score</div>
        </div>
      </div>
      
      <!-- Progress Bar -->
      <div class="progress">
        <div class="progress-bar" style="width: <?php echo $progressPercentage; ?>%"></div>
      </div>
      <p>Learning Streak: <?php echo $streak; ?> Days | Level: <?php echo ucfirst($user['proficiency_level'] ?? 'Beginner'); ?></p>
    </div>

    <div class="suggested-box">
      <h3>Suggested Next Steps</h3>

      <!-- Flashcard -->
      <div class="suggested-item">
        <div style="display: flex; align-items: center;">
          <i class="fas fa-clone"></i>
          <div class="suggested-info">
            <span>Review Flashcards</span>
            <span><?php echo $pendingFlashcards; ?> cards due for review</span>
          </div>
        </div>
        <a href="flashcardDash.php"><button class="start-btn">Start</button></a>
      </div>

      <!-- Practice Quiz -->
      <div class="suggested-item">
        <div style="display: flex; align-items: center;">
          <i class="fas fa-clipboard"></i>
          <div class="suggested-info">
            <span>Practice Quiz</span>
            <span><?php echo $availableQuestions; ?> questions available</span>
          </div>
        </div>
        <a href="quizSelect.php"><button class="start-btn">Start</button></a>
      </div>

      <!-- Chatbot Practice -->
      <div class="suggested-item">
        <div style="display: flex; align-items: center;">
          <i class="fas fa-comment"></i>
          <div class="suggested-info">
            <span>Chatbot Practice</span>
            <span>Practice conversations in <?php echo ucfirst($user['proficiency_level'] ?? 'Beginner'); ?> level</span>
          </div>
        </div>
        <a href="chatbotTopic.php"><button class="start-btn">Start</button></a>
      </div>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('collapsed');
    }
  </script>

</body>
</html>