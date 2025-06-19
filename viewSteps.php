<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}
include 'server.php';

if (!isset($_GET['topicID'])) {
    echo "No topic selected.";
    exit;
}

$topicID = intval($_GET['topicID']);

// Handle delete (moved to top to avoid fetch errors after deletion)
if (isset($_GET['deleteStep'])) {
    $deleteID = intval($_GET['deleteStep']);

    // Nullify next_stepID references before deletion
    $conn->query("UPDATE chatbot_step SET next_stepID = NULL WHERE next_stepID = $deleteID");

    // Delete the step
    $conn->query("DELETE FROM chatbot_step WHERE stepID = $deleteID");

    header("Location: viewSteps.php?topicID=$topicID");
    exit;
}

// Fetch topic details
$topic = $conn->query("SELECT * FROM chatbot_topic WHERE topicID = $topicID")->fetch_assoc();
$steps = $conn->query("SELECT * FROM chatbot_step WHERE topicID = $topicID ORDER BY step_number");

// Get all steps for auto-populating next step options
$allSteps = $conn->query("SELECT stepID, step_number FROM chatbot_step WHERE topicID = $topicID ORDER BY step_number");
$stepOptions = [];
while ($stepOption = $allSteps->fetch_assoc()) {
    $stepOptions[] = $stepOption;
}

