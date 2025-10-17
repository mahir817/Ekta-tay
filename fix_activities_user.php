<?php
session_start();
require_once "backend/db.php";

echo "<h2>Fix Activities for Current User</h2>";

// Get current user ID
$currentUserId = $_SESSION['user_id'] ?? null;

if (!$currentUserId) {
    echo "<p style='color: red;'>No user logged in. Please login first.</p>";
    echo "<p><a href='Login Page/login.html'>Go to Login</a></p>";
    exit;
}

echo "<p><strong>Current User ID:</strong> $currentUserId</p>";

try {
    // Check existing activities for current user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_activities WHERE user_id = ?");
    $stmt->execute([$currentUserId]);
    $existingCount = $stmt->fetchColumn();
    
    echo "<p><strong>Existing activities for user $currentUserId:</strong> $existingCount</p>";
    
    if ($existingCount == 0) {
        echo "<h3>Adding sample activities for user $currentUserId...</h3>";
        
        // Add sample activities for current user
        $activities = [
            ['housing_application', 'Applied for housing', 'Applied to: Beautiful 2BHK apartment', 1, '🏠', 'DATE_SUB(NOW(), INTERVAL 1 HOUR)'],
            ['expense_added', 'Added new expense', 'Added: Monthly groceries - ৳3,500', 2, '💳', 'DATE_SUB(NOW(), INTERVAL 2 HOUR)'],
            ['housing_post', 'Posted new housing', 'Posted: Cozy studio apartment', 3, '🏠', 'DATE_SUB(NOW(), INTERVAL 3 HOUR)'],
            ['profile_update', 'Updated profile', 'Updated contact information', null, '👤', 'DATE_SUB(NOW(), INTERVAL 4 HOUR)'],
            ['login', 'Logged in', 'Logged into the system', null, '🔐', 'DATE_SUB(NOW(), INTERVAL 5 HOUR)']
        ];
        
        foreach ($activities as $activity) {
            $stmt = $pdo->prepare("INSERT INTO user_activities (user_id, activity_type, activity_title, activity_description, related_id, icon, created_at) VALUES (?, ?, ?, ?, ?, ?, {$activity[5]})");
            $stmt->execute([$currentUserId, $activity[0], $activity[1], $activity[2], $activity[3], $activity[4]]);
            echo "✓ Added: {$activity[1]}<br>";
        }
        
        echo "<div style='background: green; color: white; padding: 10px; margin: 10px;'>";
        echo "✅ Successfully added sample activities for user $currentUserId!";
        echo "</div>";
    } else {
        echo "<p>User already has activities. Let's check what they are:</p>";
        
        $stmt = $pdo->prepare("SELECT * FROM user_activities WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$currentUserId]);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Icon</th><th>Title</th><th>Description</th><th>Time</th></tr>";
        foreach ($activities as $activity) {
            echo "<tr>";
            echo "<td>{$activity['icon']}</td>";
            echo "<td>{$activity['activity_title']}</td>";
            echo "<td>{$activity['activity_description']}</td>";
            echo "<td>{$activity['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Also check all users in the system
    echo "<h3>All Users in System:</h3>";
    $stmt = $pdo->query("SELECT id, name, email FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Email</th><th>Activities Count</th></tr>";
    foreach ($users as $user) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_activities WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $activityCount = $stmt->fetchColumn();
        
        $highlight = ($user['id'] == $currentUserId) ? 'style="background: #e8f5e8;"' : '';
        echo "<tr $highlight>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>$activityCount</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Fix Complete!</h3>";
    echo "<p><a href='Dashboard/dashboard.php'>Go to Dashboard</a> to see your activities</p>";
    echo "<p><a href='test_activities.php'>Test Activities Page</a></p>";

} catch (Exception $e) {
    echo "<div style='background: red; color: white; padding: 10px; margin: 10px;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; width: 100%; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
h2, h3 { color: #333; }
</style>
