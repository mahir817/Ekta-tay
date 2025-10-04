<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT s.service_id, s.title, s.description, s.location, s.price, s.created_at,
               h.property_type, h.size_sqft, h.floor_no, h.total_floors, h.furnished_status,
               h.bedrooms, h.bathrooms, h.balconies, h.rent, h.service_charge, h.advance_deposit,
               h.available_from, h.available_for, h.negotiable, h.khotiyan, h.verification_status,
               h.status as housing_status
        FROM services s
        INNER JOIN housing h ON s.service_id = h.service_id
        WHERE s.type = 'housing'
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Transform data to match frontend expectations
    $transformedPosts = [];
    foreach ($posts as $post) {
        $transformedPosts[] = [
            'id' => $post['service_id'],
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
            'status' => $post['housing_status'],
            'created_at' => $post['created_at']
        ];
    }

    echo json_encode($transformedPosts);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
