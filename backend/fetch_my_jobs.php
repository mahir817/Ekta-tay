<?php
session_start();
include 'db.php';

// Set response content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get user's job and tuition posts using unified schema
    $sql = "SELECT s.*, 
                   COALESCE(j.job_type, NULL) AS job_type,
                   COALESCE(j.company, NULL) AS company,
                   COALESCE(j.experience_level, NULL) AS experience_level,
                   COALESCE(j.work_type, NULL) AS work_type,
                   t.subject AS subject,
                   t.class_level AS class_level,
                   t.tuition_type AS tuition_type,
                   t.student_count AS student_count,
                   t.schedule AS schedule,
                   t.gender_preference AS gender_preference,
                   (SELECT COUNT(*) FROM job_applications ja WHERE ja.service_id = s.service_id) as application_count,
                   (SELECT COUNT(*) FROM job_applications ja WHERE ja.service_id = s.service_id AND ja.status = 'accepted') as hired_count
            FROM services s 
            LEFT JOIN jobs j ON s.type = 'job' AND s.service_id = j.service_id
            LEFT JOIN tuitions t ON s.type = 'tuition' AND s.service_id = t.service_id
            WHERE s.user_id = ? AND s.type IN ('job', 'tuition')
            ORDER BY s.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $my_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format posts for frontend
    $formatted_posts = [];
    foreach ($my_posts as $post) {
        $formatted_post = [
            'service_id' => $post['service_id'],
            'title' => $post['title'],
            'description' => $post['description'],
            'location' => $post['location'],
            'price' => $post['price'],
            'type' => $post['type'],
            'status' => $post['status'],
            'created_at' => $post['created_at'],
            'application_count' => $post['application_count'],
            'hired_count' => $post['hired_count']
        ];

        // Add type-specific fields based on service type
        if ($post['type'] === 'job') {
            $formatted_post['job_type'] = $post['job_type'];
            $formatted_post['company'] = $post['company'];
            $formatted_post['experience'] = $post['experience_level'];
            $formatted_post['work_type'] = $post['work_type'];
        } elseif ($post['type'] === 'tuition') {
            $formatted_post['subject'] = $post['subject'];
            $formatted_post['class_level'] = $post['class_level'];
            $formatted_post['tuition_type'] = $post['tuition_type'];
            $formatted_post['student_count'] = $post['student_count'];
            $formatted_post['schedule'] = $post['schedule'];
            $formatted_post['gender_preference'] = $post['gender_preference'];
        }

        $formatted_posts[] = $formatted_post;
    }
    
    echo json_encode($formatted_posts);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error fetching your posts: ' . $e->getMessage()
    ]);
}
?>
