<?php
session_start();
include("server.php");

if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];

$sql = "SELECT * FROM flashcard WHERE userID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flashcards</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background-color: #d8c9f1;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .sidebar {
            width: 240px;
            background-color: #b187d6;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-top-right-radius: 20px;
            border-bottom-right-radius: 20px;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 10;
            transition: width 0.3s ease;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .toggle-btn {
            color: white;
            cursor: pointer;
            font-size: 1.2rem;
            text-align: right;
            margin-bottom: 20px;
        }

        .sidebar .logo {
            font-size: 1.8rem;
            font-weight: bold;
            background-color: black;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            color: white;
            margin-bottom: 20px;
        }

        .nav-section {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1rem;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            color: white;
            text-decoration: none;
        }

        .nav-item:hover,
        .nav-item.active {
            background-color: #6e50a1;
        }

        .nav-item i {
            min-width: 20px;
            text-align: center;
        }

        .logout-btn {
            background-color: white;
            color: #6e50a1;
            font-weight: bold;
            border: none;
            padding: 10px;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 30px;
            transition: 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background-color: #f3e6ff;
        }

        .main {
            flex: 1;
            padding: 30px 40px;
            margin-left: 240px;
            position: relative;
            overflow-y: auto;
            width: 100%;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed ~ .main {
            margin-left: 70px;
        }

        .collapsed .nav-item span,
        .collapsed .logo,
        .collapsed .logout-btn span {
            display: none;
        }

        .collapsed .nav-item,
        .collapsed .logout-btn {
            justify-content: center;
        }

        .collapsed .logout-btn {
            padding: 10px 0;
        }

        .add-btn {
            position: absolute;
            top: 30px;
            right: 40px;
            background-color: #8065b6;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }

        .flashcard-list {
            margin-top: 80px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .flashcard {
            background-color: #e8e0f4;
            padding: 20px;
            border-radius: 16px;
            width: 200px;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            position: relative;
            transition: transform 0.2s;
        }

        .flashcard:hover {
            transform: scale(1.03);
        }

        .flashcard h3 {
            font-size: 18px;
            text-align: center;
            color: #333;
        }

        .flashcard .actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 10px;
            font-size: 16px;
            color: #444;
        }

        .flashcard .actions i {
            cursor: pointer;
        }

        .no-flashcards {
            font-size: 18px;
            text-align: center;
            color: #666;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 12px;
            width: 400px;
            max-width: 90%;
            position: relative;
        }

        .modal h2 {
            margin-bottom: 15px;
        }

        .modal p {
            margin-bottom: 10px;
            font-size: 16px;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            color: #aaa;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div>
        <div class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></div>
        <div class="logo">EDUCHAT</div>
        <div class="nav-section">
            <a class="nav-item" href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a class="nav-item" href="chatbotTopic.php"><i class="fas fa-comment"></i><span>Chatbot</span></a>
            <a class="nav-item" href="quizSelect.php"><i class="fas fa-clipboard"></i><span>Quiz</span></a>
            <a class="nav-item active" href="flashcardDash.php"><i class="fas fa-clone"></i><span>Flashcards</span></a>
            <a class="nav-item" href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a>
        </div>
    </div>
    <button class="logout-btn" onclick="window.location.href='logout.php'">
        <i class="fas fa-sign-out-alt"></i><span>Logout</span>
    </button>
</div>

<!-- Main -->
<div class="main">
    <a href="addFlashcard.php" class="add-btn">+ New Flashcard</a>

    <?php if ($result->num_rows > 0): ?>
        <div class="flashcard-list">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="flashcard" onclick="openModal('<?php echo htmlspecialchars(addslashes($row['vocabulary'])); ?>', '<?php echo htmlspecialchars(addslashes($row['meaning'])); ?>', '<?php echo htmlspecialchars(addslashes($row['example'])); ?>')">
                    <h3><?php echo htmlspecialchars(strtoupper($row['vocabulary'])); ?></h3>
                    <div class="actions" onclick="event.stopPropagation();">
                        <i class="fas fa-pen" title="Edit" onclick="window.location.href='editFlashcard.php?id=<?php echo $row['flashcardID']; ?>'"></i>
                        <i class="fas fa-trash" title="Delete" onclick="if(confirm('Are you sure to delete this flashcard?')) window.location.href='deleteFlashcard.php?id=<?php echo $row['flashcardID']; ?>'"></i>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="no-flashcards">You haven't added any flashcards yet.</p>
    <?php endif; ?>
</div>

<!-- Flashcard Modal -->
<div id="flashcardModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 id="modalWord"></h2>
        <p><strong>Meaning:</strong> <span id="modalMeaning"></span></p>
        <p><strong>Example:</strong> <span id="modalExample"></span></p>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('collapsed');
    }

    function openModal(word, meaning, example) {
        document.getElementById('modalWord').textContent = word;
        document.getElementById('modalMeaning').textContent = meaning;
        document.getElementById('modalExample').textContent = example;
        document.getElementById('flashcardModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('flashcardModal').style.display = 'none';
    }

    window.onclick = function(event) {
        let modal = document.getElementById('flashcardModal');
        if (event.target === modal) {
            modal.style.display = "none";
        }
    };
</script>

</body>
</html>
