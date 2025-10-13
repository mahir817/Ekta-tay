<?php
session_start();
require_once "db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get status filter if provided
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    $sql = "
        SELECT ja.application_id, ja.service_id, ja.status as app_status, 
               ja.created_at as applied_at, ja.cover_letter, ja.notes,
               s.title, s.type, s.location, s.price, s.description,
               u.name as poster_name, u.email as poster_email,
               CASE 
                   WHEN s.type = 'housing' THEN h.rent
                   WHEN s.type = 'job' THEN j.company
                   WHEN s.type = 'food' THEN fs.provider_name
                   ELSE NULL
               END as additional_info
        FROM job_applications ja
        JOIN services s ON ja.service_id = s.service_id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN housing h ON s.service_id = h.service_id AND s.type = 'housing'
        LEFT JOIN jobs j ON s.service_id = j.service_id AND s.type = 'job'
        LEFT JOIN food_services fs ON s.service_id = fs.service_id AND s.type = 'food'
        WHERE ja.applicant_id = ?
    ";
    
    $params = [$user_id];
    
    if ($status && in_array($status, ['pending', 'reviewed', 'accepted', 'rejected', 'cancelled'])) {
        $sql .= " AND ja.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY ja.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group applications by status
    $applicationsByStatus = [
        'pending' => [],
        'reviewed' => [],
        'accepted' => [],
        'rejected' => [],
        'cancelled' => []
    ];

    foreach ($applications as $app) {
        $appStatus = $app['app_status'];
        if (isset($applicationsByStatus[$appStatus])) {
            $applicationsByStatus[$appStatus][] = $app;
        }
    }

    // Calculate statistics
    $stats = [
        'total_applications' => count($applications),
        'by_status' => [
            'pending' => count($applicationsByStatus['pending']),
            'reviewed' => count($applicationsByStatus['reviewed']),
            'accepted' => count($applicationsByStatus['accepted']),
            'rejected' => count($applicationsByStatus['rejected']),
            'cancelled' => count($applicationsByStatus['cancelled'])
        ],
        'by_type' => []
    ];

    // Count by service type
    $typeCount = [];
    foreach ($applications as $app) {
        $type = $app['type'];
        $typeCount[$type] = ($typeCount[$type] ?? 0) + 1;
    }
    $stats['by_type'] = $typeCount;

    echo json_encode([
        "success" => true,
        "data" => [
            "applications" => $status ? $applications : $applicationsByStatus,
            "stats" => $stats
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error fetching user applications: " . $e->getMessage()
    ]);
}
?>
