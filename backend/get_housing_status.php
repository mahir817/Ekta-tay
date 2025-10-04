<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Please login to view status"]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Get user's housing applications with housing details
    $stmt = $pdo->prepare("
        SELECT 
            ha.application_id,
            ha.status,
            ha.message,
            ha.created_at as applied_date,
            s.title,
            s.location,
            s.description,
            h.rent,
            h.property_type,
            h.bedrooms,
            h.bathrooms
        FROM housing_applications ha
        JOIN housing h ON ha.housing_id = h.housing_id
        JOIN services s ON h.service_id = s.service_id
        WHERE ha.applicant_id = ? AND s.type = 'housing'
        ORDER BY ha.created_at DESC
    ");
    $stmt->execute([$userId]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group applications by status
    $statusData = [
        'pending' => [],
        'confirmed' => [],
        'cancelled' => [],
        'rejected' => []
    ];

    foreach ($applications as $app) {
        $applicationData = [
            'id' => $app['application_id'],
            'title' => $app['title'],
            'location' => $app['location'],
            'rent' => number_format($app['rent']),
            'property_type' => $app['property_type'],
            'bedrooms' => $app['bedrooms'],
            'bathrooms' => $app['bathrooms'],
            'appliedDate' => date('Y-m-d', strtotime($app['applied_date'])),
            'status' => $app['status'],
            'message' => $app['message']
        ];

        switch ($app['status']) {
            case 'pending':
                $statusData['pending'][] = $applicationData;
                break;
            case 'accepted':
                $applicationData['confirmedDate'] = date('Y-m-d', strtotime($app['applied_date']));
                $statusData['confirmed'][] = $applicationData;
                break;
            case 'rejected':
                $applicationData['rejectedDate'] = date('Y-m-d', strtotime($app['applied_date']));
                $statusData['rejected'][] = $applicationData;
                break;
            case 'cancelled':
                $applicationData['cancelledDate'] = date('Y-m-d', strtotime($app['applied_date']));
                $statusData['cancelled'][] = $applicationData;
                break;
        }
    }

    echo json_encode([
        "success" => true,
        "data" => $statusData
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
