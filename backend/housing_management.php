<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Please login to access housing management"]);
    exit;
}

// Get the action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'apply':
            handleApplyToHousing();
            break;
        case 'shortlist':
        case 'shortlist_applicant':
            handleShortlistApplicant();
            break;
        case 'confirm_tenant':
            handleConfirmTenant();
            break;
        case 'reject_applicant':
            handleRejectApplicant();
            break;
        case 'withdraw_application':
            handleWithdrawApplication();
            break;
        case 'get_my_applications':
            getMyApplications();
            break;
        case 'get_my_housing_applications':
            getMyHousingApplications();
            break;
        case 'get_dashboard_stats':
            getDashboardStats();
            break;
        default:
            echo json_encode(["success" => false, "message" => "Invalid action"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}

function handleApplyToHousing() {
    global $pdo, $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $serviceId = $input['housing_id'] ?? null; // This is actually service_id from frontend
    $message = $input['message'] ?? 'Application submitted';
    
    if (!$serviceId) {
        echo json_encode(["success" => false, "message" => "Housing ID is required"]);
        return;
    }
    
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
        return;
    }
    
    if ($housing['availability'] !== 'available') {
        echo json_encode(["success" => false, "message" => "Housing is no longer available"]);
        return;
    }
    
    if ($housing['owner_id'] == $userId) {
        echo json_encode(["success" => false, "message" => "You cannot apply to your own housing"]);
        return;
    }
    
    // Check if already applied (using housing_id)
    $stmt = $pdo->prepare("SELECT application_id FROM housing_applications WHERE housing_id = ? AND applicant_id = ?");
    $stmt->execute([$housing['housing_id'], $userId]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "You have already applied for this housing"]);
        return;
    }
    
    // Insert application (using housing_id)
    $stmt = $pdo->prepare("
        INSERT INTO housing_applications (housing_id, owner_id, applicant_id, status, message) 
        VALUES (?, ?, ?, 'pending', ?)
    ");
    $stmt->execute([$housing['housing_id'], $housing['owner_id'], $userId, $message]);
    
    echo json_encode(["success" => true, "message" => "Application submitted successfully"]);
}

