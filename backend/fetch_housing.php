<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

try {
    $currentUserId = $_SESSION['user_id'] ?? null;
    
    // Exclude current user's posts from Find House section
    if ($currentUserId) {
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
    } else {
        // If no user is logged in, show all posts
        $stmt = $pdo->prepare("
            SELECT s.service_id, s.title, s.description, s.location, s.price, s.created_at, s.user_id,
                   h.housing_id, h.property_type, h.size_sqft, h.floor_no, h.total_floors, h.furnished_status,
                   h.bedrooms, h.bathrooms, h.balconies, h.rent, h.service_charge, h.advance_deposit,
                   h.available_from, h.available_for, h.negotiable, h.khotiyan, h.verification_status,
                   h.availability, h.location as housing_location, h.coordinates, h.generalized_location
            FROM services s
            INNER JOIN housing h ON s.service_id = h.service_id
            WHERE s.type = 'housing' AND h.availability = 'available'
            ORDER BY s.created_at DESC
        ");
        $stmt->execute();
    }
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Transform data to match frontend expectations
    $currentUserId = $_SESSION['user_id'] ?? null;
    $transformedPosts = [];
    foreach ($posts as $post) {
        $transformedPosts[] = [
            'id' => $post['service_id'],
            'housing_id' => $post['housing_id'],
            'title' => $post['title'],
            'location' => $post['location'],
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
            'housing_location' => $post['housing_location'],
            'coordinates' => $post['coordinates'],
            'generalized_location' => $post['generalized_location'],
            'is_owner' => ($currentUserId && $post['user_id'] == $currentUserId),
            'created_at' => $post['created_at']
        ];
    }

    echo json_encode($transformedPosts);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
