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
    
    // Simplified query to work with existing database structure
    $sql = "
        SELECT s.service_id, s.title, s.description, s.type, s.location, 
               s.price, s.created_at,
               (SELECT COUNT(*) FROM job_applications ja WHERE ja.service_id = s.service_id) as application_count
        FROM services s
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
        // Add default status since column doesn't exist
        $post['status'] = 'active';
        
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
