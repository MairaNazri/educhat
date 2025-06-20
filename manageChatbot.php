<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'server.php';

// Handle topic deletion
if (isset($_GET['delete'])) {
    $deleteID = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM chatbot_topic WHERE topicID = ?");
    $stmt->bind_param("i", $deleteID);
    $stmt->execute();
    header("Location: manageChatbot.php");
    exit();
}

// Filter logic
$filter_level = isset($_GET['level']) ? $_GET['level'] : '';

$sql = "SELECT * FROM chatbot_topic";
if (!empty($filter_level)) {
    $sql .= " WHERE proficiency_level = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $filter_level);
} else {
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Chatbot Topics</title>
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
      padding: 20px;
    }

    form {
      margin-bottom: 20px;
    }

    label, select, button {
      font-size: 1rem;
    }

    select, button {
      padding: 8px 12px;
      border-radius: 6px;
      border: 1px solid #aaa;
      margin-left: 8px;
    }

    .add-button {
      display: inline-block;
      margin-bottom: 20px;
      padding: 10px 15px;
      background-color: #28a745;
      color: white;
      text-decoration: none;
      border-radius: 6px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: white;
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

    a.action-link {
      color: #6c52a1;
      margin: 0 5px;
      text-decoration: none;
    }

    .action-link.view-link {
      color: #4b0082;
    }

    .action-link.delete-link {
      color: #dc143c;
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

    <div class="nav-link" onclick="location.href='manageQuiz.php'" data-tooltip="Manage Quizzes">
      <i class="fas fa-clipboard-list"></i> <span>Manage Quizzes</span>
    </div>

    <div class="nav-link active" data-tooltip="Manage Chatbot">
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
        <h1>Manage Chatbot Topics</h1>
      </div>
      <div class="user-info">
        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <div class="user-avatar"></div>
      </div>
    </div>

    <div class="content">
      <form method="get">
        <label for="level">Filter by Proficiency Level:</label>
        <select name="level" id="level">
          <option value="">All</option>
          <option value="Beginner" <?= $filter_level == 'Beginner' ? 'selected' : '' ?>>Beginner</option>
          <option value="Intermediate" <?= $filter_level == 'Intermediate' ? 'selected' : '' ?>>Intermediate</option>
          <option value="Advanced" <?= $filter_level == 'Advanced' ? 'selected' : '' ?>>Advanced</option>
        </select>
        <button type="submit">Filter</button>
      </form>

      <a class="add-button" href="addChatbotTopic.php">➕ Add New Topic</a>

      <table>
        <thead>
          <tr>
            <th>Topic ID</th>
            <th>Title</th>
            <th>Proficiency Level</th>
            <th>Created At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($topic = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $topic['topicID'] ?></td>
              <td><?= htmlspecialchars($topic['title']) ?></td>
              <td><?= htmlspecialchars($topic['proficiency_level']) ?></td>
              <td><?= $topic['created_at'] ?></td>
              <td>
                <a class="action-link view-link" href="viewSteps.php?topicID=<?= $topic['topicID'] ?>">View Steps</a> |
                <a class="action-link delete-link" href="?delete=<?= $topic['topicID'] ?>" onclick="return confirm('Delete this topic and all its steps?');">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
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