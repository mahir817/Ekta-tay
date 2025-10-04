<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(["success" => false, "message" => "Housing ID is required"]);
    exit;
}

$housingId = $_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT s.service_id, s.title, s.description, s.location, s.price, s.created_at,
               h.property_type, h.size_sqft, h.floor_no, h.total_floors, h.furnished_status,
               h.bedrooms, h.bathrooms, h.balconies, h.rent, h.service_charge, h.advance_deposit,
               h.available_from, h.available_for, h.negotiable, h.khotiyan, h.verification_status,
               h.status as housing_status
        FROM services s
        INNER JOIN housing h ON s.service_id = h.service_id
        WHERE s.service_id = ? AND s.type = 'housing'
    ");
    $stmt->execute([$housingId]);
    $housing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$housing) {
        echo json_encode(["success" => false, "message" => "Housing not found"]);
        exit;
    }

    // Transform data to match frontend expectations
    $transformedHousing = [
        'id' => $housing['service_id'],
        'title' => $housing['title'],
        'location' => $housing['location'],
        'rent' => number_format($housing['rent']),
        'description' => $housing['description'],
        'property_type' => $housing['property_type'],
        'bedrooms' => $housing['bedrooms'],
        'bathrooms' => $housing['bathrooms'],
        'size_sqft' => $housing['size_sqft'],
        'furnished_status' => $housing['furnished_status'],
        'available_from' => $housing['available_from'],
        'available_for' => $housing['available_for'],
        'negotiable' => $housing['negotiable'],
        'khotiyan' => $housing['khotiyan'],
        'verification_status' => $housing['verification_status'],
        'status' => $housing['housing_status'],
        'service_charge' => $housing['service_charge'],
        'advance_deposit' => $housing['advance_deposit'],
        'balconies' => $housing['balconies'],
        'floor_no' => $housing['floor_no'],
        'total_floors' => $housing['total_floors'],
        'created_at' => $housing['created_at']
    ];

    echo json_encode(["success" => true, "housing" => $transformedHousing]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
