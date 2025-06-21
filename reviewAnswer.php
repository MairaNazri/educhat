<?php
session_start();
require 'server.php';

if (!isset($_SESSION['userID']) || !isset($_GET['quizID'])) {
    die("Unauthorized access.");
}

$userID = $_SESSION['userID'];
$quizID = intval($_GET['quizID']);

// Get quiz title
$quizStmt = $conn->prepare("SELECT title FROM quiz WHERE quizID = ?");
$quizStmt->bind_param("i", $quizID);
$quizStmt->execute();
$quizTitle = $quizStmt->get_result()->fetch_assoc()['title'] ?? 'Quiz';

// Fetch all answered questions and details
$sql = "
SELECT q.question_text, ua.answerID AS selectedID, a1.answer_text AS selected_text, a2.answer_text AS correct_text, ua.is_correct
FROM user_answer ua
JOIN question q ON ua.questionID = q.questionID
JOIN qanswer a1 ON ua.answerID = a1.answerID
JOIN qanswer a2 ON q.questionID = a2.questionID AND a2.status = 1
WHERE ua.userID = ? AND ua.quizID = ?
ORDER BY q.questionID
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $userID, $quizID);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Review Answers - <?php echo htmlspecialchars($quizTitle); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0ebff;
            padding: 40px;
        }

        .container {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        h2 {
            color: #6e50a1;
            margin-bottom: 25px;
        }

        .question-box {
            margin-bottom: 30px;
            padding: 20px;
            border-left: 6px solid #b187d6;
            background: #f9f5ff;
            border-radius: 8px;
        }

        .question-text {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .answer {
            margin-left: 20px;
        }

        .correct {
            color: green;
        }

        .wrong {
            color: red;
        }

        .back-btn {
            margin-top: 30px;
            display: inline-block;
            background: #8a75c9;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
        }

        .back-btn:hover {
            background: #6f5baa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Review: <?php echo htmlspecialchars($quizTitle); ?></h2>

        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="question-box">
                <div class="question-text"><?php echo htmlspecialchars($row['question_text']); ?></div>
                <div class="answer">
                    Your Answer: 
                    <span class="<?php echo $row['is_correct'] ? 'correct' : 'wrong'; ?>">
                        <?php echo htmlspecialchars($row['selected_text']); ?>
                    </span>
                </div>
                <?php if (!$row['is_correct']): ?>
                    <div class="answer">
                        Correct Answer: 
                        <span class="correct"><?php echo htmlspecialchars($row['correct_text']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>

        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
</body>
</html>
