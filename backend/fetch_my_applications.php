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
    // Get user's applications with service details using unified schema
    $sql = "SELECT ja.*, s.title, s.description, s.location, s.price, s.type, 
            j.company, j.job_type, j.subject, j.class_level, j.tuition_type,
            u.name as poster_name, u.email as poster_email
            FROM job_applications ja
            INNER JOIN services s ON ja.service_id = s.service_id
            INNER JOIN jobs j ON s.service_id = j.service_id
            INNER JOIN users u ON s.user_id = u.id
            WHERE ja.applicant_id = ?
            ORDER BY ja.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format applications for frontend
    $formatted_applications = [];
    foreach ($applications as $app) {
        $formatted_app = [
            'application_id' => $app['application_id'],
            'service_id' => $app['service_id'],
            'title' => $app['title'],
            'description' => $app['description'],
            'location' => $app['location'],
            'price' => $app['price'],
            'type' => $app['type'],
            'cover_letter' => $app['cover_letter'],
            'phone' => $app['phone'],
            'email' => $app['email'],
            'status' => $app['status'],
            'applied_at' => $app['created_at'],
            'notes' => $app['notes'],
            'poster_name' => $app['poster_name'],
            'poster_email' => $app['poster_email']
        ];

        // Add type-specific fields
        if ($app['type'] === 'job') {
            $formatted_app['company'] = $app['company'];
            $formatted_app['job_type'] = $app['job_type'];
        } elseif ($app['type'] === 'tuition') {
            $formatted_app['subject'] = $app['subject'];
            $formatted_app['class_level'] = $app['class_level'];
            $formatted_app['tuition_type'] = $app['tuition_type'];
        }

        $formatted_applications[] = $formatted_app;
    }
    
    echo json_encode($formatted_applications);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error fetching applications: ' . $e->getMessage()
    ]);
}
?>
