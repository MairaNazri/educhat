<?php
session_start();
require_once 'server.php';

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$userID = $_SESSION['userID'];

// Get topic ID from URL parameter
$topicID = isset($_GET['topicID']) ? intval($_GET['topicID']) : null;
if (!$topicID) {
    header('Location: chatbotTopic.php'); // Redirect to chatbotTopic.php if no topic specified
    exit();
}

// Get topic information
$sql = "SELECT title, proficiency_level FROM chatbot_topic WHERE topicID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $topicID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: chatbotTopic.php'); // Redirect if topic not found
    exit();
}

$topicInfo = $result->fetch_assoc();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'start_topic':
            $topicID = intval($_POST['topicID']);
            echo json_encode(startTopic($conn, $userID, $topicID));
            exit();
            
        case 'retake_topic':
            $topicID = intval($_POST['topicID']);
            echo json_encode(retakeTopic($conn, $userID, $topicID));
            exit();
            
        case 'send_message':
            $message = trim($_POST['message']);
            $stepID = intval($_POST['stepID']);
            echo json_encode(processUserResponse($conn, $userID, $stepID, $message));
            exit();
            
        case 'get_progress':
            $topicID = intval($_POST['topicID']);
            echo json_encode(getUserProgress($conn, $userID, $topicID));
            exit();
    }
}

// Function to start a topic
function startTopic($conn, $userID, $topicID) {
    // Get the first step of the topic
    $sql = "SELECT stepID, prompt FROM chatbot_step WHERE topicID = ? ORDER BY step_number ASC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $topicID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Topic not found or has no steps'];
    }
    
    $step = $result->fetch_assoc();
    
    // Check if user already has progress for this topic
    $sql = "SELECT current_stepID FROM chatbot_user_progress WHERE userID = ? AND topicID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userID, $topicID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User has existing progress, get current step
        $progress = $result->fetch_assoc();
        $currentStepID = $progress['current_stepID'];
        
        $sql = "SELECT stepID, prompt FROM chatbot_step WHERE stepID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $currentStepID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $step = $result->fetch_assoc();
        }
    } else {
        // Create new progress record
        $sql = "INSERT INTO chatbot_user_progress (userID, topicID, current_stepID, is_completed, last_updated) VALUES (?, ?, ?, 0, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $userID, $topicID, $step['stepID']);
        $stmt->execute();
    }
    
    return [
        'success' => true,
        'stepID' => $step['stepID'],
        'prompt' => $step['prompt'],
        'topicID' => $topicID
    ];
}

// Function to retake a topic
function retakeTopic($conn, $userID, $topicID) {
    // Reset progress - delete existing progress and interactions
    $sql = "DELETE FROM chatbot_user_progress WHERE userID = ? AND topicID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userID, $topicID);
    $stmt->execute();
    
    // Optional: Delete interaction history for this topic
    $sql = "DELETE ci FROM chatbot_interaction ci 
            JOIN chatbot_step cs ON ci.stepID = cs.stepID 
            WHERE ci.userID = ? AND cs.topicID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userID, $topicID);
    $stmt->execute();
    
    // Start the topic again
    return startTopic($conn, $userID, $topicID);
}

// Function to process user response
function processUserResponse($conn, $userID, $stepID, $userResponse) {
    // Get step details
    $sql = "SELECT * FROM chatbot_step WHERE stepID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $stepID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Step not found'];
    }
    
    $step = $result->fetch_assoc();
    
    // Check if response matches expected pattern
    $isCorrect = checkResponse($userResponse, $step['expected_pattern']);
    
    // Record the interaction
    $sql = "INSERT INTO chatbot_interaction (userID, stepID, user_response, is_correct, timestamp) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisi", $userID, $stepID, $userResponse, $isCorrect);
    $stmt->execute();
    
    // Get feedback based on correctness
    $feedback = $isCorrect ? $step['correct_feedback'] : $step['wrong_feedback'];
    $nextStepID = null;
    $nextPrompt = null;
    $isCompleted = false;
    
    if ($isCorrect) {
        if ($step['next_stepID']) {
            // Get next step
            $sql = "SELECT stepID, prompt FROM chatbot_step WHERE stepID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $step['next_stepID']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $nextStep = $result->fetch_assoc();
                $nextStepID = $nextStep['stepID'];
                $nextPrompt = $nextStep['prompt'];
                
                // Update user progress
                $sql = "UPDATE chatbot_user_progress SET current_stepID = ?, last_updated = NOW() WHERE userID = ? AND topicID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iii", $nextStepID, $userID, $step['topicID']);
                $stmt->execute();
            }
        } else {
            // No next step means topic is completed
            $isCompleted = true;
            $sql = "UPDATE chatbot_user_progress SET is_completed = 1, last_updated = NOW() WHERE userID = ? AND topicID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $userID, $step['topicID']);
            $stmt->execute();
        }
    }
    
    return [
        'success' => true,
        'is_correct' => $isCorrect,
        'feedback' => $feedback,
        'next_stepID' => $nextStepID,
        'next_prompt' => $nextPrompt,
        'is_completed' => $isCompleted
    ];
}

