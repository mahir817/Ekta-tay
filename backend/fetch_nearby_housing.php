<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

try {
    $currentUserId = $_SESSION['user_id'] ?? null;
    
    if (!$currentUserId) {
        echo json_encode(["success" => false, "message" => "User not logged in"]);
        exit;
    }
    
    // Get user's generalized location
    $userStmt = $pdo->prepare("SELECT generalized_location FROM users WHERE id = ?");
    $userStmt->execute([$currentUserId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    $userGeneralizedLocation = $user['generalized_location'] ?? null;
    
    // Always return all available housing - filtering will be done on frontend
    $stmt = $pdo->prepare("
        SELECT s.service_id, s.title, s.description, s.location, s.price, s.created_at, s.user_id,
               h.housing_id, h.property_type, h.size_sqft, h.floor_no, h.total_floors, h.furnished_status,
               h.bedrooms, h.bathrooms, h.balconies, h.rent, h.service_charge, h.advance_deposit,
               h.available_from, h.available_for, h.negotiable, h.khotiyan, h.verification_status,
               h.availability, h.location as housing_location, h.coordinates, h.generalized_location
        FROM services s
        INNER JOIN housing h ON s.service_id = h.service_id
        WHERE s.type = 'housing' AND h.availability = 'available' AND s.user_id != ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$currentUserId]);
    
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Transform data to match frontend expectations
    $transformedPosts = [];
    $nearbyCount = 0;
    
    foreach ($posts as $post) {
        $isNearby = ($userGeneralizedLocation && $post['generalized_location'] === $userGeneralizedLocation);
        if ($isNearby) {
            $nearbyCount++;
        }
        
        $transformedPosts[] = [
            'id' => $post['service_id'],
            'housing_id' => $post['housing_id'],
            'title' => $post['title'],
            'location' => $post['location'],
            'housing_location' => $post['housing_location'],
            'rent' => number_format($post['rent']),
            'description' => $post['description'],
            'property_type' => $post['property_type'],
            'bedrooms' => $post['bedrooms'],
            'bathrooms' => $post['bathrooms'],
            'size_sqft' => $post['size_sqft'],
            'furnished_status' => $post['furnished_status'],
            'available_from' => $post['available_from'],
            'available_for' => $post['available_for'],
            'negotiable' => $post['negotiable'],
            'khotiyan' => $post['khotiyan'],
            'verification_status' => $post['verification_status'],
            'availability' => $post['availability'],
            'coordinates' => $post['coordinates'],
            'generalized_location' => $post['generalized_location'],
            'is_nearby' => $isNearby,
            'is_owner' => false, // User can't be owner of nearby housing
            'created_at' => $post['created_at']
        ];
    }

    echo json_encode([
        "success" => true,
        "housing" => $transformedPosts,
        "nearby_count" => $nearbyCount,
        "user_area" => $userGeneralizedLocation
    ]);
    
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
