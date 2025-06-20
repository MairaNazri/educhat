<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Include server connection file (same as manageUser.php)
include 'server.php';

// Check if connection exists and is working
if (!isset($conn)) {
    die("Database connection not established. Check server.php file.");
}

// Initialize variables with default values
$totalUsers = 0;
$activeUsers = 0;
$totalQuizzes = 0;
$completedQuizzes = 0;

// Fetch stats with error handling
try {
    // Total users (non-admin)
    $result = $conn->query("SELECT COUNT(*) as total FROM user WHERE role != 'admin'");
    if ($result) {
        $totalUsers = $result->fetch_assoc()['total'];
    }

    // Active users (users who have taken at least one quiz)
    $result = $conn->query("SELECT COUNT(DISTINCT userID) as total FROM quizresult");
    if ($result) {
        $activeUsers = $result->fetch_assoc()['total'];
    }

    // Total quizzes
    $result = $conn->query("SELECT COUNT(*) as total FROM quiz");
    if ($result) {
        $totalQuizzes = $result->fetch_assoc()['total'];
    }

    // Completed quizzes
    $result = $conn->query("SELECT COUNT(*) as total FROM quizresult");
    if ($result) {
        $completedQuizzes = $result->fetch_assoc()['total'];
    }
} catch (Exception $e) {
    // Log error or handle it appropriately
    error_log("Database query error in adminDash.php: " . $e->getMessage());
}

// Don't close connection here as it might be used elsewhere
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* ✅ EXACT same styling as your HTML version */
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
      margin-bottom: 40px;
    }

    .header h1 {
      font-size: 1.8rem;
      color: #6c52a1;
      display: inline-block;
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

    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }

    .card {
      background-color: #e8dcfa;
      padding: 20px;
      border-radius: 15px;
      color: #6c52a1;
    }

    .card h3 {
      font-size: 1rem;
      margin-bottom: 8px;
    }

    .card p {
      font-size: 2rem;
      font-weight: bold;
    }

    .chart-container {
      background-color: #e8dcfa;
      padding: 20px;
      border-radius: 15px;
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

    .debug-info {
      background-color: #fff3cd;
      border: 1px solid #ffeaa7;
      padding: 10px;
      margin-bottom: 20px;
      border-radius: 5px;
      color: #856404;
      display: none; /* Hidden by default */
    }
  </style>
</head>
<body>

  <div class="sidebar" id="sidebar">
    <h2>EduChat<br>Admin</h2>

    <div class="nav-link active" data-tooltip="Dashboard">
      <i class="fas fa-home"></i> <span>Dashboard</span>
    </div>

    <div class="nav-link" onclick="location.href='manageQuiz.php'" data-tooltip="Manage Quizzes">
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
        <h1>Admin Dashboard</h1>
      </div>
      <div class="user-info">
        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <div class="user-avatar"></div>
      </div>
    </div>

    <!-- Debug info (hidden by default) -->
    <div class="debug-info" id="debugInfo">
      <strong>Debug Info:</strong><br>
      Database connection: <?php echo isset($conn) ? 'Connected' : 'Not connected'; ?><br>
      Total Users: <?php echo $totalUsers; ?><br>
      Active Users: <?php echo $activeUsers; ?><br>
      Total Quizzes: <?php echo $totalQuizzes; ?><br>
      Completed Quizzes: <?php echo $completedQuizzes; ?>
    </div>

    <div class="cards">
      <div class="card">
        <h3>Total Users</h3>
        <p><?php echo $totalUsers; ?></p>
      </div>
      <div class="card">
        <h3>Active Users</h3>
        <p><?php echo $activeUsers; ?></p>
      </div>
      <div class="card">
        <h3>Total Quizzes</h3>
        <p><?php echo $totalQuizzes; ?></p>
      </div>
      <div class="card">
        <h3>Completed Quizzes</h3>
        <p><?php echo $completedQuizzes; ?></p>
      </div>
    </div>

    <div class="chart-container">
      <canvas id="quizChart"></canvas>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      sidebar.classList.toggle("collapsed");
    }

    // Toggle debug info (for troubleshooting)
    function toggleDebugInfo() {
      const debugInfo = document.getElementById("debugInfo");
      debugInfo.style.display = debugInfo.style.display === 'none' ? 'block' : 'none';
    }

    // Add keyboard shortcut for debug info (Ctrl+Shift+D)
    document.addEventListener('keydown', function(e) {
      if (e.ctrlKey && e.shiftKey && e.key === 'D') {
        toggleDebugInfo();
      }
    });

    const ctx = document.getElementById('quizChart').getContext('2d');
    const quizChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Total Users', 'Active Users', 'Total Quizzes', 'Completed Quizzes'],
        datasets: [{
          label: 'Dashboard Stats',
          data: [<?php echo "$totalUsers, $activeUsers, $totalQuizzes, $completedQuizzes"; ?>],
          backgroundColor: ['#6c52a1', '#8f75b5', '#a98bd3', '#bda1e8'],
          borderRadius: 10,
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  </script>

</body>
</html>