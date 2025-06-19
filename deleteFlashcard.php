<?php
session_start();
include("server.php");

if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $flashcardID = $_GET['id'];

    // Verify the flashcard belongs to the current user before deleting
    $checkStmt = $conn->prepare("SELECT * FROM flashcard WHERE flashcardID = ? AND userID = ?");
    $checkStmt->bind_param("ii", $flashcardID, $userID);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        $deleteStmt = $conn->prepare("DELETE FROM flashcard WHERE flashcardID = ?");
        $deleteStmt->bind_param("i", $flashcardID);
        $deleteStmt->execute();
    }
}

header("Location: flashcardDash.php");
exit();
?>
