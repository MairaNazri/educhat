<?php
include 'server.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topicID = $_POST['topicID'];
    $step_number = $_POST['step_number'];
    $prompt = $_POST['prompt'];
    $expected_pattern = $_POST['expected_pattern'];
    $pattern_type = $_POST['pattern_type'];
    $correct_feedback = $_POST['correct_feedback'] ?? '';
    $wrong_feedback = $_POST['wrong_feedback'] ?? '';
    $next_stepID = $_POST['next_stepID'] !== '' ? $_POST['next_stepID'] : NULL;

    $stmt = $conn->prepare("INSERT INTO chatbot_step (topicID, step_number, prompt, expected_pattern, pattern_type, correct_feedback, wrong_feedback, next_stepID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssssi", $topicID, $step_number, $prompt, $expected_pattern, $pattern_type, $correct_feedback, $wrong_feedback, $next_stepID);
    $stmt->execute();

    header("Location: viewSteps.php?topicID=$topicID");
    exit;
} else {
    echo "Invalid request.";
}
?>
