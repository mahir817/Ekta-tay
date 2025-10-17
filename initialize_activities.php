<?php
session_start();
require_once "backend/db.php";
require_once "backend/log_activity.php";

// Set test user for initialization
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 2; // Change to your user ID
}

$userId = $_SESSION['user_id'];

echo "<h2>Initializing Sample Activities</h2>";

try {
    // Create the activities table if it doesn't exist
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
    
    echo "✓ Activities table created/verified<br>";

    // Clear existing activities for this user
    $pdo->prepare("DELETE FROM user_activities WHERE user_id = ?")->execute([$userId]);
    echo "✓ Cleared existing activities<br>";

    // Add sample activities
    $sampleActivities = [
        ['housing_post', 'Posted new housing', 'Posted a 2BHK apartment in Dhaka North', 1, '🏠', 'DATE_SUB(NOW(), INTERVAL 2 HOUR)'],
        ['expense_added', 'Added new expense', 'Added rent payment of ৳25,000', 1, '💳', 'DATE_SUB(NOW(), INTERVAL 1 HOUR)'],
        ['job_application', 'Applied for job', 'Applied for Software Developer position', 1, '💼', 'DATE_SUB(NOW(), INTERVAL 3 HOUR)'],
        ['profile_update', 'Updated profile', 'Updated contact information', null, '👤', 'DATE_SUB(NOW(), INTERVAL 5 HOUR)'],
        ['login', 'Logged in', 'Logged into the system', null, '🔐', 'DATE_SUB(NOW(), INTERVAL 6 HOUR)']
    ];

    foreach ($sampleActivities as $activity) {
        $stmt = $pdo->prepare("INSERT INTO user_activities (user_id, activity_type, activity_title, activity_description, related_id, icon, created_at) VALUES (?, ?, ?, ?, ?, ?, {$activity[5]})");
        $stmt->execute([$userId, $activity[0], $activity[1], $activity[2], $activity[3], $activity[4]]);
        echo "✓ Added: {$activity[1]}<br>";
    }

    echo "<h3>✅ Sample activities initialized successfully!</h3>";
    echo "<p>User ID: $userId</p>";
    echo "<p><a href='Dashboard/dashboard.php'>Go to Dashboard</a> to see the activities</p>";

    // Test the getRecentActivities function
    echo "<h3>Recent Activities Preview:</h3>";
    $activities = getRecentActivities($userId, 5);
    if ($activities) {
        echo "<ul>";
        foreach ($activities as $activity) {
            echo "<li>{$activity['icon']} {$activity['title']} - {$activity['time']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No activities found</p>";
    }

} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
</style>
