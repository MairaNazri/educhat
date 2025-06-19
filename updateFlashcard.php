<?php
include 'server.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['flashcardID'];
    $vocabulary = $_POST['vocabulary'];
    $meaning = $_POST['meaning'];
    $example = $_POST['example'];

    $sql = "UPDATE flashcard SET vocabulary = ?, meaning = ?, example = ? WHERE flashcardID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $vocabulary, $meaning, $example, $id);

    if ($stmt->execute()) {
        header("Location: flashcardDash.php");
        exit();
    } else {
        echo "Error updating flashcard.";
    }
} else {
    echo "Invalid request.";
}
?>
