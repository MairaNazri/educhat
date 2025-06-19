<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "website");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all users (non-admin)
$sql = "SELECT u.userID, u.name, u.email, u.profile_picture, u.proficiency_level, 
        (SELECT COUNT(*) FROM quizresult qr WHERE qr.userID = u.userID) as quizzes_taken 
        FROM user u WHERE u.role != 'admin' ORDER BY u.name";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background-color: #dcd0f3;
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 240px;
            background-color: #b28acb;
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
            transition: width 0.3s ease;
            position: relative;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar h2 {
            font-size: 1.6rem;
            color: #e5ddf4;
            margin-bottom: 30px;
            text-align: center;
        }

        .sidebar.collapsed h2 {
            display: none;
        }

        .nav-link {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 15px;
            border-radius: 10px;
            cursor: pointer;
            color: #f2e9ff;
            font-size: 1rem;
            transition: background 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: #8f75b5;
        }

        .nav-link i {
            min-width: 20px;
            text-align: center;
        }

        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 10px 0;
        }

        .sidebar.collapsed .nav-link span {
            display: none;
        }

        .main {
            flex: 1;
            padding: 40px;
            background-color: #c6aef0;
            transition: margin-left 0.3s ease;
            width: 100%;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 1.8rem;
            color: #6c52a1;
            display: inline-block;
            margin-left: 10px;
        }

        .user-info {
            font-size: 1rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background-color: #ccc;
            border-radius: 50%;
        }

        .toggle-btn {
            font-size: 24px;
            cursor: pointer;
            color: #6c52a1;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #e8dcfa;
            border-radius: 15px;
            overflow: hidden;
        }

        .user-table th,
        .user-table td {
            padding: 12px 15px;
            text-align: left;
        }

        .user-table thead {
            background-color: #6c52a1;
            color: white;
        }

        .user-table tbody tr:hover {
            background-color: #d6c6f1;
        }

        .user-table th:first-child,
        .user-table td:first-child {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #6c52a1;
            background-color: #f0f0f0;
        }

        .profile-pic-fallback {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #6c52a1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background-color 0.3s;
        }

        .btn-info {
            background-color: #6c52a1;
            color: white;
        }

        .btn-info:hover {
            background-color: #5a4388;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #e8dcfa;
            margin: 5% auto;
            padding: 20px;
            border-radius: 15px;
            width: 80%;
            max-width: 800px;
            position: relative;
        }

        .modal-header {
            padding: 10px 0;
            background-color: #6c52a1;
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 15px 20px;
        }

        .modal-body {
            padding: 20px;
        }

        .close {
            color: white;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .nav-tabs {
            display: flex;
            border-bottom: 1px solid #ccc;
            margin-bottom: 15px;
        }

        .nav-tabs .nav-link {
            padding: 10px 15px;
            cursor: pointer;
            color: #6c52a1;
            border-bottom: 2px solid transparent;
        }

        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 2px solid #6c52a1;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
        
        .nav-link[data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background-color: #6c52a1;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            white-space: nowrap;
            margin-left: 10px;
            font-size: 0.85rem;
            z-index: 10;
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <h2>EduChat<br>Admin</h2>

        <div class="nav-link" onclick="location.href='adminDash.php'" data-tooltip="Dashboard">
            <i class="fas fa-home"></i> <span>Dashboard</span>
        </div>

        <div class="nav-link" onclick="location.href='manageQuiz.php'" data-tooltip="Manage Quizzes">
            <i class="fas fa-clipboard-list"></i> <span>Manage Quizzes</span>
        </div>

        <div class="nav-link" onclick="location.href='manageChatbot.php'" data-tooltip="Manage Chatbot">
            <i class="fas fa-comments"></i> <span>Manage Chatbot</span>
        </div>

        <div class="nav-link active" onclick="location.href='manageUser.php'" data-tooltip="User Management">
            <i class="fas fa-user"></i> <span>User Management</span>
        </div>

        <a href="logout.php" class="nav-link" data-tooltip="Logout" style="margin-top: auto;">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </div>

    <div class="main" id="main">
        <div class="header">
            <div style="display: flex; align-items: center;">
                <div class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></div>
                <h1>User Management</h1>
            </div>
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="user-avatar"></div>
            </div>
        </div>

        <table class="user-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Proficiency Level</th>
                    <th>Quizzes Taken</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // Handle profile picture path - check multiple possible locations
                        $profilePic = '';
                        if (!empty($row["profile_picture"])) {
                            // Check if it already starts with uploads/
                            if (strpos($row["profile_picture"], 'uploads/') === 0) {
                                $profilePic = $row["profile_picture"];
                            } else {
                                $profilePic = "uploads/" . $row["profile_picture"];
                            }
                        }
                        
                        // Set default if no profile picture or file doesn't exist
                        if (empty($profilePic) || !file_exists($profilePic)) {
                            $profilePic = "uploads/profiles/default-avatar.png";
                            // If default doesn't exist either, we'll use a fallback div
                            if (!file_exists($profilePic)) {
                                $profilePic = null;
                            }
                        }
                        
                        echo "<tr>";
                        echo "<td>";
                        if ($profilePic && file_exists($profilePic)) {
                            echo "<img src='".$profilePic."' class='profile-pic' alt='Profile Picture' onerror='this.style.display=\"none\"; this.nextElementSibling.style.display=\"flex\";'>";
                            echo "<div class='profile-pic-fallback' style='display: none;'>".strtoupper(substr($row["name"], 0, 1))."</div>";
                        } else {
                            echo "<div class='profile-pic-fallback'>".strtoupper(substr($row["name"], 0, 1))."</div>";
                        }
                        echo htmlspecialchars($row["name"]);
                        echo "</td>";
                        echo "<td>".htmlspecialchars($row["email"])."</td>";
                        echo "<td>".htmlspecialchars($row["proficiency_level"])."</td>";
                        echo "<td>".$row["quizzes_taken"]."</td>";
                        echo "<td><button class='btn btn-info view-details' data-id='".$row["userID"]."'><i class='fas fa-eye'></i> View Details</button></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align: center;'>No users found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- User Details Modal -->
    <div id="userDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5>User Details</h5>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <!-- Content will be loaded dynamically -->
                <div style="text-align: center;">
                    <div style="display: inline-block; width: 50px; height: 50px; border: 5px solid #6c52a1; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("collapsed");
        }

        // Modal handling
        var modal = document.getElementById("userDetailsModal");
        var span = document.getElementsByClassName("close")[0];

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // View user details
        document.querySelectorAll('.view-details').forEach(function(button) {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                modal.style.display = "block";
                
                // Fetch user details with AJAX
                fetch('detailUser.php?userId=' + userId)
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('userDetailsContent').innerHTML = data;
                        // Initialize tabs after content is loaded
                        initTabs();
                    })
                    .catch(error => {
                        document.getElementById('userDetailsContent').innerHTML = '<div style="color: red; text-align: center;">Error loading user details</div>';
                    });
            });
        });

        function initTabs() {
            const tabs = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to current tab
                    tab.classList.add('active');
                    const tabId = tab.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        }

        // Add spinning animation for loading
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>