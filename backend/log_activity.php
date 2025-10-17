<?php
// Activity logging utility
require_once __DIR__ . '/db.php';

function logActivity($userId, $activityType, $title, $description = null, $relatedId = null, $icon = null) {
    global $pdo;
    
    // Default icons for different activity types
    $defaultIcons = [
        'housing_post' => '🏠',
        'job_post' => '💼',
        'tuition_post' => '📚',
        'food_post' => '🍽️',
        'housing_application' => '🏠',
        'job_application' => '💼',
        'expense_added' => '💳',
        'profile_update' => '👤',
        'login' => '🔐',
        'logout' => '🚪'
    ];
    
    $icon = $icon ?: ($defaultIcons[$activityType] ?? '📝');
    
    try {
        // Ensure activities table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_activities (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            activity_type ENUM('housing_post','job_post','tuition_post','food_post','housing_application','job_application','expense_added','profile_update','login','logout') NOT NULL,
            activity_title VARCHAR(255) NOT NULL,
            activity_description TEXT DEFAULT NULL,
            related_id INT(11) DEFAULT NULL,
            icon VARCHAR(10) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY activity_type (activity_type),
            KEY created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        $stmt = $pdo->prepare("INSERT INTO user_activities (user_id, activity_type, activity_title, activity_description, related_id, icon) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $activityType, $title, $description, $relatedId, $icon]);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

function getRecentActivities($userId, $limit = 5) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT activity_title as title, 
                   activity_description as description,
                   icon,
                   CASE 
                       WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN CONCAT(TIMESTAMPDIFF(MINUTE, created_at, NOW()), ' minutes ago')
                       WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN CONCAT(TIMESTAMPDIFF(HOUR, created_at, NOW()), ' hours ago')
                       WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN CONCAT(TIMESTAMPDIFF(DAY, created_at, NOW()), ' days ago')
                       ELSE DATE_FORMAT(created_at, '%M %d, %Y')
                   END as time
            FROM user_activities 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to get activities: " . $e->getMessage());
        return [];
    }
}

// If called directly via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    session_start();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit();
    }
    
    $action = $_POST['action'];
    $userId = $_SESSION['user_id'];
    
    if ($action === 'log') {
        $activityType = $_POST['type'] ?? '';
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? null;
        $relatedId = $_POST['related_id'] ?? null;
        $icon = $_POST['icon'] ?? null;
        
        $success = logActivity($userId, $activityType, $title, $description, $relatedId, $icon);
        echo json_encode(['success' => $success]);
    } elseif ($action === 'get') {
        $limit = (int)($_POST['limit'] ?? 5);
        $activities = getRecentActivities($userId, $limit);
        echo json_encode(['success' => true, 'activities' => $activities]);
    }
}
?>
