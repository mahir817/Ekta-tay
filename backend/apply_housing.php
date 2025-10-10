<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Please login to apply for housing"]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['housing_id'])) {
    echo json_encode(["success" => false, "message" => "Housing ID is required"]);
    exit;
}

$serviceId = $input['housing_id']; // This is actually service_id from frontend
$userId = $_SESSION['user_id'];
$message = $input['message'] ?? 'Application submitted';

try {
    // Get housing information using service_id
    $stmt = $pdo->prepare("
        SELECT s.user_id as owner_id, h.housing_id, h.availability 
        FROM services s 
        INNER JOIN housing h ON s.service_id = h.service_id 
        WHERE s.service_id = ? AND s.type = 'housing'
    ");
    $stmt->execute([$serviceId]);
    $housing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$housing) {
        echo json_encode(["success" => false, "message" => "Housing not found"]);
        exit;
    }
    
    if ($housing['availability'] !== 'available') {
        echo json_encode(["success" => false, "message" => "Housing is no longer available"]);
        exit;
    }
    
    if ($housing['owner_id'] == $userId) {
        echo json_encode(["success" => false, "message" => "You cannot apply to your own housing"]);
        exit;
    }

    // Check if user already applied (using housing_id)
    $stmt = $pdo->prepare("SELECT application_id FROM housing_applications WHERE housing_id = ? AND applicant_id = ?");
    $stmt->execute([$housing['housing_id'], $userId]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "You have already applied for this housing"]);
        exit;
    }

    // Insert application with owner_id (using housing_id)
    $stmt = $pdo->prepare("
        INSERT INTO housing_applications (housing_id, owner_id, applicant_id, status, message) 
        VALUES (?, ?, ?, 'pending', ?)
    ");
    $stmt->execute([$housing['housing_id'], $housing['owner_id'], $userId, $message]);

    echo json_encode(["success" => true, "message" => "Application submitted successfully"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>
