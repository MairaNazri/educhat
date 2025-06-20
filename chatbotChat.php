<?php
session_start();
require 'server.php'; // Your DB connection

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$userID = $_SESSION['userID'];
$topicID = $_GET['topicID'] ?? null;

if (!$topicID) {
    die("Topic not specified.");
}

// RETAKE logic
if (isset($_GET['retake']) && $_GET['retake'] == 1) {
    $stmt = $conn->prepare("DELETE FROM chatbot_interaction WHERE userID = ? AND stepID IN (SELECT stepID FROM chatbot_step WHERE topicID = ?)");
    $stmt->bind_param("ii", $userID, $topicID);
    $stmt->execute();

    $stmt = $conn->prepare("DELETE FROM chatbot_user_progress WHERE userID = ? AND topicID = ?");
    $stmt->bind_param("ii", $userID, $topicID);
    $stmt->execute();

    header("Location: chatbotChat.php?topicID=$topicID");
    exit();
}

// Get topic title
$stmt = $conn->prepare("SELECT title FROM chatbot_topic WHERE topicID = ?");
$stmt->bind_param("i", $topicID);
$stmt->execute();
$stmt->bind_result($topicTitle);
$stmt->fetch();
$stmt->close();

// Handle POST (user response)
// Handle POST (user response)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_response'])) {
    $userResponse = trim($_POST['user_response']);

    $stmt = $conn->prepare("SELECT current_stepID FROM chatbot_user_progress WHERE userID = ? AND topicID = ?");
    $stmt->bind_param("ii", $userID, $topicID);
    $stmt->execute();
    $stmt->bind_result($currentStepID);
    $stmt->fetch();
    $stmt->close();

    if (!$currentStepID) {
        $stmt = $conn->prepare("SELECT stepID FROM chatbot_step WHERE topicID = ? ORDER BY step_number ASC LIMIT 1");
        $stmt->bind_param("i", $topicID);
        $stmt->execute();
        $stmt->bind_result($currentStepID);
        $stmt->fetch();
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT expected_pattern, correct_feedback, wrong_feedback, next_stepID FROM chatbot_step WHERE stepID = ?");
    $stmt->bind_param("i", $currentStepID);
    $stmt->execute();
    $stmt->bind_result($expectedPattern, $correctFeedback, $wrongFeedback, $nextStepID);
    $stmt->fetch();
    $stmt->close();

    $isCorrect = preg_match("/$expectedPattern/i", $userResponse) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO chatbot_interaction (userID, stepID, user_response, is_correct) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisi", $userID, $currentStepID, $userResponse, $isCorrect);
    $stmt->execute();
    $stmt->close();

    // Store in session to display after reload
    $_SESSION['last_feedback'] = [
        'stepID' => $currentStepID,
        'user_response' => $userResponse,
        'is_correct' => $isCorrect,
        'correct_feedback' => $correctFeedback,
        'wrong_feedback' => $wrongFeedback
    ];

    $stmt = $conn->prepare("SELECT prompt FROM chatbot_step WHERE stepID = ?");
    $stmt->bind_param("i", $currentStepID);
    $stmt->execute();
    $stmt->bind_result($prompt);
    $stmt->fetch();
    $stmt->close();
    $_SESSION['last_feedback']['prompt'] = $prompt;

    if ($nextStepID) {
        $stmt = $conn->prepare("INSERT INTO chatbot_user_progress (userID, topicID, current_stepID, is_completed, last_updated)
            VALUES (?, ?, ?, 0, NOW())
            ON DUPLICATE KEY UPDATE current_stepID = VALUES(current_stepID), is_completed = 0");
        $stmt->bind_param("iii", $userID, $topicID, $nextStepID);
    } else {
        $stmt = $conn->prepare("INSERT INTO chatbot_user_progress (userID, topicID, current_stepID, is_completed, last_updated)
            VALUES (?, ?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE is_completed = 1, last_updated = NOW()");
        $stmt->bind_param("iii", $userID, $topicID, $currentStepID);
    }
    $stmt->execute();
    $stmt->close();

    // Do not redirect — allow feedback to render
}


// Get conversation history
$stmt = $conn->prepare("SELECT s.prompt, i.user_response, i.is_correct, s.correct_feedback, s.wrong_feedback
    FROM chatbot_interaction i
    JOIN chatbot_step s ON i.stepID = s.stepID
    WHERE i.userID = ? AND s.topicID = ?
    ORDER BY i.timestamp ASC");
$stmt->bind_param("ii", $userID, $topicID);
$stmt->execute();
$result = $stmt->get_result();
$conversation = $result->fetch_all(MYSQLI_ASSOC);
// Append temporary feedback from session
if (isset($_SESSION['last_feedback'])) {
    $fb = $_SESSION['last_feedback'];
    $conversation[] = [
        'prompt' => $fb['prompt'],
        'user_response' => $fb['user_response'],
        'is_correct' => $fb['is_correct'],
        'correct_feedback' => $fb['correct_feedback'],
        'wrong_feedback' => $fb['wrong_feedback']
    ];
    unset($_SESSION['last_feedback']);
}

$stmt->close();

// Get progress and current step
$stmt = $conn->prepare("SELECT current_stepID, is_completed FROM chatbot_user_progress WHERE userID = ? AND topicID = ?");
$stmt->bind_param("ii", $userID, $topicID);
$stmt->execute();
$stmt->bind_result($currentStepID, $isCompleted);
$stmt->fetch();
$stmt->close();

// ✅ NEW: Initialize progress if no current step and not completed
if (!$currentStepID && !$isCompleted) {
    $stmt = $conn->prepare("SELECT stepID FROM chatbot_step WHERE topicID = ? ORDER BY step_number ASC LIMIT 1");
    $stmt->bind_param("i", $topicID);
    $stmt->execute();
    $stmt->bind_result($firstStepID);
    $stmt->fetch();
    $stmt->close();

    if ($firstStepID) {
        $stmt = $conn->prepare("INSERT INTO chatbot_user_progress (userID, topicID, current_stepID, is_completed, last_updated)
            VALUES (?, ?, ?, 0, NOW())
            ON DUPLICATE KEY UPDATE current_stepID = VALUES(current_stepID), is_completed = 0");
        $stmt->bind_param("iii", $userID, $topicID, $firstStepID);
        $stmt->execute();
        $stmt->close();

        $currentStepID = $firstStepID;
    }
}

// Get the current prompt if in progress
$currentPrompt = null;
if (!$isCompleted && $currentStepID) {
    $stmt = $conn->prepare("SELECT prompt FROM chatbot_step WHERE stepID = ?");
    $stmt->bind_param("i", $currentStepID);
    $stmt->execute();
    $stmt->bind_result($currentPrompt);
    $stmt->fetch();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot - <?= htmlspecialchars($topicTitle) ?></title>
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
            margin-left: 240px;
            transition: margin-left 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100vh;
            background-color: #e5dffc;
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

        /* Chat Header */
        .chat-header {
            background-color: #f5f2ff;
            padding: 20px 30px;
            border-bottom: 2px solid #e0daf3;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .chat-header h2 {
            color: #333;
            font-size: 1.5rem;
            margin: 0;
        }

        .chat-header .topic-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .header-btn {
            background-color: #8a75c9;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .header-btn:hover {
            background-color: #6f5baa;
            transform: translateY(-1px);
        }

        .header-btn.retake {
            background-color: #ff6b6b;
        }

        .header-btn.retake:hover {
            background-color: #ff5252;
        }

        /* Chat Container */
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px 30px;
            background: linear-gradient(135deg, #e5dffc 0%, #f0ebff 100%);
        }

        .message {
            margin-bottom: 20px;
            animation: fadeInUp 0.3s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-content {
            max-width: 80%;
            padding: 15px 20px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }

        .bot-message {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .bot-message .message-content {
            background: linear-gradient(135deg, #ffffff 0%, #f8f6ff 100%);
            border: 1px solid #e0daf3;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-left: 0;
        }

        .user-message {
            display: flex;
            justify-content: flex-end;
        }

        .user-message .message-content {
            background: linear-gradient(135deg, #8a75c9 0%, #b187d6 100%);
            color: white;
            margin-right: 0;
            box-shadow: 0 2px 10px rgba(138, 117, 201, 0.3);
        }

        .bot-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8a75c9 0%, #b187d6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            flex-shrink: 0;
            box-shadow: 0 2px 10px rgba(138, 117, 201, 0.3);
        }

        .feedback-message {
            margin-top: 10px;
        }

        .feedback-message .message-content {
            font-size: 0.9rem;
            padding: 10px 15px;
        }

        .feedback-correct {
            background: linear-gradient(135deg, #6fcf97 0%, #5cb85c 100%);
            color: white;
        }

        .feedback-wrong {
            background: linear-gradient(135deg, #ff6b6b 0%, #e74c3c 100%);
            color: white;
        }

        /* Input Area */
        .chat-input-area {
            background-color: #f5f2ff;
            padding: 20px 30px;
            border-top: 2px solid #e0daf3;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }

        .input-form {
            display: flex;
            gap: 15px;
            align-items: center;
            max-width: 1000px;
            margin: 0 auto;
        }

        .input-container {
            flex: 1;
            position: relative;
        }

        .user-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0daf3;
            border-radius: 25px;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .user-input:focus {
            border-color: #8a75c9;
            box-shadow: 0 0 0 3px rgba(138, 117, 201, 0.1);
        }

        .send-btn {
            background: linear-gradient(135deg, #8a75c9 0%, #b187d6 100%);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 10px rgba(138, 117, 201, 0.3);
        }

        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(138, 117, 201, 0.4);
        }

        .send-btn:active {
            transform: translateY(0);
        }

        /* Completion Message */
        .completion-message {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #6fcf97 0%, #5cb85c 100%);
            color: white;
            border-radius: 20px;
            margin: 20px;
            box-shadow: 0 4px 20px rgba(108, 207, 151, 0.3);
        }

        .completion-message h3 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .completion-message p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Scrollbar Styling */
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #8a75c9;
            border-radius: 10px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: #6f5baa;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .main {
                margin-left: 0;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .chat-header {
                padding: 15px 20px;
            }
            
            .chat-messages {
                padding: 15px 20px;
            }
            
            .chat-input-area {
                padding: 15px 20px;
            }
            
            .message-content {
                max-width: 90%;
            }
            
            .input-form {
                gap: 10px;
            }
            
            .send-btn {
                padding: 12px 20px;
            }
        }

        /* Loading Animation */
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 20px;
            background-color: white;
            border-radius: 18px;
            margin: 10px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .typing-dots {
            display: flex;
            gap: 4px;
        }

        .typing-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #8a75c9;
            animation: typing 1.4s infinite ease-in-out;
        }

        .typing-dots span:nth-child(1) { animation-delay: -0.32s; }
        .typing-dots span:nth-child(2) { animation-delay: -0.16s; }

        @keyframes typing {
            0%, 80%, 100% { 
                transform: scale(0.8);
                opacity: 0.5;
            }
            40% { 
                transform: scale(1);
                opacity: 1;
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
                <a class="nav-item active" href="chatbotTopic.php"><i class="fas fa-comment"></i><span>Chatbot</span></a>
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
        <!-- Chat Header -->
        <div class="chat-header">
            <div class="topic-info">
                <h2><i class="fas fa-robot"></i> <?= htmlspecialchars($topicTitle) ?></h2>
                <?php if ($isCompleted): ?>
                    <span class="header-btn" style="background-color: #6fcf97; cursor: default;">
                        <i class="fas fa-check-circle"></i> Completed
                    </span>
                <?php endif; ?>
            </div>
            <div class="header-actions">
                <a href="chatbotTopic.php" class="header-btn">
                    <i class="fas fa-arrow-left"></i> Back to Topics
                </a>
                <a href="chatbotChat.php?topicID=<?= $topicID ?>&retake=1" class="header-btn retake">
                    <i class="fas fa-redo"></i> Retake
                </a>
            </div>
        </div>

        <!-- Chat Container -->
        <div class="chat-container">
            <div class="chat-messages" id="chatMessages">
                <?php foreach ($conversation as $msg): ?>
                    <!-- Bot Question -->
                    <div class="message bot-message">
                        <div class="bot-avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="message-content">
                            <?= htmlspecialchars($msg['prompt']) ?>
                        </div>
                    </div>

                    <!-- User Response -->
                    <div class="message user-message">
                        <div class="message-content">
                            <?= htmlspecialchars($msg['user_response']) ?>
                        </div>
                    </div>

                    <!-- Bot Feedback -->
                    <div class="message bot-message feedback-message">
                        <div class="bot-avatar">
                            <i class="fas fa-<?= $msg['is_correct'] ? 'check' : 'times' ?>"></i>
                        </div>
                        <div class="message-content <?= $msg['is_correct'] ? 'feedback-correct' : 'feedback-wrong' ?>">
                            <?= htmlspecialchars($msg['is_correct'] ? $msg['correct_feedback'] : $msg['wrong_feedback']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($currentPrompt && !$isCompleted): ?>
                    <!-- Current Question -->
                    <div class="message bot-message">
                        <div class="bot-avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="message-content">
                            <?= htmlspecialchars($currentPrompt) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($isCompleted): ?>
                    <div class="completion-message">
                        <h3><i class="fas fa-trophy"></i> Congratulations!</h3>
                        <p>You have successfully completed this topic. Great job on your learning journey!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Input Area -->
            <?php if ($currentPrompt && !$isCompleted): ?>
                <div class="chat-input-area">
                    <form method="POST" class="input-form" id="chatForm">
                        <div class="input-container">
                            <input type="text" name="user_response" class="user-input" 
                                   placeholder="Type your response here..." required autofocus>
                        </div>
                        <button type="submit" class="send-btn">
                            <i class="fas fa-paper-plane"></i>
                            Send
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
        }

        // Auto-scroll to bottom of chat
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Scroll to bottom on page load
        window.addEventListener('load', scrollToBottom);

        // Handle form submission with better UX
        document.getElementById('chatForm')?.addEventListener('submit', function(e) {
            const input = this.querySelector('input[name="user_response"]');
            const sendBtn = this.querySelector('.send-btn');
            
            if (input.value.trim() === '') {
                e.preventDefault();
                input.focus();
                return;
            }
            
            // Disable form to prevent double submission
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        });

        // Handle Enter key in input
        document.querySelector('.user-input')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('chatForm').submit();
            }
        });

        // Mobile sidebar toggle
        if (window.innerWidth <= 768) {
            document.querySelector('.toggle-btn').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('show');
            });
        }
    </script>
</body>
</html>