<?php
session_start();
require_once 'server.php'; // make sure this exists and connects to DB as $conn

// Redirect to login if not logged in
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vocabulary = $_POST["vocabulary"];
    $meaning = $_POST["meaning"];
    $example = $_POST["example"];
    $userID = $_SESSION['userID'];

    $stmt = $conn->prepare("INSERT INTO flashcard (userID, vocabulary, meaning, example, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $userID, $vocabulary, $meaning, $example);

    if ($stmt->execute()) {
        header("Location: flashcardDash.php?success=1");
        exit();
    } else {
        $error = "Error adding flashcard. Please try again.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Add Flashcard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* All your existing CSS is copied below without changes */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
    body { display: flex; height: 100vh; background-color: #f4eaff; overflow-x: hidden; }
    .sidebar { width: 240px; background-color: #b187d6; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; border-top-right-radius: 20px; border-bottom-right-radius: 20px; transition: width 0.3s ease; position: fixed; top: 0; left: 0; height: 100vh; z-index: 10; }
    .sidebar.collapsed { width: 70px; }
    .sidebar .logo { font-size: 1.8rem; font-weight: bold; background-color: black; padding: 10px; border-radius: 5px; text-align: center; color: white; margin-bottom: 20px; }
    .toggle-btn { color: white; cursor: pointer; font-size: 1.2rem; text-align: right; margin-bottom: 20px; }
    .nav-section { display: flex; flex-direction: column; gap: 25px; }
    .nav-item { display: flex; align-items: center; gap: 12px; font-size: 1rem; padding: 10px; border-radius: 8px; cursor: pointer; color: white; transition: background 0.2s ease; }
    .nav-item:hover, .nav-item.active { background-color: #6e50a1; }
    .nav-item i { min-width: 20px; text-align: center; }
    .logout-btn { background-color: white; color: #6e50a1; font-weight: bold; border: none; padding: 10px; border-radius: 10px; cursor: pointer; margin-top: 30px; transition: 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 8px; }
    .logout-btn:hover { background-color: #f3e6ff; }
    .collapsed .nav-item span, .collapsed .logo, .collapsed .logout-btn span { display: none; }
    .collapsed .nav-item, .collapsed .logout-btn { justify-content: center; }
    .collapsed .logout-btn { padding: 10px 0; }
    .main-content { flex: 1; margin-left: 240px; display: flex; justify-content: center; align-items: center; height: 100vh; transition: margin-left 0.3s ease; }
    .sidebar.collapsed ~ .main-content { margin-left: 70px; }
    .modal { background-color: white; padding: 30px 40px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); width: 400px; }
    .modal h2 { text-align: center; margin-bottom: 25px; font-size: 1.6rem; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 8px; }
    .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; font-size: 14px; resize: vertical; }
    .button-group { display: flex; justify-content: space-between; margin-top: 25px; }
    .button-group button { padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; font-weight: bold; }
    .cancel-btn { background-color: #e0e0f0; color: #333; border: 2px solid #a7a7cc; }
    .cancel-btn:hover { background-color: #d2d2f0; }
    .add-btn { background-color: #a241f1; color: white; }
    .add-btn:hover { background-color: #8e2ed0; }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div>
      <div class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></div>
      <div class="logo">EDUCHAT</div>
      <div class="nav-section">
        <div class="nav-item"><i class="fas fa-home"></i><span>Dashboard</span></div>
        <div class="nav-item"><i class="fas fa-comments"></i><span>Chatbot</span></div>
        <div class="nav-item"><i class="fas fa-clipboard-list"></i><span>Quiz</span></div>
        <div class="nav-item active"><i class="fas fa-clone"></i><span>Flashcards</span></div>
        <div class="nav-item"><i class="fas fa-user"></i><span>Profile</span></div>
      </div>
    </div>
    <button class="logout-btn" onclick="confirmLogout()">
      <i class="fas fa-sign-out-alt"></i><span>Logout</span>
    </button>
  </div>

  <!-- Main Content -->
  <div class="main-content" id="mainContent">
    <div class="modal">
      <h2>Add New Flashcard</h2>
      <?php if (!empty($error)): ?>
        <p style="color:red; text-align:center;"><?= $error ?></p>
      <?php endif; ?>
      <form id="flashcardForm" method="POST" action="">
        <div class="form-group">
          <label for="word">Word</label>
          <input type="text" id="vocabulary" name="vocabulary" required />
        </div>
        <div class="form-group">
          <label for="meaning">Meaning</label>
          <textarea id="meaning" name="meaning" rows="3" required></textarea>
        </div>
        <div class="form-group">
          <label for="example">Example Sentence</label>
          <textarea id="example" name="example" rows="3"></textarea>
        </div>
        <div class="button-group">
          <button type="button" class="cancel-btn" onclick="handleCancel()">Cancel</button>
          <button type="submit" class="add-btn">Add Flashcard</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function confirmLogout() {
      if (confirm("Are you sure you want to logout?")) {
        window.location.href = "login.php";
      }
    }

    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      const main = document.getElementById("mainContent");
      sidebar.classList.toggle("collapsed");
    }

    function handleCancel() {
      if (confirm("Cancel adding flashcard?")) {
        window.location.href = "flashcardDash.php";
      }
    }
  </script>

</body>
</html>
