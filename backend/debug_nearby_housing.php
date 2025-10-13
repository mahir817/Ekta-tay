<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

try {
    $currentUserId = $_SESSION['user_id'] ?? null;
    
    if (!$currentUserId) {
        echo json_encode(["error" => "User not logged in"]);
        exit;
    }
    
    // Get user's generalized location
    $userStmt = $pdo->prepare("SELECT id, name, location, generalized_location FROM users WHERE id = ?");
    $userStmt->execute([$currentUserId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get all housing with generalized locations
    $housingStmt = $pdo->prepare("
        SELECT s.service_id, s.title, s.user_id, h.generalized_location, h.location as housing_location
        FROM services s
        INNER JOIN housing h ON s.service_id = h.service_id
        WHERE s.type = 'housing' AND h.availability = 'available'
        ORDER BY s.created_at DESC
    ");
    $housingStmt->execute();
    $housing = $housingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count nearby housing
    $userLocation = $user['generalized_location'] ?? null;
    $nearbyCount = 0;
    $nearbyHousing = [];
    
    foreach ($housing as $house) {
        if ($house['generalized_location'] === $userLocation && $house['user_id'] != $currentUserId) {
            $nearbyCount++;
            $nearbyHousing[] = $house;
        }
    }
    
    echo json_encode([
        "current_user" => $user,
        "user_generalized_location" => $userLocation,
        "total_housing_posts" => count($housing),
        "nearby_count" => $nearbyCount,
        "nearby_housing" => $nearbyHousing,
        "all_housing_locations" => array_map(function($h) {
            return [
                'title' => $h['title'],
                'generalized_location' => $h['generalized_location'],
                'housing_location' => $h['housing_location'],
                'user_id' => $h['user_id']
            ];
        }, $housing)
    ]);
    
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
