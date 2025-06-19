<?php
include 'server.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Optional: delete related questions/answers here if needed
    $stmt = $conn->prepare("DELETE FROM quiz WHERE quizID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: manageQuiz.php");
?>
