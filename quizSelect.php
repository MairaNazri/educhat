<?php
session_start();
require 'server.php'; // mysqli connection

// Check if user is logged in
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'user') {
    header("Location: login.html");
    exit();
}

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
$proficiency = trim($user['proficiency_level']); // Remove extra whitespace

// Get all quizzes for this user's level (case-insensitive comparison)
$quizQuery = $conn->prepare("SELECT * FROM quiz WHERE TRIM(LOWER(proficiency_level)) = LOWER(?) ORDER BY quizID ASC");
$quizQuery->bind_param("s", $proficiency);
$quizQuery->execute();
$quizResult = $quizQuery->get_result();

$quizzes = [];
while ($row = $quizResult->fetch_assoc()) {
    $quizzes[] = $row;
}

// Get completed (and passed) quizzes
$completed = [];
$completedQuery = $conn->prepare("SELECT quizID FROM quizresult WHERE userID = ? AND passed = 1");
$completedQuery->bind_param("i", $userID);
$completedQuery->execute();
$completedResult = $completedQuery->get_result();

while ($row = $completedResult->fetch_assoc()) {
    $completed[] = $row['quizID'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Quiz Selection</title>
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
      color: #333;
    }

    .top-box p {
      color: #555;
      margin-bottom: 10px;
    }

    .quiz-container {
      background-color: #f5f2ff;
      padding: 20px;
      border-radius: 15px;
    }

    .quiz-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    .quiz-table th, .quiz-table td {
      padding: 15px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    .quiz-table th {
      background-color: #b187d6;
      color: white;
      font-weight: normal;
    }

    .quiz-table tr:hover {
      background-color: #eeebf7;
    }

    .quiz-table .locked {
      color: #aaa;
    }

    .quiz-status {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.9rem;
      display: inline-block;
      text-align: center;
      min-width: 100px;
    }

    .completed {
      background-color: #6fcf97;
      color: white;
    }

    .available {
      background-color: #8a75c9;
      color: white;
    }

    .locked {
      background-color: #e0e0e0;
      color: #888;
    }

    .start-btn {
      background-color: #8a75c9;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: background 0.2s ease;
    }

    .start-btn:hover {
      background-color: #6f5baa;
    }

    .start-btn.disabled {
      background-color: #e0e0e0;
      color: #888;
      cursor: not-allowed;
    }



    .no-quizzes {
      text-align: center;
      padding: 40px;
      color: #666;
      font-style: italic;
    }

    @media screen and (max-width: 768px) {
      .main {
        padding: 20px;
      }
      
      .quiz-table th, .quiz-table td {
        padding: 10px;
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
        <a class="nav-item active"><i class="fas fa-clipboard"></i><span>Quiz</span></a>
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
      <h2>Quiz Selection - <?php echo ucfirst(htmlspecialchars($proficiency)); ?> Level</h2>
      <p>Complete quizzes in order to unlock the next ones. Each quiz helps you practice and improve your skills.</p>
    </div>

    <div class="quiz-container">
      <h3 style="margin-bottom: 20px; color: #333;">Available Quizzes</h3>
      
      <?php if (empty($quizzes)): ?>
        <div class="no-quizzes">
          <p>No quizzes found for your proficiency level: <strong><?php echo htmlspecialchars($proficiency); ?></strong></p>
          <p>Please contact an administrator to add quizzes for your level.</p>
        </div>
      <?php else: ?>
        <table class="quiz-table">
          <thead>
            <tr>
              <th style="width: 50%">Quiz Title</th>
              <th style="width: 20%">Status</th>
              <th style="width: 30%">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $unlocked = true;
            foreach ($quizzes as $quiz) {
                $quizID = $quiz['quizID'];
                $title = htmlspecialchars($quiz['title']);
                $isCompleted = in_array($quizID, $completed);
                
                echo "<tr>";
                echo "<td>" . $title . "</td>";
                
                if ($isCompleted) {
                    echo "<td><span class='quiz-status completed'>Completed</span></td>";
                    echo "<td><a href='takeQuiz.php?quizID={$quizID}'><button class='start-btn'>Retake</button></a></td>";
                } elseif ($unlocked) {
                    echo "<td><span class='quiz-status available'>Available</span></td>";
                    echo "<td><a href='takeQuiz.php?quizID={$quizID}'><button class='start-btn'>Start</button></a></td>";
                } else {
                    echo "<td><span class='quiz-status locked'>Locked</span></td>";
                    echo "<td><button class='start-btn disabled'>Locked</button></td>";
                }
                
                echo "</tr>";
                
                if (!$isCompleted) {
                    $unlocked = false;
                }
            }
            ?>
          </tbody>
        </table>
      <?php endif; ?>
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