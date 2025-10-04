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

$housingId = $input['housing_id'];
$userId = $_SESSION['user_id'];

try {
    // Check if housing exists
    $stmt = $pdo->prepare("SELECT service_id FROM services WHERE service_id = ? AND type = 'housing'");
    $stmt->execute([$housingId]);
    if (!$stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Housing not found"]);
        exit;
    }

    // Check if user already applied
    $stmt = $pdo->prepare("SELECT application_id FROM housing_applications WHERE housing_id = ? AND applicant_id = ?");
    $stmt->execute([$housingId, $userId]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "You have already applied for this housing"]);
        exit;
    }

    // Insert application
    $stmt = $pdo->prepare("INSERT INTO housing_applications (housing_id, applicant_id, status, message) VALUES (?, ?, 'pending', 'Application submitted')");
    $stmt->execute([$housingId, $userId]);

    echo json_encode(["success" => true, "message" => "Application submitted successfully"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>