// Function to check if user response matches expected pattern
function normalize($text) {
    return strtolower(trim(preg_replace('/\s+/', ' ', $text)));
}

function checkResponse($userResponse, $expectedPattern) {
    $userResponse = trim($userResponse);
    $expectedPattern = trim($expectedPattern);
    return matchPattern($userResponse, $expectedPattern);
}

function matchPattern($userResponse, $pattern) {
    // Match using regex (case-insensitive)
    return preg_match("/{$pattern}/i", $userResponse);
}

// Function to get user progress
function getUserProgress($conn, $userID, $topicID) {
    $sql = "SELECT * FROM chatbot_user_progress WHERE userID = ? AND topicID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userID, $topicID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['success' => true, 'progress' => $result->fetch_assoc()];
    }
    
    return ['success' => false, 'message' => 'No progress found'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>English Learning Chatbot - <?php echo htmlspecialchars($topicInfo['title']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #d9c8f4;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #f5f2ff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: #b187d6;
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .header h1 {
            font-size: 2em;
            margin-bottom: 5px;
        }

        .topic-info {
            font-size: 1.1em;
            opacity: 0.95;
        }

        .topic-level {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85em;
            margin-left: 10px;
        }

        .back-btn {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .chat-container {
            height: 500px;
            display: flex;
            flex-direction: column;
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #eee8fc;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }

        .message.bot {
            justify-content: flex-start;
        }

        .message.user {
            justify-content: flex-end;
        }

        .message-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
        }

        .message.bot .message-bubble {
            background: #e6d2f6;
            color: #4a2778;
        }

        .message.user .message-bubble {
            background: #6f5baa;
            color: white;
        }

        .message.feedback {
            justify-content: center;
        }

        .message.feedback .message-bubble {
            background: #f0f0f0;
            color: #666;
            font-style: italic;
        }

        .message.feedback.correct .message-bubble {
            background: #d6f5e9;
            color: #2e7d32;
        }

        .message.feedback.incorrect .message-bubble {
            background: #fce2e5;
            color: #c62828;
        }

        .chat-input {
            padding: 20px;
            border-top: 1px solid #ddd;
            background: #f5f2ff;
        }

        .input-group {
            display: flex;
            gap: 10px;
        }

        .chat-input input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #ccc;
            border-radius: 25px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .chat-input input:focus {
            border-color: #8a75c9;
        }

        .send-btn {
            padding: 12px 24px;
            background: #8a75c9;
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            transition: transform 0.2s ease;
        }

        .send-btn:hover {
            transform: scale(1.05);
        }

        .send-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .progress-bar {
            height: 4px;
            background: #d4c0ec;
            margin: 20px;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #8a75c9;
            width: 0%;
            transition: width 0.3s ease;
        }

        .completed-message {
            text-align: center;
            padding: 40px 20px;
            background: #e8f5e8;
            color: #2e7d32;
            font-size: 1.2em;
            font-weight: bold;
            display: none;
        }

        .completion-buttons {
            margin-top: 20px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .retake-btn, .topics-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            min-width: 160px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .retake-btn {
            background: #ff6680;
            color: white;
        }

        .topics-btn {
            background: #8a75c9;
            color: white;
        }

        .retake-btn:hover {
            background: #e0556f;
        }

        .topics-btn:hover {
            background: #6f5baa;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #8a75c9;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            display: none;
        }

        .loading-message {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        @media (max-width: 600px) {
            .completion-buttons {
                flex-direction: column;
                align-items: center;
            }

            .retake-btn, .topics-btn {
                width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-message">
            <div class="loading"></div>
            <p>Starting topic...</p>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <a href="chatbotTopic.php" class="back-btn">← Back</a>
            <h1>🤖 English Learning Chatbot</h1>
            <div class="topic-info">
                <?php echo htmlspecialchars($topicInfo['title']); ?>
                <span class="topic-level"><?php echo htmlspecialchars($topicInfo['proficiency_level']); ?></span>
            </div>
        </div>

        <div class="progress-bar" id="progressBar">
            <div class="progress-fill" id="progressFill"></div>
        </div>

        <div class="chat-container" id="chatContainer">
            <div class="chat-messages" id="chatMessages"></div>
            <div class="chat-input">
                <div class="input-group">
                    <input type="text" id="messageInput" placeholder="Type your response here..." disabled>
                    <button class="send-btn" id="sendBtn" disabled onclick="sendMessage()">Send</button>
                </div>
            </div>
        </div>

        <div class="completed-message" id="completedMessage">
            🎉 Congratulations! You've completed this topic! 🎉
            <div class="completion-buttons">
                <button class="retake-btn" onclick="retakeTopic()">
                    <i class="fas fa-redo-alt"></i>
                    <span>Retake Topic</span>
                </button>
                <a href="chatbotTopic.php" class="topics-btn">
                    <i class="fas fa-list-alt"></i>
                    <span>Browse Topics</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        let currentStepID = null;
        let currentTopicID = <?php echo $topicID; ?>;
        let isWaitingForResponse = false;
        let totalSteps = 0;
        let currentStep = 0;

        // Start the topic automatically on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Show loading overlay
            document.getElementById('loadingOverlay').style.display = 'flex';
            
            // Get total steps for progress calculation
            getTotalSteps();
            
            // Start the topic
            startTopic();
            
            // Enter key support
            document.getElementById('messageInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        });

        function getTotalSteps() {
            // This is optional - you could add an AJAX call to get total steps for better progress tracking
            // For now, we'll estimate progress based on completed steps
        }

        function startTopic() {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=start_topic&topicID=${currentTopicID}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    initializeChat(data);
                } else {
                    alert('Error starting topic: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while starting the topic.');
            })
            .finally(() => {
                // Hide loading overlay
                document.getElementById('loadingOverlay').style.display = 'none';
            });
        }

        function retakeTopic() {
            // Show loading overlay
            document.getElementById('loadingOverlay').style.display = 'flex';
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=retake_topic&topicID=${currentTopicID}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reset everything
                    currentStep = 0;
                    
                    // Hide completed message and show chat
                    document.getElementById('completedMessage').style.display = 'none';
                    document.getElementById('chatContainer').style.display = 'flex';
                    
                    // Reset progress bar
                    updateProgress(0);
                    
                    // Initialize chat with new data
                    initializeChat(data);
                } else {
                    alert('Error retaking topic: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while retaking the topic.');
            })
            .finally(() => {
                // Hide loading overlay
                document.getElementById('loadingOverlay').style.display = 'none';
            });
        }

        function initializeChat(data) {
            currentStepID = data.stepID;
            
            // Clear messages and add bot prompt
            const messagesDiv = document.getElementById('chatMessages');
            messagesDiv.innerHTML = '';
            addMessage('bot', data.prompt);
            
            // Enable input
            document.getElementById('messageInput').disabled = false;
            document.getElementById('sendBtn').disabled = false;
            document.getElementById('messageInput').focus();
        }

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message || isWaitingForResponse) return;
            
            // Add user message
            addMessage('user', message);
            input.value = '';
            
            // Disable input while processing
            isWaitingForResponse = true;
            document.getElementById('messageInput').disabled = true;
            document.getElementById('sendBtn').disabled = true;
            
            // Send to server
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=send_message&stepID=${currentStepID}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    handleResponse(data);
                } else {
                    alert('Error processing response: ' + data.message);
                    enableInput();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your response.');
                enableInput();
            })
            .finally(() => {
                isWaitingForResponse = false;
            });
        }

        function handleResponse(data) {
            // Add feedback message
            const feedbackClass = data.is_correct ? 'correct' : 'incorrect';
            addMessage('feedback ' + feedbackClass, data.feedback);
            
            if (data.is_completed) {
                // Topic completed
                setTimeout(() => {
                    document.getElementById('chatContainer').style.display = 'none';
                    document.getElementById('completedMessage').style.display = 'block';
                    updateProgress(100);
                }, 1500);
            } else if (data.next_stepID && data.is_correct) {
                // Continue to next step (only if answer was correct)
                currentStepID = data.next_stepID;
                currentStep++;
                
                setTimeout(() => {
                    addMessage('bot', data.next_prompt);
                    enableInput();
                    // Update progress (estimate based on steps completed)
                    const progressPercent = Math.min((currentStep / 5) * 100, 90); // Assume ~5 steps, max 90% until completion
                    updateProgress(progressPercent);
                }, 1500);
            } else {
                // Stay on current step (incorrect answer) or no next step
                setTimeout(() => {
                    enableInput();
                }, 1500);
            }
        }

        function enableInput() {
            document.getElementById('messageInput').disabled = false;
            document.getElementById('sendBtn').disabled = false;
            document.getElementById('messageInput').focus();
        }

        function addMessage(type, text) {
            const messagesDiv = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            
            const bubble = document.createElement('div');
            bubble.className = 'message-bubble';
            bubble.textContent = text;
            
            messageDiv.appendChild(bubble);
            messagesDiv.appendChild(messageDiv);
            
            // Scroll to bottom
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function updateProgress(percentage) {
            document.getElementById('progressFill').style.width = percentage + '%';
        }
    </script>
</body>
</html>