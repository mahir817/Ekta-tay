<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

try {
    // Get total housing count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM services WHERE type = 'housing'");
    $totalHousing = $stmt->fetch()['total'];

    // Get pending applications count (for current user if logged in)
    $pendingCount = 0;
    $appliedCount = 0;
    $confirmedCount = 0;
    $cancelledCount = 0;
    
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        
        // Get user's applications
        $stmt = $pdo->prepare("
            SELECT ha.status, COUNT(*) as count 
            FROM housing_applications ha
            JOIN housing h ON ha.housing_id = h.housing_id
            JOIN services s ON h.service_id = s.service_id
            WHERE ha.applicant_id = ? AND s.type = 'housing'
            GROUP BY ha.status
        ");
        $stmt->execute([$userId]);
        $userApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($userApplications as $app) {
            switch ($app['status']) {
                case 'pending':
                    $pendingCount = $app['count'];
                    break;
                case 'accepted':
                    $confirmedCount = $app['count'];
                    break;
                case 'rejected':
                    $cancelledCount = $app['count'];
                    break;
            }
        }
        
        $appliedCount = $pendingCount + $confirmedCount + $cancelledCount;
    }

    // Get nearby housing count (within same city/area)
    $nearbyCount = $totalHousing; // For now, show all housing as nearby

    $stats = [
        'pending' => $pendingCount,
        'applied' => $appliedCount,
        'confirmed' => $confirmedCount,
        'cancelled' => $cancelledCount,
        'nearby' => $nearbyCount,
        'total' => $totalHousing
    ];

    echo json_encode($stats);
} catch (Exception $e) {
    echo json_encode([
        'pending' => 0,
        'applied' => 0,
        'confirmed' => 0,
        'cancelled' => 0,
        'nearby' => 0,
        'total' => 0,
        'error' => $e->getMessage()
    ]);
}
?>