function handleShortlistApplicant() {
    global $pdo, $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $applicationId = $input['application_id'] ?? null;
    
    if (!$applicationId) {
        echo json_encode(["success" => false, "message" => "Application ID is required"]);
        return;
    }
    
    // Verify ownership and current status
    $stmt = $pdo->prepare("
        SELECT ha.*, h.availability 
        FROM housing_applications ha
        INNER JOIN housing h ON ha.housing_id = h.housing_id
        WHERE ha.application_id = ? AND ha.owner_id = ? AND ha.status = 'pending'
    ");
    $stmt->execute([$applicationId, $userId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        echo json_encode(["success" => false, "message" => "Application not found or not eligible for shortlisting"]);
        return;
    }
    
    if ($application['availability'] !== 'available') {
        echo json_encode(["success" => false, "message" => "Housing is no longer available"]);
        return;
    }
    
    // Update application status to shortlisted
    $stmt = $pdo->prepare("UPDATE housing_applications SET status = 'shortlisted' WHERE application_id = ?");
    $stmt->execute([$applicationId]);
    
    echo json_encode(["success" => true, "message" => "Applicant shortlisted successfully"]);
}

function handleConfirmTenant() {
    global $pdo, $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $applicationId = $input['application_id'] ?? null;
    $startDate = $input['start_date'] ?? date('Y-m-d');
    
    if (!$applicationId) {
        echo json_encode(["success" => false, "message" => "Application ID is required"]);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Verify ownership and shortlisted status
        $stmt = $pdo->prepare("
            SELECT ha.*, h.availability 
            FROM housing_applications ha
            INNER JOIN housing h ON ha.housing_id = h.housing_id
            WHERE ha.application_id = ? AND ha.owner_id = ? AND ha.status = 'shortlisted'
        ");
        $stmt->execute([$applicationId, $userId]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application) {
            throw new Exception("Application not found or not eligible for confirmation");
        }
        
        if ($application['availability'] !== 'available') {
            throw new Exception("Housing is no longer available");
        }
        
        // Update application status to accepted
        $stmt = $pdo->prepare("UPDATE housing_applications SET status = 'accepted' WHERE application_id = ?");
        $stmt->execute([$applicationId]);
        
        // Insert into housing_tenants (check if owner_id column exists)
        $stmt = $pdo->query("SHOW COLUMNS FROM housing_tenants LIKE 'owner_id'");
        if ($stmt->rowCount() > 0) {
            // owner_id column exists
            $stmt = $pdo->prepare("
                INSERT INTO housing_tenants (housing_id, user_id, owner_id, start_date, status) 
                VALUES (?, ?, ?, ?, 'active')
            ");
            $stmt->execute([$application['housing_id'], $application['applicant_id'], $userId, $startDate]);
        } else {
            // owner_id column doesn't exist, use basic structure
            $stmt = $pdo->prepare("
                INSERT INTO housing_tenants (housing_id, user_id, start_date, active) 
                VALUES (?, ?, ?, 1)
            ");
            $stmt->execute([$application['housing_id'], $application['applicant_id'], $startDate]);
        }
        
        // Update housing availability to occupied
        $stmt = $pdo->prepare("UPDATE housing SET availability = 'occupied' WHERE housing_id = ?");
        $stmt->execute([$application['housing_id']]);
        
        // Reject all other applications for this housing
        $stmt = $pdo->prepare("
            UPDATE housing_applications 
            SET status = 'rejected' 
            WHERE housing_id = ? AND application_id != ? AND status IN ('pending', 'shortlisted')
        ");
        $stmt->execute([$application['housing_id'], $applicationId]);
        
        $pdo->commit();
        echo json_encode(["success" => true, "message" => "Tenant confirmed successfully"]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleRejectApplicant() {
    global $pdo, $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $applicationId = $input['application_id'] ?? null;
    
    if (!$applicationId) {
        echo json_encode(["success" => false, "message" => "Application ID is required"]);
        return;
    }
    
    // Verify ownership
    $stmt = $pdo->prepare("
        SELECT application_id FROM housing_applications 
        WHERE application_id = ? AND owner_id = ? AND status IN ('pending', 'shortlisted')
    ");
    $stmt->execute([$applicationId, $userId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Application not found or not eligible for rejection"]);
        return;
    }
    
    // Update application status to rejected
    $stmt = $pdo->prepare("UPDATE housing_applications SET status = 'rejected' WHERE application_id = ?");
    $stmt->execute([$applicationId]);
    
    echo json_encode(["success" => true, "message" => "Applicant rejected successfully"]);
}

function handleWithdrawApplication() {
    global $pdo, $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $applicationId = $input['application_id'] ?? null;
    
    if (!$applicationId) {
        echo json_encode(["success" => false, "message" => "Application ID is required"]);
        return;
    }
    
    // Verify ownership by applicant
    $stmt = $pdo->prepare("
        SELECT application_id FROM housing_applications 
        WHERE application_id = ? AND applicant_id = ? AND status IN ('pending', 'shortlisted')
    ");
    $stmt->execute([$applicationId, $userId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Application not found or cannot be withdrawn"]);
        return;
    }
    
    // Update application status to withdrawn
    $stmt = $pdo->prepare("UPDATE housing_applications SET status = 'withdrawn' WHERE application_id = ?");
    $stmt->execute([$applicationId]);
    
    echo json_encode(["success" => true, "message" => "Application withdrawn successfully"]);
}

function getMyApplications() {
    global $pdo, $userId;
    
    $stmt = $pdo->prepare("
        SELECT ha.*, s.title, s.location, s.price, h.rent, h.property_type, h.bedrooms, h.bathrooms,
               u.name as owner_name, u.phone as owner_phone
        FROM housing_applications ha
        INNER JOIN housing h ON ha.housing_id = h.housing_id
        INNER JOIN services s ON h.service_id = s.service_id
        INNER JOIN users u ON ha.owner_id = u.id
        WHERE ha.applicant_id = ?
        ORDER BY ha.created_at DESC
    ");
    $stmt->execute([$userId]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["success" => true, "applications" => $applications]);
}

function getMyHousingApplications() {
    global $pdo, $userId;
    
    $stmt = $pdo->prepare("
        SELECT ha.*, s.title, s.location, h.rent, h.property_type,
               u.name as applicant_name, u.phone as applicant_phone, u.email as applicant_email
        FROM housing_applications ha
        INNER JOIN housing h ON ha.housing_id = h.housing_id
        INNER JOIN services s ON h.service_id = s.service_id
        INNER JOIN users u ON ha.applicant_id = u.id
        WHERE ha.owner_id = ?
        ORDER BY ha.housing_id, ha.status, ha.created_at DESC
    ");
    $stmt->execute([$userId]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by housing and status
    $grouped = [];
    foreach ($applications as $app) {
        $housingId = $app['housing_id'];
        $status = $app['status'];
        
        if (!isset($grouped[$housingId])) {
            $grouped[$housingId] = [
                'housing_info' => [
                    'title' => $app['title'],
                    'location' => $app['location'],
                    'rent' => $app['rent'],
                    'property_type' => $app['property_type']
                ],
                'applications' => []
            ];
        }
        
        if (!isset($grouped[$housingId]['applications'][$status])) {
            $grouped[$housingId]['applications'][$status] = [];
        }
        
        $grouped[$housingId]['applications'][$status][] = $app;
    }
    
    echo json_encode(["success" => true, "housing_applications" => $grouped]);
}

function getDashboardStats() {
    global $pdo, $userId;
    
    // Stats for applicants
    $applicantStats = [];
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count 
        FROM housing_applications 
        WHERE applicant_id = ? 
        GROUP BY status
    ");
    $stmt->execute([$userId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $applicantStats[$row['status']] = (int)$row['count'];
    }
    
    // Stats for owners
    $ownerStats = [];
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.service_id) as my_housing_posts
        FROM services s
        INNER JOIN housing h ON s.service_id = h.service_id
        WHERE s.user_id = ? AND s.type = 'housing'
    ");
    $stmt->execute([$userId]);
    $ownerStats['my_housing_posts'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count 
        FROM housing_applications 
        WHERE owner_id = ? 
        GROUP BY status
    ");
    $stmt->execute([$userId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $ownerStats['applications_' . $row['status']] = (int)$row['count'];
    }
    
    // Active tenants (use housing ownership if owner_id column doesn't exist)
    $stmt = $pdo->query("SHOW COLUMNS FROM housing_tenants LIKE 'owner_id'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM housing_tenants 
            WHERE owner_id = ? AND status = 'active'
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM housing_tenants ht
            INNER JOIN housing h ON ht.housing_id = h.housing_id
            INNER JOIN services s ON h.service_id = s.service_id
            WHERE s.user_id = ? AND ht.active = 1
        ");
        $stmt->execute([$userId]);
    }
    $ownerStats['active_tenants'] = (int)$stmt->fetchColumn();
    
    echo json_encode([
        "success" => true, 
        "applicant_stats" => $applicantStats,
        "owner_stats" => $ownerStats
    ]);
}
?>