// Get the highest step number for auto-generating next step number
$maxStepResult = $conn->query("SELECT MAX(step_number) as max_step FROM chatbot_step WHERE topicID = $topicID");
$maxStep = $maxStepResult->fetch_assoc()['max_step'] ?? 0;
$nextStepNumber = $maxStep + 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Chatbot Steps - <?= htmlspecialchars($topic['title']) ?></title>
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

    .topic-info {
      background-color: #d4c5f9;
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 20px;
      border-left: 4px solid #6c52a1;
    }

    .topic-info h3 {
      color: #6c52a1;
      margin-bottom: 5px;
    }

    .topic-info p {
      color: #666;
      margin: 0;
    }

    .action-buttons {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }

    .btn {
      padding: 10px 15px;
      border-radius: 6px;
      text-decoration: none;
      font-size: 1rem;
      cursor: pointer;
      border: none;
      transition: background-color 0.3s ease;
    }

    .btn-add {
      background-color: #28a745;
      color: white;
    }

    .btn-add:hover {
      background-color: #218838;
    }

    .btn-back {
      background-color: #6c52a1;
      color: white;
    }

    .btn-back:hover {
      background-color: #5a4287;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #eee;
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
      background-color: #f8f6ff;
    }

    .action-link {
      color: #6c52a1;
      margin: 0 5px;
      text-decoration: none;
      font-size: 0.9rem;
      padding: 4px 8px;
      border-radius: 4px;
      transition: background-color 0.3s ease;
    }

    .action-link:hover {
      background-color: #e8dcfa;
    }

    .action-link.edit-link {
      color: #ffa500;
    }

    .action-link.delete-link {
      color: #dc143c;
    }

    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 1000;
    }

    .modal-content {
      background: white;
      margin: 5% auto;
      padding: 30px;
      border-radius: 15px;
      width: 90%;
      max-width: 600px;
      max-height: 80vh;
      overflow-y: auto;
    }

    .modal-content h3 {
      color: #6c52a1;
      margin-bottom: 20px;
      font-size: 1.5rem;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      color: #6c52a1;
      font-weight: 500;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
    }

    .form-group textarea {
      resize: vertical;
      min-height: 80px;
    }

    .modal-buttons {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      margin-top: 20px;
    }

    .btn-edit {
      background-color: #ffa500;
      color: white;
    }

    .btn-edit:hover {
      background-color: #e69500;
    }

    .btn-cancel {
      background-color: #6c757d;
      color: white;
    }

    .btn-cancel:hover {
      background-color: #5a6268;
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

    .empty-state {
      text-align: center;
      padding: 40px;
      color: #666;
    }

    .empty-state i {
      font-size: 3rem;
      color: #ccc;
      margin-bottom: 15px;
    }

    .end-badge {
      background-color: #dc3545;
      color: white;
      padding: 2px 8px;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: bold;
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
        <h1>Manage Chatbot Steps</h1>
      </div>
      <div class="user-info">
        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <div class="user-avatar"></div>
      </div>
    </div>

    <div class="content">
      <div class="topic-info">
        <h3><i class="fas fa-book"></i> <?= htmlspecialchars($topic['title']) ?></h3>
        <p><strong>Proficiency Level:</strong> <?= htmlspecialchars($topic['proficiency_level']) ?></p>
        <p><strong>Created:</strong> <?= $topic['created_at'] ?></p>
      </div>

      <div class="action-buttons">
        <a class="btn btn-back" href="manageChatbot.php">
          <i class="fas fa-arrow-left"></i> Back to Topics
        </a>
        <button class="btn btn-add" onclick="openModal('addModal')">
          <i class="fas fa-plus"></i> Add Step
        </button>
      </div>

      <?php if ($steps->num_rows > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Step #</th>
              <th>Prompt</th>
              <th>Expected Pattern</th>
              <th>Pattern Type</th>
              <th>Next Step</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            // Reset the result pointer to fetch data again
            $steps->data_seek(0);
            while ($step = $steps->fetch_assoc()): 
            ?>
              <tr>
                <td><?= $step['step_number'] ?></td>
                <td><?= htmlspecialchars(substr($step['prompt'], 0, 50)) ?><?= strlen($step['prompt']) > 50 ? '...' : '' ?></td>
                <td><?= htmlspecialchars(substr($step['expected_pattern'], 0, 30)) ?><?= strlen($step['expected_pattern']) > 30 ? '...' : '' ?></td>
                <td><span class="badge"><?= $step['pattern_type'] ?></span></td>
                <td>
                  <?php if ($step['next_stepID']): ?>
                    <?= $step['next_stepID'] ?>
                  <?php else: ?>
                    <span class="end-badge">END</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a class="action-link edit-link" href="#" onclick="openModal('editModal<?= $step['stepID'] ?>')">
                    <i class="fas fa-edit"></i> Edit
                  </a>
                  <a class="action-link delete-link" href="?topicID=<?= $topicID ?>&deleteStep=<?= $step['stepID'] ?>" onclick="return confirm('Delete this step?')">
                    <i class="fas fa-trash"></i> Delete
                  </a>
                </td>
              </tr>

              <!-- Edit Modal -->
              <div class="modal" id="editModal<?= $step['stepID'] ?>">
                <div class="modal-content">
                  <form method="POST" action="updateSteps.php">
                    <h3><i class="fas fa-edit"></i> Edit Step #<?= $step['step_number'] ?></h3>
                    <input type="hidden" name="stepID" value="<?= $step['stepID'] ?>">
                    <input type="hidden" name="topicID" value="<?= $topicID ?>">
                    
                    <div class="form-group">
                      <label>Step Number:</label>
                      <input type="number" name="step_number" value="<?= $step['step_number'] ?>" required>
                    </div>

                    <div class="form-group">
                      <label>Prompt:</label>
                      <textarea name="prompt" required><?= htmlspecialchars($step['prompt']) ?></textarea>
                    </div>

                    <div class="form-group">
                      <label>Expected Pattern:</label>
                      <input type="text" name="expected_pattern" value="<?= htmlspecialchars($step['expected_pattern']) ?>" required>
                    </div>

                    <div class="form-group">
                      <label>Pattern Type:</label>
                      <select name="pattern_type">
                        <option value="exact" <?= $step['pattern_type'] == 'exact' ? 'selected' : '' ?>>Exact</option>
                        <option value="keyword" <?= $step['pattern_type'] == 'keyword' ? 'selected' : '' ?>>Keyword</option>
                        <option value="regex" <?= $step['pattern_type'] == 'regex' ? 'selected' : '' ?>>Regex</option>
                      </select>
                    </div>

                    <div class="form-group">
                      <label>Correct Feedback:</label>
                      <input type="text" name="correct_feedback" value="<?= htmlspecialchars($step['correct_feedback']) ?>">
                    </div>

                    <div class="form-group">
                      <label>Wrong Feedback:</label>
                      <input type="text" name="wrong_feedback" value="<?= htmlspecialchars($step['wrong_feedback']) ?>">
                    </div>

                    <div class="form-group">
                      <label>Next Step:</label>
                      <select name="next_stepID" id="editNextStep<?= $step['stepID'] ?>">
                        <option value="">-- End of conversation --</option>
                        <?php foreach ($stepOptions as $option): ?>
                          <?php if ($option['stepID'] != $step['stepID']): // Don't allow self-reference ?>
                            <option value="<?= $option['stepID'] ?>" <?= $step['next_stepID'] == $option['stepID'] ? 'selected' : '' ?>>
                              Step <?= $option['step_number'] ?> (ID: <?= $option['stepID'] ?>)
                            </option>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="modal-buttons">
                      <button class="btn btn-edit" type="submit">
                        <i class="fas fa-save"></i> Save Changes
                      </button>
                      <button type="button" class="btn btn-cancel" onclick="closeModal('editModal<?= $step['stepID'] ?>')">
                        <i class="fas fa-times"></i> Cancel
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-comments"></i>
          <h3>No Steps Found</h3>
          <p>This topic doesn't have any steps yet. Click "Add Step" to create the first one.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Add Step Modal -->
  <div class="modal" id="addModal">
    <div class="modal-content">
      <form method="POST" action="addSteps.php">
        <h3><i class="fas fa-plus"></i> Add New Step</h3>
        <input type="hidden" name="topicID" value="<?= $topicID ?>">

        <div class="form-group">
          <label>Step Number:</label>
          <input type="number" name="step_number" value="<?= $nextStepNumber ?>" required>
        </div>

        <div class="form-group">
          <label>Prompt:</label>
          <textarea name="prompt" required placeholder="Enter the step prompt or question..."></textarea>
        </div>

        <div class="form-group">
          <label>Expected Pattern:</label>
          <input type="text" name="expected_pattern" required placeholder="Enter the expected answer pattern...">
        </div>

        <div class="form-group">
          <label>Pattern Type:</label>
          <select name="pattern_type">
            <option value="exact">Exact</option>
            <option value="keyword">Keyword</option>
            <option value="regex">Regex</option>
          </select>
        </div>

        <div class="form-group">
          <label>Correct Feedback:</label>
          <input type="text" name="correct_feedback" placeholder="Feedback when answer is correct...">
        </div>

        <div class="form-group">
          <label>Wrong Feedback:</label>
          <input type="text" name="wrong_feedback" placeholder="Feedback when answer is wrong...">
        </div>

        <div class="form-group">
          <label>Next Step:</label>
          <select name="next_stepID" id="addNextStep">
            <option value="">-- End of conversation --</option>
            <?php foreach ($stepOptions as $option): ?>
              <option value="<?= $option['stepID'] ?>">
                Step <?= $option['step_number'] ?> (ID: <?= $option['stepID'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="modal-buttons">
          <button class="btn btn-add" type="submit">
            <i class="fas fa-plus"></i> Add Step
          </button>
          <button type="button" class="btn btn-cancel" onclick="closeModal('addModal')">
            <i class="fas fa-times"></i> Cancel
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      sidebar.classList.toggle("collapsed");
    }

    function openModal(id) {
      document.getElementById(id).style.display = 'block';
    }

    function closeModal(id) {
      document.getElementById(id).style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
      }
    }
  </script>
</body>
</html>