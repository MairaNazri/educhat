<?php
include 'server.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stepID = $_POST['stepID'];
    $topicID = $_POST['topicID'];
    $step_number = $_POST['step_number'];
    $prompt = $_POST['prompt'];
    $expected_pattern = $_POST['expected_pattern'];
    $pattern_type = $_POST['pattern_type'];
    $correct_feedback = $_POST['correct_feedback'] ?? '';
    $wrong_feedback = $_POST['wrong_feedback'] ?? '';
    $next_stepID = $_POST['next_stepID'] !== '' ? $_POST['next_stepID'] : NULL;

    // Validate next_stepID if it's not NULL
    if (!empty($next_stepID)) {
        $check = $conn->prepare("SELECT stepID FROM chatbot_step WHERE stepID = ?");
        $check->bind_param("i", $next_stepID);
        $check->execute();
        $check->store_result();
        if ($check->num_rows === 0) {
            echo "Error: next_stepID $next_stepID does not exist in chatbot_step.";
            exit;
        }
        $check->close();
    }

    // Prepare the update statement
    $stmt = $conn->prepare("UPDATE chatbot_step 
        SET step_number = ?, prompt = ?, expected_pattern = ?, pattern_type = ?, 
            correct_feedback = ?, wrong_feedback = ?, next_stepID = ? 
        WHERE stepID = ?");
    $stmt->bind_param("issssssi", $step_number, $prompt, $expected_pattern, $pattern_type, $correct_feedback, $wrong_feedback, $next_stepID, $stepID);
    $stmt->execute();

    header("Location: viewSteps.php?topicID=$topicID");
    exit;
} else {
    echo "Invalid request.";
}
?>
