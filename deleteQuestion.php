<?php
include 'server.php';

$questionID = $_GET['questionID'] ?? 0;
$quizID = $_GET['quizID'] ?? 0;

// Debugging: Check if parameters are passed correctly
echo "Question ID: " . $questionID . "<br>";
echo "Quiz ID: " . $quizID . "<br>";

if (!$questionID || !$quizID) {
    die("Missing question ID or quiz ID.");
}

// Delete related answers from the qanswer table
$delAnswers = $conn->prepare("DELETE FROM qanswer WHERE questionID = ?");
if ($delAnswers) {
    $delAnswers->bind_param("i", $questionID);
    if ($delAnswers->execute()) {
        echo "Related answers deleted successfully.<br>";
    } else {
        die("Error deleting answers: " . $conn->error);
    }
    $delAnswers->close();
} else {
    die("Error preparing DELETE for qanswer: " . $conn->error);
}

// Delete the question itself from the question table
$delQuestion = $conn->prepare("DELETE FROM question WHERE questionID = ?");
if ($delQuestion) {
    $delQuestion->bind_param("i", $questionID);
    if ($delQuestion->execute()) {
        echo "Question deleted successfully.<br>";
    } else {
        die("Error deleting question: " . $conn->error);
    }
    $delQuestion->close();
} else {
    die("Error preparing DELETE for question: " . $conn->error);
}

// Redirect back to the question list page
header("Location: manageQuestion.php?quizID=$quizID");
exit;
?>
