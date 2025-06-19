<?php
session_start();
include 'server.php';

$userID = $_SESSION['userID'];
$topicID = intval($_GET['topicID']);
$feedback = "";
$isCompleted = false;

// Handle retake request
if (isset($_GET['retake']) && $_GET['retake'] == '1') {
    // Reset user progress
    $conn->query("UPDATE chatbot_user_progress SET is_completed=0, last_updated=NOW() WHERE userID=$userID AND topicID=$topicID");
    
    // Delete all previous interactions for this topic
    $conn->query("DELETE ci FROM chatbot_interaction ci 
                  JOIN chatbot_step cs ON ci.stepID = cs.stepID 
                  WHERE ci.userID = $userID AND cs.topicID = $topicID");
    
    // Reset to first step
    $stepQuery = $conn->query("SELECT stepID FROM chatbot_step WHERE topicID=$topicID ORDER BY step_number ASC LIMIT 1");
    $stepRow = $stepQuery->fetch_assoc();
    $firstStepID = $stepRow['stepID'];
    $conn->query("UPDATE chatbot_user_progress SET current_stepID=$firstStepID WHERE userID=$userID AND topicID=$topicID");
    
    // Redirect to clean chat
    header("Location: chatbotChat.php?topicID=$topicID");
    exit();
}

// Get topic title
$topicQuery = $conn->query("SELECT title FROM chatbot_topic WHERE topicID = $topicID");
$topicTitle = $topicQuery->fetch_assoc()['title'];

// Get user progress
$progressQuery = $conn->query("SELECT * FROM chatbot_user_progress WHERE userID=$userID AND topicID=$topicID");
if ($progressQuery->num_rows > 0) {
    $progress = $progressQuery->fetch_assoc();
    $currentStepID = $progress['current_stepID'];
    $isCompleted = $progress['is_completed'];
} else {
    // No progress yet
    $stepQuery = $conn->query("SELECT stepID FROM chatbot_step WHERE topicID=$topicID ORDER BY step_number ASC LIMIT 1");
    $stepRow = $stepQuery->fetch_assoc();
    $currentStepID = $stepRow['stepID'];
    $conn->query("INSERT INTO chatbot_user_progress (userID, topicID, current_stepID, is_completed, last_updated) 
                  VALUES ($userID, $topicID, $currentStepID, 0, NOW())");
}

// Get current step info
$currentStepQuery = $conn->query("SELECT * FROM chatbot_step WHERE stepID = $currentStepID");
$currentStep = $currentStepQuery->fetch_assoc();

// Handle user response
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userResponse = trim($_POST['user_response']);
    $isCorrect = preg_match('/' . $currentStep['expected_pattern'] . '/i', $userResponse) ? 1 : 0;
    $feedback = $isCorrect ? $currentStep['correct_feedback'] : $currentStep['wrong_feedback'];

    // Store interaction
    $stmt = $conn->prepare("INSERT INTO chatbot_interaction (userID, stepID, user_response, is_correct, timestamp) 
                            VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisi", $userID, $currentStepID, $userResponse, $isCorrect);
    $stmt->execute();

    // Proceed if correct
    if ($isCorrect) {
        $nextStepID = $currentStep['next_stepID'];
        if ($nextStepID) {
            $conn->query("UPDATE chatbot_user_progress SET current_stepID=$nextStepID, last_updated=NOW() 
                          WHERE userID=$userID AND topicID=$topicID");
            header("Location: chatbotChat.php?topicID=$topicID");
            exit();
        } else {
            $conn->query("UPDATE chatbot_user_progress SET is_completed=1, last_updated=NOW() 
                          WHERE userID=$userID AND topicID=$topicID");
            $isCompleted = true;
        }
    }
}

// Load conversation history
$chatQuery = $conn->query("
    SELECT ci.*, cs.prompt, cs.correct_feedback, cs.wrong_feedback 
    FROM chatbot_interaction ci 
    JOIN chatbot_step cs ON ci.stepID = cs.stepID 
    WHERE ci.userID = $userID AND cs.topicID = $topicID 
    ORDER BY ci.timestamp ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Session - <?php echo htmlspecialchars($topicTitle); ?></title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .top-header {
            background: linear-gradient(135deg, #b187d6, #8a75c9);
            padding: 20px 40px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .educhat-logo {
            background-color: black;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 1.2rem;
            font-weight: bold;
            color: white;
        }

        .header-title {
            font-size: 1.4rem;
            font-weight: 600;
        }

        .header-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 2px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-btn, .retake-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.3);
            cursor: pointer;
        }

        .back-btn:hover, .retake-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }

        .retake-btn {
            background: rgba(255, 107, 107, 0.8);
            border: 1px solid rgba(255, 107, 107, 0.5);
        }

        .retake-btn:hover {
            background: rgba(255, 107, 107, 0.9);
        }

        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            padding: 30px 40px;
        }

        .chat-wrapper {
            width: 100%;
            max-width: 800px;
            background: #f5f2ff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            height: 75vh;
            overflow: hidden;
        }

        .chat-header {
            background: white;
            padding: 20px 25px;
            border-radius: 20px 20px 0 0;
            border-bottom: 1px solid #e5e5e5;
        }

        .chat-header h3 {
            color: #333;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .chat-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #666;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            background: #6fcf97;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px 25px;
            background: #fafbff;
        }

        .message {
            display: flex;
            margin-bottom: 20px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.bot {
            justify-content: flex-start;
        }

        .message.user {
            justify-content: flex-end;
        }

        .message-bubble {
            max-width: 70%;
            padding: 15px 20px;
            border-radius: 20px;
            line-height: 1.5;
            word-wrap: break-word;
            position: relative;
        }

        .message.bot .message-bubble {
            background: white;
            color: #333;
            border-bottom-left-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e5e5e5;
        }

        .message.user .message-bubble {
            background: linear-gradient(135deg, #b187d6, #8a75c9);
            color: white;
            border-bottom-right-radius: 5px;
            box-shadow: 0 2px 10px rgba(177, 135, 214, 0.3);
        }

        .message-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            margin: 0 10px;
        }

        .bot .message-avatar {
            background: linear-gradient(135deg, #b187d6, #8a75c9);
            color: white;
        }

        .user .message-avatar {
            background: #6e50a1;
            color: white;
        }

        .chat-input-container {
            background: white;
            padding: 20px 25px;
            border-radius: 0 0 20px 20px;
            border-top: 1px solid #e5e5e5;
        }

        .chat-input-form {
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }

        .input-wrapper {
            flex: 1;
            position: relative;
        }

        .chat-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e5e5e5;
            border-radius: 25px;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
            background: #fafbff;
        }

        .chat-input:focus {
            border-color: #b187d6;
            background: white;
        }

        .send-btn {
            background: linear-gradient(135deg, #b187d6, #8a75c9);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(177, 135, 214, 0.3);
        }

        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(177, 135, 214, 0.4);
        }

        .send-btn:active {
            transform: translateY(0);
        }

        .completed-container {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 15px;
            margin: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .completed-icon {
            font-size: 4rem;
            color: #6fcf97;
            margin-bottom: 20px;
        }

        .completed-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .completed-text {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .completed-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .completed-btn {
            background: linear-gradient(135deg, #b187d6, #8a75c9);
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 25px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(177, 135, 214, 0.3);
        }

        .completed-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(177, 135, 214, 0.4);
        }

        .completed-btn.retake {
            background: linear-gradient(135deg, #ff6b6b, #ff5252);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .completed-btn.retake:hover {
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .typing-indicator {
            display: none;
            align-items: center;
            gap: 5px;
            color: #666;
            font-style: italic;
            margin-bottom: 10px;
        }

        .typing-dots {
            display: flex;
            gap: 3px;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            background: #b187d6;
            border-radius: 50%;
            animation: typing 1.4s infinite ease-in-out;
        }

        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }

        @keyframes typing {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .modal-icon {
            font-size: 3rem;
            color: #ff6b6b;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .modal-text {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .modal-btn.confirm {
            background: linear-gradient(135deg, #ff6b6b, #ff5252);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .modal-btn.confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .modal-btn.cancel {
            background: #f8f9fa;
            color: #666;
            border: 2px solid #e5e5e5;
        }

        .modal-btn.cancel:hover {
            background: #e5e5e5;
        }

        @media screen and (max-width: 768px) {
            .top-header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .header-right {
                order: -1;
                width: 100%;
                justify-content: center;
            }

            .main-container {
                padding: 20px;
            }

            .chat-wrapper {
                height: 70vh;
            }

            .message-bubble {
                max-width: 85%;
            }

            .header-title {
                font-size: 1.2rem;
            }

            .completed-actions {
                flex-direction: column;
                align-items: center;
            }

            .modal-content {
                margin: 20% auto;
                padding: 25px;
            }

            .modal-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="top-header">
    <div class="header-left">
        <div class="educhat-logo">EDUCHAT</div>
        <div>
            <div class="header-title"><?php echo htmlspecialchars($topicTitle); ?></div>
            <div class="header-subtitle">Interactive Learning Session</div>
        </div>
    </div>
    <div class="header-right">
        <?php if ($chatQuery->num_rows > 0 || $isCompleted): ?>
            <button onclick="showRetakeModal()" class="retake-btn">
                <i class="fas fa-redo"></i>
                <span>Retake</span>
            </button>
        <?php endif; ?>
        <a href="#" onclick="handleBackClick(event)" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Topics</span>
        </a>
    </div>
</div>

<div class="main-container">
    <?php if ($isCompleted): ?>
        <div class="completed-container">
            <div class="completed-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="completed-title">Congratulations!</div>
            <div class="completed-text">
                You have successfully completed this topic. Your progress has been saved and you can now move on to the next topic.
            </div>
            <div class="completed-actions">
                <a href="chatbotTopic.php" class="completed-btn">
                    <i class="fas fa-list"></i>
                    Back to Topics
                </a>
                <a href="#" onclick="showRetakeModal()" class="completed-btn retake">
                    <i class="fas fa-redo"></i>
                    Retake Session
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="chat-wrapper">
            <div class="chat-header">
                <h3>Chat with EDUCHAT Assistant</h3>
                <div class="chat-status">
                    <div class="status-dot"></div>
                    <span>Active Learning Session</span>
                </div>
            </div>

            <div class="chat-messages" id="chat-messages">
                <?php
                // Rebuild past conversation
                while ($chat = $chatQuery->fetch_assoc()) {
                    echo "<div class='message bot'>";
                    echo "<div class='message-avatar'><i class='fas fa-robot'></i></div>";
                    echo "<div class='message-bubble'>" . htmlspecialchars($chat['prompt']) . "</div>";
                    echo "</div>";
                    
                    echo "<div class='message user'>";
                    echo "<div class='message-bubble'>" . htmlspecialchars($chat['user_response']) . "</div>";
                    echo "<div class='message-avatar'><i class='fas fa-user'></i></div>";
                    echo "</div>";
                    
                    $feedbackMsg = $chat['is_correct'] ? $chat['correct_feedback'] : $chat['wrong_feedback'];
                    echo "<div class='message bot'>";
                    echo "<div class='message-avatar'><i class='fas fa-robot'></i></div>";
                    echo "<div class='message-bubble'>$feedbackMsg</div>";
                    echo "</div>";
                }

                // Show current prompt
                if (!$isCompleted && $currentStep) {
                    echo "<div class='message bot'>";
                    echo "<div class='message-avatar'><i class='fas fa-robot'></i></div>";
                    echo "<div class='message-bubble'>" . htmlspecialchars($currentStep['prompt']) . "</div>";
                    echo "</div>";
                }
                ?>
                
                <div class="typing-indicator" id="typing-indicator">
                    <div class="message-avatar"><i class="fas fa-robot"></i></div>
                    <div>
                        EDUCHAT is typing
                        <div class="typing-dots">
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chat-input-container">
                <form method="post" class="chat-input-form" id="chatForm">
                    <div class="input-wrapper">
                        <input type="text" name="user_response" class="chat-input" placeholder="Type your response here..." required autocomplete="off" id="messageInput">
                    </div>
                    <button type="submit" class="send-btn" id="sendBtn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Retake Confirmation Modal -->
<div id="retakeModal" class="modal">
    <div class="modal-content">
        <div class="modal-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="modal-title">Retake Chatbot Session?</div>
        <div class="modal-text">
            Are you sure you want to retake this chatbot session? This will delete all your current progress and conversation history, and you'll start from the beginning.
        </div>
        <div class="modal-actions">
            <a href="chatbotChat.php?topicID=<?php echo $topicID; ?>&retake=1" class="modal-btn confirm">
                <i class="fas fa-redo"></i>
                Yes, Retake
            </a>
            <button onclick="hideRetakeModal()" class="modal-btn cancel">
                <i class="fas fa-times"></i>
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
    const chatMessages = document.getElementById('chat-messages');
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const typingIndicator = document.getElementById('typing-indicator');
    const retakeModal = document.getElementById('retakeModal');

    // Auto-scroll to bottom
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Show typing indicator
    function showTyping() {
        typingIndicator.style.display = 'flex';
        scrollToBottom();
    }

    // Hide typing indicator
    function hideTyping() {
        typingIndicator.style.display = 'none';
    }

    // Show retake modal
    function showRetakeModal() {
        retakeModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    // Hide retake modal
    function hideRetakeModal() {
        retakeModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == retakeModal) {
            hideRetakeModal();
        }
    }

    // Handle form submission
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            const message = messageInput.value.trim();
            if (!message) {
                e.preventDefault();
                return;
            }

            // Show user message immediately
            const userMsg = document.createElement('div');
            userMsg.className = 'message user';
            userMsg.innerHTML = `
                <div class="message-bubble">${message}</div>
                <div class="message-avatar"><i class="fas fa-user"></i></div>
            `;
            chatMessages.appendChild(userMsg);
            
            // Show typing indicator
            showTyping();
            
            // Disable input and button
            messageInput.disabled = true;
            sendBtn.disabled = true;
            
            scrollToBottom();
        });
    }

    // Handle back button click
    function handleBackClick(event) {
        event.preventDefault();
        const isCompleted = <?= $isCompleted ? 'true' : 'false' ?>;
        if (!isCompleted) {
            const confirmLeave = confirm("Are you sure you want to leave this chat? Your progress will be saved.");
            if (!confirmLeave) return;
        }
        window.location.href = 'chatbotTopic.php';
    }

    // Focus on input when page loads
    if (messageInput) {
        messageInput.focus();
    }

    // Initial scroll to bottom
    scrollToBottom();

    // Handle Enter key for sending messages
    if (messageInput) {
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                chatForm.submit();
            }
        });
    }

    // Handle Escape key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && retakeModal.style.display === 'block') {
            hideRetakeModal();
        }
    });
</script>

</body>
</html>