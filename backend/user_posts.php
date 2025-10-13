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
    // Get type filter if provided
    $type = isset($_GET['type']) ? $_GET['type'] : null;
    
    $sql = "
        SELECT s.service_id, s.title, s.description, s.type, s.location, 
               s.price, s.status, s.created_at,
               CASE 
                   WHEN s.type = 'housing' THEN h.rent
                   WHEN s.type = 'job' THEN j.company
                   WHEN s.type = 'food' THEN fs.provider_name
                   ELSE NULL
               END as additional_info,
               CASE 
                   WHEN s.type = 'housing' THEN h.property_type
                   WHEN s.type = 'job' THEN j.job_type
                   WHEN s.type = 'food' THEN fs.food_type
                   ELSE NULL
               END as sub_type,
               (SELECT COUNT(*) FROM job_applications ja WHERE ja.service_id = s.service_id) as application_count
        FROM services s
        LEFT JOIN housing h ON s.service_id = h.service_id AND s.type = 'housing'
        LEFT JOIN jobs j ON s.service_id = j.service_id AND s.type = 'job'
        LEFT JOIN food_services fs ON s.service_id = fs.service_id AND s.type = 'food'
        WHERE s.user_id = ?
    ";
    
    $params = [$user_id];
    
    if ($type && in_array($type, ['tuition', 'job', 'housing', 'food'])) {
        $sql .= " AND s.type = ?";
        $params[] = $type;
    }
    
    $sql .= " ORDER BY s.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group posts by type
    $postsByType = [
        'tuition' => [],
        'job' => [],
        'housing' => [],
        'food' => []
    ];

    foreach ($posts as $post) {
        $postType = $post['type'];
        if (isset($postsByType[$postType])) {
            $postsByType[$postType][] = $post;
        }
    }

    // Calculate statistics
    $stats = [
        'total_posts' => count($posts),
        'active_posts' => count(array_filter($posts, function($p) { return $p['status'] === 'active'; })),
        'total_applications' => array_sum(array_column($posts, 'application_count')),
        'by_type' => [
            'tuition' => count($postsByType['tuition']),
            'job' => count($postsByType['job']),
            'housing' => count($postsByType['housing']),
            'food' => count($postsByType['food'])
        ]
    ];

    echo json_encode([
        "success" => true,
        "data" => [
            "posts" => $type ? $posts : $postsByType,
            "stats" => $stats
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error fetching user posts: " . $e->getMessage()
    ]);
}
?>
