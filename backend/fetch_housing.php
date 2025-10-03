<?php
session_start();
require_once "../../backend/db.php";

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT s.id as service_id, s.title, s.description, s.location, s.price, s.created_at,
               h.property_type, h.size_sqft, h.floor_no, h.total_floors, h.furnished_status,
               h.bedrooms, h.bathrooms, h.balconies, h.rent, h.service_charge, h.advance_deposit,
               h.available_from, h.available_for, h.negotiable
        FROM services s
        INNER JOIN housing h ON s.id = h.service_id
        WHERE s.type = 'housing'
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $posts]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
