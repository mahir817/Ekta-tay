<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Please login to access owner dashboard"]);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_my_housing_posts':
            getMyHousingPosts();
            break;
        case 'get_applications_for_housing':
            getApplicationsForHousing();
            break;
        case 'get_owner_stats':
            getOwnerStats();
            break;
        default:
            echo json_encode(["success" => false, "message" => "Invalid action"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}

function getMyHousingPosts() {
    global $pdo, $userId;
    
    $stmt = $pdo->prepare("
        SELECT s.service_id, s.title, s.description, s.location, s.price, s.created_at,
               h.rent, h.property_type, h.bedrooms, h.bathrooms, h.furnished_status, h.availability,
               COUNT(ha.application_id) as total_applications,
               SUM(CASE WHEN ha.status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
               SUM(CASE WHEN ha.status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted_applications,
               SUM(CASE WHEN ha.status = 'accepted' THEN 1 ELSE 0 END) as accepted_applications
        FROM services s
        INNER JOIN housing h ON s.service_id = h.service_id
        LEFT JOIN housing_applications ha ON h.housing_id = ha.housing_id
        WHERE s.user_id = ? AND s.type = 'housing'
        GROUP BY s.service_id
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$userId]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["success" => true, "posts" => $posts]);
}

function getApplicationsForHousing() {
    global $pdo, $userId;
    
    $serviceId = $_GET['housing_id'] ?? null; // This is actually service_id from frontend
    if (!$serviceId) {
        echo json_encode(["success" => false, "message" => "Housing ID is required"]);
        return;
    }
    
    // Get housing_id from service_id and verify ownership
    $stmt = $pdo->prepare("
        SELECT h.housing_id 
        FROM services s 
        INNER JOIN housing h ON s.service_id = h.service_id 
        WHERE s.service_id = ? AND s.user_id = ? AND s.type = 'housing'
    ");
    $stmt->execute([$serviceId, $userId]);
    $housing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$housing) {
        echo json_encode(["success" => false, "message" => "Housing not found or access denied"]);
        return;
    }
    
    $housingId = $housing['housing_id'];
    
    // Get applications grouped by status
    $stmt = $pdo->prepare("
        SELECT ha.*, u.name as applicant_name, u.phone as applicant_phone, 
               u.email as applicant_email, u.location as applicant_location
        FROM housing_applications ha
        INNER JOIN users u ON ha.applicant_id = u.id
        WHERE ha.housing_id = ? AND ha.owner_id = ?
        ORDER BY 
            CASE ha.status 
                WHEN 'pending' THEN 1 
                WHEN 'shortlisted' THEN 2 
                WHEN 'accepted' THEN 3 
                WHEN 'rejected' THEN 4 
                WHEN 'withdrawn' THEN 5 
            END,
            ha.created_at DESC
    ");
    $stmt->execute([$housingId, $userId]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by status
    $grouped = [
        'pending' => [],
        'shortlisted' => [],
        'accepted' => [],
        'rejected' => [],
        'withdrawn' => []
    ];
    
    foreach ($applications as $app) {
        $grouped[$app['status']][] = $app;
    }
    
    echo json_encode(["success" => true, "applications" => $grouped]);
}

function getOwnerStats() {
    global $pdo, $userId;
    
    $stats = [];
    
    // Total housing posts
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_posts,
               SUM(CASE WHEN h.availability = 'available' THEN 1 ELSE 0 END) as available_posts,
               SUM(CASE WHEN h.availability = 'occupied' THEN 1 ELSE 0 END) as occupied_posts
        FROM services s
        INNER JOIN housing h ON s.service_id = h.service_id
        WHERE s.user_id = ? AND s.type = 'housing'
    ");
    $stmt->execute([$userId]);
    $housingStats = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['housing'] = $housingStats;
    
    // Application statistics
    $stmt = $pdo->prepare("
        SELECT ha.status, COUNT(*) as count
        FROM housing_applications ha
        WHERE ha.owner_id = ?
        GROUP BY ha.status
    ");
    $stmt->execute([$userId]);
    $applicationStats = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $applicationStats[$row['status']] = (int)$row['count'];
    }
    $stats['applications'] = $applicationStats;
    
    // Active tenants (check if owner_id column exists, otherwise use housing ownership)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as active_tenants
        FROM housing_tenants ht
        INNER JOIN housing h ON ht.housing_id = h.housing_id
        INNER JOIN services s ON h.service_id = s.service_id
        WHERE s.user_id = ? AND ht.active = 1
    ");
    $stmt->execute([$userId]);
    $stats['active_tenants'] = (int)$stmt->fetchColumn();
    
    // Recent applications (last 7 days)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as recent_applications
        FROM housing_applications ha
        WHERE ha.owner_id = ? AND ha.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$userId]);
    $stats['recent_applications'] = (int)$stmt->fetchColumn();
    
    echo json_encode(["success" => true, "stats" => $stats]);
}
?>
