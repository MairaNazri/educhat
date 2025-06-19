<?php
// detailUser.php - Script to fetch detailed user information
if(!isset($_GET['userId']) || empty($_GET['userId'])) {
    echo "Invalid user ID";
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "website");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userId = $_GET['userId'];

// Fetch user basic info
$userSql = "SELECT * FROM user WHERE userID = ?";
$stmt = $conn->prepare($userSql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();

if($userResult->num_rows == 0) {
    echo "User not found";
    exit;
}

$user = $userResult->fetch_assoc();
$profilePic = !empty($user["profile_picture"]) ? "uploads/profiles/".$user["profile_picture"] : "uploads/profiles/default-avatar.png";
?>

<div class="user-details">
    <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 20px;">
        <img src="<?php echo $profilePic; ?>" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 10px;">
        <h3 style="color: #6c52a1;"><?php echo $user["name"]; ?></h3>
        <p style="color: #666;"><?php echo $user["email"]; ?></p>
        <div style="background-color: #6c52a1; color: white; padding: 4px 12px; border-radius: 12px; font-size: 0.8rem;">
            <?php echo $user["proficiency_level"]; ?>
        </div>
        <p style="font-size: 0.8rem; margin-top: 8px;">Member since: <?php echo date("M d, Y", strtotime($user["created_at"])); ?></p>
    </div>
    
    <div class="nav-tabs">
        <div class="nav-link tab-link active" data-tab="quizzes">Quizzes</div>
        <div class="nav-link tab-link" data-tab="flashcards">Flashcards</div>
        <div class="nav-link tab-link" data-tab="achievements">Achievements</div>
        <div class="nav-link tab-link" data-tab="chatbot">Chatbot History</div>
    </div>
    
    <!-- Quizzes Tab -->
    <div id="quizzes" class="tab-content active">
        <?php
        // Fetch user's quiz results
        $quizSql = "SELECT qr.*, q.title FROM quizresult qr 
                  JOIN quiz q ON qr.quizID = q.quizID 
                  WHERE qr.userID = ? 
                  ORDER BY qr.completed_date DESC";
        $quizStmt = $conn->prepare($quizSql);
        $quizStmt->bind_param("i", $userId);
        $quizStmt->execute();
        $quizResult = $quizStmt->get_result();
        
        if($quizResult->num_rows > 0) {
            echo '<table style="width: 100%; border-collapse: collapse;">
                    <thead style="background-color: #6c52a1; color: white;">
                        <tr>
                            <th style="padding: 10px; text-align: left;">Quiz Title</th>
                            <th style="padding: 10px; text-align: left;">Score</th>
                            <th style="padding: 10px; text-align: left;">Status</th>
                            <th style="padding: 10px; text-align: left;">Completed Date</th>
                        </tr>
                    </thead>
                    <tbody>';
                    
            while($quiz = $quizResult->fetch_assoc()) {
                $status = $quiz["passed"] ? 
                    '<span style="background-color: #4caf50; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.8rem;">Passed</span>' : 
                    '<span style="background-color: #f44336; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.8rem;">Failed</span>';
                echo "<tr style='border-bottom: 1px solid #ddd;'>
                        <td style='padding: 10px;'>".$quiz["title"]."</td>
                        <td style='padding: 10px;'>".$quiz["score"]."%</td>
                        <td style='padding: 10px;'>".$status."</td>
                        <td style='padding: 10px;'>".date("M d, Y", strtotime($quiz["completed_date"]))."</td>
                      </tr>";
            }
            
            echo '</tbody></table>';
        } else {
            echo '<div style="background-color: #d1c4e9; padding: 15px; border-radius: 8px; text-align: center;">No quiz results found</div>';
        }
        ?>
    </div>
    
    <!-- Flashcards Tab -->
    <div id="flashcards" class="tab-content">
        <?php
        // Fetch user's flashcards
        $flashcardSql = "SELECT * FROM flashcard WHERE userID = ? ORDER BY created_at DESC";
        $flashcardStmt = $conn->prepare($flashcardSql);
        $flashcardStmt->bind_param("i", $userId);
        $flashcardStmt->execute();
        $flashcardResult = $flashcardStmt->get_result();
        
        if($flashcardResult->num_rows > 0) {
            echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">';
            
            while($card = $flashcardResult->fetch_assoc()) {
                echo '<div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <div style="background-color: #6c52a1; color: white; padding: 8px 12px;">
                            <strong>'.$card["vocabulary"].'</strong>
                        </div>
                        <div style="padding: 12px;">
                            <p><strong>Meaning:</strong> '.$card["meaning"].'</p>
                            <p><strong>Example:</strong> <em>'.$card["example"].'</em></p>
                            <div style="font-size: 0.7rem; color: #666; margin-top: 8px;">Created: '.date("M d, Y", strtotime($card["created_at"])).'</div>
                        </div>
                      </div>';
            }
            
            echo '</div>';
        } else {
            echo '<div style="background-color: #d1c4e9; padding: 15px; border-radius: 8px; text-align: center;">No flashcards found</div>';
        }
        ?>
    </div>
    
    <!-- Achievements Tab -->
    <div id="achievements" class="tab-content">
        <?php
        // Fetch user's achievements
        $achievementSql = "SELECT * FROM achievement WHERE userID = ? ORDER BY earned_date DESC";
        $achievementStmt = $conn->prepare($achievementSql);
        $achievementStmt->bind_param("i", $userId);
        $achievementStmt->execute();
        $achievementResult = $achievementStmt->get_result();
        
        if($achievementResult->num_rows > 0) {
            echo '<div style="display: flex; flex-direction: column; gap: 10px;">';
            
            while($achievement = $achievementResult->fetch_assoc()) {
                echo '<div style="background-color: white; border-radius: 10px; padding: 12px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h5 style="margin: 0; color: #6c52a1;"><i class="fas fa-trophy" style="color: #ffc107; margin-right: 8px;"></i>'.$achievement["achievement_name"].'</h5>
                        </div>
                        <div style="font-size: 0.8rem; color: #666;">'.date("M d, Y", strtotime($achievement["earned_date"])).'</div>
                      </div>';
            }
            
            echo '</div>';
        } else {
            echo '<div style="background-color: #d1c4e9; padding: 15px; border-radius: 8px; text-align: center;">No achievements earned yet</div>';
        }
        ?>
    </div>
    
    <!-- Chatbot History Tab -->
    <div id="chatbot" class="tab-content">
        <?php
        // Fetch user's chatbot interactions
        $chatbotSql = "SELECT ci.*, cc.topic, cc.prompt 
                     FROM chatbotinteraction ci
                     JOIN chatbot_content cc ON ci.chatbot_contentID = cc.chatbot_contentID
                     WHERE ci.userID = ? 
                     ORDER BY ci.timestamp DESC";
        $chatbotStmt = $conn->prepare($chatbotSql);
        $chatbotStmt->bind_param("i", $userId);
        $chatbotStmt->execute();
        $chatbotResult = $chatbotStmt->get_result();
        
        if($chatbotResult->num_rows > 0) {
            while($chat = $chatbotResult->fetch_assoc()) {
                $status = $chat["completed"] ? 
                    '<span style="background-color: #4caf50; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.8rem;">Completed</span>' : 
                    '<span style="background-color: #ff9800; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.8rem;">Incomplete</span>';
                
                echo '<details style="background-color: white; border-radius: 10px; margin-bottom: 10px; overflow: hidden;">
                        <summary style="padding: 12px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background-color: #f0eaf7;">
                            <div style="font-weight: bold; color: #6c52a1;">'.$chat["topic"].'</div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                '.$status.'
                                <span style="font-size: 0.8rem; color: #666;">'.date("M d, Y H:i", strtotime($chat["timestamp"])).'</span>
                            </div>
                        </summary>
                        <div style="padding: 15px; border-top: 1px solid #ddd;">
                            <p><strong>Prompt:</strong> '.$chat["prompt"].'</p>
                            <p style="margin-top: 10px;"><strong>User Response:</strong> '.$chat["user_response"].'</p>
                        </div>
                      </details>';
            }
        } else {
            echo '<div style="background-color: #d1c4e9; padding: 15px; border-radius: 8px; text-align: center;">No chatbot interactions found</div>';
        }
        ?>
    </div>
</div>