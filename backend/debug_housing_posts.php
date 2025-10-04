<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

echo "Debug Information:\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID in session: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    echo "User ID: $userId\n";
    
    // Check if user exists
    $userStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    echo "User found: " . ($user ? $user['name'] : 'NOT FOUND') . "\n";
    
    // Check services for this user
    $servicesStmt = $pdo->prepare("SELECT service_id, title, type FROM services WHERE user_id = ?");
    $servicesStmt->execute([$userId]);
    $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Total services for user: " . count($services) . "\n";
    
    foreach ($services as $service) {
        echo "- Service ID: {$service['service_id']}, Title: {$service['title']}, Type: {$service['type']}\n";
    }
    
    // Check housing services specifically
    $housingStmt = $pdo->prepare("SELECT service_id, title, type FROM services WHERE user_id = ? AND type = 'housing'");
    $housingStmt->execute([$userId]);
    $housingServices = $housingStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Housing services for user: " . count($housingServices) . "\n";
    
    foreach ($housingServices as $service) {
        echo "- Housing Service ID: {$service['service_id']}, Title: {$service['title']}\n";
    }
    
    // Check the JOIN query
    $stmt = $pdo->prepare("
        SELECT s.service_id, s.title, s.description, s.location, s.price, s.created_at,
               h.rent, h.property_type, h.bedrooms, h.bathrooms, h.furnished_status
        FROM services s
        INNER JOIN housing h ON s.service_id = h.service_id
        WHERE s.user_id = ? AND s.type = 'housing'
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$userId]);
    $userHousing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Joined query results: " . count($userHousing) . "\n";
    
    foreach ($userHousing as $post) {
        echo "- Housing Post: {$post['title']} (Service ID: {$post['service_id']})\n";
    }
    
    // Check all housing records
    $allHousingStmt = $pdo->query("SELECT service_id FROM housing");
    $allHousing = $allHousingStmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Total housing records in database: " . count($allHousing) . "\n";
    echo "Housing service IDs: " . implode(', ', $allHousing) . "\n";
    
} else {
    echo "No user session found!\n";
}
?>
