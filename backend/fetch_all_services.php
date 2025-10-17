<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

try {
    // Get all services with their specific details
    $stmt = $pdo->prepare("
        SELECT s.service_id, s.title, s.description, s.type, s.price, s.location, s.created_at, s.user_id,
               h.property_type, h.bedrooms, h.bathrooms, h.furnished_status, h.generalized_location,
               j.job_type, j.company, j.work_type,
               t.subject AS tuition_subject, t.class_level AS tuition_class_level, t.tuition_type, t.hourly_rate,
               f.food_type, f.provider_name, f.available_date
        FROM services s
        LEFT JOIN housing h ON s.service_id = h.service_id
        LEFT JOIN jobs j ON s.service_id = j.service_id
        LEFT JOIN tuitions t ON s.service_id = t.service_id
        LEFT JOIN food_services f ON s.service_id = f.service_id
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "services" => $services
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error fetching services: " . $e->getMessage()
    ]);
}
?>
