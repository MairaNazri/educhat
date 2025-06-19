<?php 
include 'server.php'; 
 
if (!isset($_GET['id'])) { 
    echo "Flashcard ID is missing."; 
    exit(); 
} 
 
$flashcard_id = $_GET['id']; 
$sql = "SELECT * FROM flashcard WHERE flashcardID = ?"; 
$stmt = $conn->prepare($sql); 
$stmt->bind_param("i", $flashcard_id); 
$stmt->execute(); 
$result = $stmt->get_result(); 
 
if ($result->num_rows != 1) { 
    echo "Flashcard not found."; 
    exit(); 
} 
 
$flashcard = $result->fetch_assoc(); 
?> 
 
<!DOCTYPE html> 
<html> 
<head> 
    <title>Edit Flashcard</title> 
    <link rel="stylesheet" href="styles.css"> <!-- Link your main CSS file if needed --> 
    <style> 
        body { 
            background-color: #d8c9f1; 
            font-family: 'Segoe UI', sans-serif; 
            padding: 40px; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh;
        } 
 
        .edit-container { 
            max-width: 600px; 
            width: 100%;
            background-color: #f4edfb; 
            border-radius: 20px; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); 
            padding: 40px 30px; 
        } 
 
        .edit-container h2 { 
            color: #6e50a1; 
            margin-bottom: 30px; 
            text-align: center; 
        } 
 
        .form-group { 
            margin-bottom: 20px; 
        } 
 
        label { 
            font-weight: bold; 
            color: #4a3c66; 
            display: block; 
            margin-bottom: 8px; 
        } 
 
        input[type="text"], textarea { 
            width: 100%; 
            padding: 12px 14px; 
            border: 1px solid #c8aed6; 
            border-radius: 10px; 
            background-color: #ffffff; 
            font-size: 15px; 
        } 
 
        .btn-save { 
            background-color: #8065b6; 
            color: white; 
            padding: 14px 0; 
            border: none; 
            border-radius: 12px; 
            cursor: pointer; 
            width: 100%; 
            font-size: 16px; 
            transition: background-color 0.2s ease; 
        } 
 
        .btn-save:hover { 
            background-color: #6e50a1; 
        } 
 
        a.back-link { 
            display: block; 
            text-align: center; 
            margin-top: 20px; 
            color: #6e50a1; 
            text-decoration: none; 
        } 
 
        a.back-link:hover { 
            text-decoration: underline; 
        } 
    </style> 
</head> 
<body> 
    <div class="edit-container"> 
        <h2>Edit Flashcard</h2> 
        <form method="POST" action="updateFlashcard.php"> 
            <input type="hidden" name="flashcardID" value="<?= $flashcard['flashcardID'] ?>"> 
 
            <div class="form-group"> 
                <label>Word:</label> 
                <input type="text" name="vocabulary" value="<?= htmlspecialchars($flashcard['vocabulary']) ?>" required> 
            </div> 
 
            <div class="form-group"> 
                <label>Meaning:</label> 
                <textarea name="meaning" rows="3" required><?= htmlspecialchars($flashcard['meaning']) ?></textarea> 
            </div> 
 
            <div class="form-group"> 
                <label>Example:</label> 
                <textarea name="example" rows="3" required><?= htmlspecialchars($flashcard['example']) ?></textarea> 
            </div> 
 
            <button type="submit" class="btn-save">Save Changes</button> 
        </form> 
        <a class="back-link" href="flashcardDash.php">← Back to Flashcards</a> 
    </div> 
</body> 
</html>
