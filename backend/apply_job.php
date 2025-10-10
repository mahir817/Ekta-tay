<?php
session_start();
include 'db.php';

// Set response content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to apply for jobs']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Validate required fields
    if (empty($_POST['service_id']) || empty($_POST['cover_letter'])) {
        echo json_encode(['success' => false, 'message' => 'Service ID and cover letter are required']);
        exit;
    }

    $service_id = intval($_POST['service_id']);
    $cover_letter = trim($_POST['cover_letter']);
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Verify service exists and is a job or tuition
    $sql = "SELECT s.type FROM services s 
            INNER JOIN jobs j ON s.service_id = j.service_id 
            WHERE s.service_id = ? AND s.type IN ('job', 'tuition')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$service_id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        echo json_encode(['success' => false, 'message' => 'Service not found or not available for applications']);
        exit;
    }

    // Check if user already applied for this service
    $sql = "SELECT application_id FROM job_applications WHERE service_id = ? AND applicant_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$service_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already applied for this position']);
        exit;
    }

    // Insert application into job_applications table
    $sql = "INSERT INTO job_applications (service_id, applicant_id, cover_letter, 
            phone, email, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$service_id, $user_id, $cover_letter, $phone, $email]);

    echo json_encode([
        'success' => true, 
        'message' => 'Application submitted successfully!'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error submitting application: ' . $e->getMessage()
    ]);
}
?>
