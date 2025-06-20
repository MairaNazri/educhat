<?php
// detailUser.php - Script to fetch detailed user information
if(!isset($_GET['userId']) || empty($_GET['userId'])) {
    echo "Invalid user ID";
    exit;
}

// Include server connection file (same as other admin pages)
include 'server.php';

// Check if connection exists and is working
if (!isset($conn)) {
    echo "Database connection not established. Check server.php file.";
    exit;
}

$userId = $_GET['userId'];

// Initialize variables
$user = null;
$profilePic = null;

try {
    // Fetch user basic info
    $userSql = "SELECT * FROM user WHERE userID = ?";
    $stmt = $conn->prepare($userSql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare user query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userResult = $stmt->get_result();

    if($userResult->num_rows == 0) {
        echo "User not found";
        exit;
    }

    $user = $userResult->fetch_assoc();
    $stmt->close();

    // Handle profile picture path - check multiple possible locations (same logic as manageUser.php)
    if (!empty($user["profile_picture"])) {
        // Check if it already starts with uploads/
        if (strpos($user["profile_picture"], 'uploads/') === 0) {
            $profilePic = $user["profile_picture"];
        } else {
            $profilePic = "uploads/" . $user["profile_picture"];
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

} catch (Exception $e) {
    echo "Error loading user details: " . htmlspecialchars($e->getMessage());
    exit;
}
?>

<!-- Add CSS for profile picture fallback -->
<style>
.profile-pic-large {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #6c52a1;
    background-color: #f0f0f0;
}

.profile-pic-fallback-large {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background-color: #6c52a1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 36px;
    border: 3px solid #6c52a1;
}

.error-message {
    background-color: #ffebee;
    color: #c62828;
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
    border: 1px solid #ffcdd2;
}

.no-data-message {
    background-color: #d1c4e9;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    color: #6c52a1;
}
</style>

<div class="user-details">
    <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 20px;">
        <?php if ($profilePic && file_exists($profilePic)): ?>
            <img src="<?php echo htmlspecialchars($profilePic); ?>" class="profile-pic-large" alt="Profile Picture" 
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="profile-pic-fallback-large" style="display: none;">
                <?php echo strtoupper(substr($user["name"], 0, 1)); ?>
            </div>
        <?php else: ?>
            <div class="profile-pic-fallback-large">
                <?php echo strtoupper(substr($user["name"], 0, 1)); ?>
            </div>
        <?php endif; ?>
        
        <h3 style="color: #6c52a1; margin-top: 10px;"><?php echo htmlspecialchars($user["name"]); ?></h3>
        <p style="color: #666;"><?php echo htmlspecialchars($user["email"]); ?></p>
        <div style="background-color: #6c52a1; color: white; padding: 4px 12px; border-radius: 12px; font-size: 0.8rem;">
            <?php echo htmlspecialchars($user["proficiency_level"] ?? 'Not Set'); ?>
        </div>
        <p style="font-size: 0.8rem; margin-top: 8px;">
            Member since: <?php echo isset($user["created_at"]) ? date("M d, Y", strtotime($user["created_at"])) : 'Unknown'; ?>
        </p>
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
        try {
            // Fetch user's quiz results
            $quizSql = "SELECT qr.*, q.title FROM quizresult qr 
                      JOIN quiz q ON qr.quizID = q.quizID 
                      WHERE qr.userID = ? 
                      ORDER BY qr.completed_date DESC";
            $quizStmt = $conn->prepare($quizSql);
            
            if (!$quizStmt) {
                throw new Exception("Failed to prepare quiz query: " . $conn->error);
            }
            
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
                            <td style='padding: 10px;'>".htmlspecialchars($quiz["title"])."</td>
                            <td style='padding: 10px;'>".htmlspecialchars($quiz["score"])."%</td>
                            <td style='padding: 10px;'>".$status."</td>
                            <td style='padding: 10px;'>".date("M d, Y", strtotime($quiz["completed_date"]))."</td>
                          </tr>";
                }
                
                echo '</tbody></table>';
            } else {
                echo '<div class="no-data-message">No quiz results found</div>';
            }
            $quizStmt->close();
            
        } catch (Exception $e) {
            echo '<div class="error-message">Error loading quiz data: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
    
    <!-- Flashcards Tab -->
    <div id="flashcards" class="tab-content">
        <?php
        try {
            // Fetch user's flashcards
            $flashcardSql = "SELECT * FROM flashcard WHERE userID = ? ORDER BY flashcardID DESC";
            $flashcardStmt = $conn->prepare($flashcardSql);
            
            if (!$flashcardStmt) {
                throw new Exception("Failed to prepare flashcard query: " . $conn->error);
            }
            
            $flashcardStmt->bind_param("i", $userId);
            $flashcardStmt->execute();
            $flashcardResult = $flashcardStmt->get_result();
            
            if($flashcardResult->num_rows > 0) {
                echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">';
                
                while($card = $flashcardResult->fetch_assoc()) {
                    echo '<div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                            <div style="background-color: #6c52a1; color: white; padding: 8px 12px;">
                                <strong>'.htmlspecialchars($card["vocabulary"]).'</strong>
                            </div>
                            <div style="padding: 12px;">
                                <p><strong>Meaning:</strong> '.htmlspecialchars($card["meaning"]).'</p>
                                <p><strong>Example:</strong> <em>'.htmlspecialchars($card["example"]).'</em></p>
                            </div>
                          </div>';
                }
                
                echo '</div>';
            } else {
                echo '<div class="no-data-message">No flashcards found</div>';
            }
            $flashcardStmt->close();
            
        } catch (Exception $e) {
            echo '<div class="error-message">Error loading flashcard data: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
    
    <!-- Achievements Tab -->
    <div id="achievements" class="tab-content">
        <?php
        try {
            // Fetch user's achievements
            $achievementSql = "SELECT * FROM achievement WHERE userID = ? ORDER BY earned_date DESC";
            $achievementStmt = $conn->prepare($achievementSql);
            
            if (!$achievementStmt) {
                throw new Exception("Failed to prepare achievement query: " . $conn->error);
            }
            
            $achievementStmt->bind_param("i", $userId);
            $achievementStmt->execute();
            $achievementResult = $achievementStmt->get_result();
            
            if($achievementResult->num_rows > 0) {
                echo '<div style="display: flex; flex-direction: column; gap: 10px;">';
                
                while($achievement = $achievementResult->fetch_assoc()) {
                    echo '<div style="background-color: white; border-radius: 10px; padding: 12px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h5 style="margin: 0; color: #6c52a1;"><i class="fas fa-trophy" style="color: #ffc107; margin-right: 8px;"></i>'.htmlspecialchars($achievement["achievement_name"]).'</h5>
                            </div>
                            <div style="font-size: 0.8rem; color: #666;">'.date("M d, Y", strtotime($achievement["earned_date"])).'</div>
                          </div>';
                }
                
                echo '</div>';
            } else {
                echo '<div class="no-data-message">No achievements earned yet</div>';
            }
            $achievementStmt->close();
            
        } catch (Exception $e) {
            echo '<div class="error-message">Error loading achievement data: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
    
    <!-- Chatbot History Tab -->
    <div id="chatbot" class="tab-content">
        <?php
        try {
            // Fetch user's chatbot interactions with progress status
            $chatbotSql = "SELECT ct.title as topic, cup.is_completed, cup.last_updated, cs.prompt
                         FROM chatbot_user_progress cup
                         JOIN chatbot_topic ct ON cup.topicID = ct.topicID
                         LEFT JOIN chatbot_step cs ON cup.current_stepID = cs.stepID
                         WHERE cup.userID = ? 
                         ORDER BY cup.last_updated DESC";
            $chatbotStmt = $conn->prepare($chatbotSql);
            
            if (!$chatbotStmt) {
                throw new Exception("Failed to prepare chatbot query: " . $conn->error);
            }
            
            $chatbotStmt->bind_param("i", $userId);
            $chatbotStmt->execute();
            $chatbotResult = $chatbotStmt->get_result();
            
            if($chatbotResult->num_rows > 0) {
                while($chat = $chatbotResult->fetch_assoc()) {
                    $status = $chat["is_completed"] ? 
                        '<span style="background-color: #4caf50; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.8rem;">Completed</span>' : 
                        '<span style="background-color: #ff9800; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.8rem;">In Progress</span>';
                    
                    // Get recent interactions for this topic
                    $interactionSql = "SELECT ci.user_response, ci.timestamp, cs.prompt
                                     FROM chatbot_interaction ci
                                     JOIN chatbot_step cs ON ci.stepID = cs.stepID
                                     JOIN chatbot_topic ct ON cs.topicID = ct.topicID
                                     WHERE ci.userID = ? AND ct.title = ?
                                     ORDER BY ci.timestamp DESC LIMIT 1";
                    $interactionStmt = $conn->prepare($interactionSql);
                    
                    if ($interactionStmt) {
                        $interactionStmt->bind_param("is", $userId, $chat["topic"]);
                        $interactionStmt->execute();
                        $interactionResult = $interactionStmt->get_result();
                        $interaction = $interactionResult->fetch_assoc();
                        $interactionStmt->close();
                    } else {
                        $interaction = null;
                    }
                    
                    echo '<details style="background-color: white; border-radius: 10px; margin-bottom: 10px; overflow: hidden;">
                            <summary style="padding: 12px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background-color: #f0eaf7;">
                                <div style="font-weight: bold; color: #6c52a1;">'.htmlspecialchars($chat["topic"]).'</div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    '.$status.'
                                    <span style="font-size: 0.8rem; color: #666;">'.date("M d, Y H:i", strtotime($chat["last_updated"])).'</span>
                                </div>
                            </summary>
                            <div style="padding: 15px; border-top: 1px solid #ddd;">';
                    
                    if($interaction) {
                        echo '<p><strong>Current/Last Prompt:</strong> '.htmlspecialchars($interaction["prompt"]).'</p>
                              <p style="margin-top: 10px;"><strong>User Response:</strong> '.htmlspecialchars($interaction["user_response"]).'</p>';
                    } else {
                        echo '<p><strong>Current Prompt:</strong> '.($chat["prompt"] ? htmlspecialchars($chat["prompt"]) : 'Topic started but no interactions yet').'</p>';
                    }
                    
                    echo '</div>
                          </details>';
                }
            } else {
                echo '<div class="no-data-message">No chatbot interactions found</div>';
            }
            $chatbotStmt->close();
            
        } catch (Exception $e) {
            echo '<div class="error-message">Error loading chatbot data: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
</div>

<?php
// Don't close connection here as it might be used elsewhere
?>