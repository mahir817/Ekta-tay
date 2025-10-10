<?php
session_start();
include 'db.php';

// Set response content type
header('Content-Type: application/json');

try {
    // Build the query with filters using unified jobs table
    $sql = "SELECT s.*, j.*, u.name, u.email, u.phone
            FROM services s 
            INNER JOIN jobs j ON s.service_id = j.service_id 
            INNER JOIN users u ON s.user_id = u.id 
            WHERE s.type IN ('job', 'tuition') AND j.status = 'active'";
    
    $params = [];

    // Filter by type
    if (isset($_GET['type']) && !empty($_GET['type'])) {
        $type = trim($_GET['type']);
        if (in_array($type, ['job', 'tuition'])) {
            $sql .= " AND s.type = ?";
            $params[] = $type;
        }
    }

    // Filter by location
    if (isset($_GET['location']) && !empty($_GET['location'])) {
        $sql .= " AND s.location LIKE ?";
        $params[] = '%' . trim($_GET['location']) . '%';
    }

    // Filter by keyword (search in title, description)
    if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
        $keyword = '%' . trim($_GET['keyword']) . '%';
        $sql .= " AND (s.title LIKE ? OR s.description LIKE ?)";
        $params[] = $keyword;
        $params[] = $keyword;
    }

    // Filter by price range
    if (isset($_GET['salary_min']) && is_numeric($_GET['salary_min'])) {
        $sql .= " AND s.price >= ?";
        $params[] = floatval($_GET['salary_min']);
    }

    if (isset($_GET['salary_max']) && is_numeric($_GET['salary_max'])) {
        $sql .= " AND s.price <= ?";
        $params[] = floatval($_GET['salary_max']);
    }

    // Job-specific filters
    if (isset($_GET['job_type']) && !empty($_GET['job_type'])) {
        $sql .= " AND j.job_type = ?";
        $params[] = trim($_GET['job_type']);
    }

    if (isset($_GET['company']) && !empty($_GET['company'])) {
        $sql .= " AND j.company LIKE ?";
        $params[] = '%' . trim($_GET['company']) . '%';
    }

    if (isset($_GET['experience']) && !empty($_GET['experience'])) {
        $sql .= " AND j.experience_level = ?";
        $params[] = trim($_GET['experience']);
    }

    if (isset($_GET['work_type']) && !empty($_GET['work_type'])) {
        $sql .= " AND j.work_type = ?";
        $params[] = trim($_GET['work_type']);
    }

    // Tuition-specific filters
    if (isset($_GET['subject']) && !empty($_GET['subject'])) {
        $sql .= " AND j.subject = ?";
        $params[] = trim($_GET['subject']);
    }

    if (isset($_GET['class_level']) && !empty($_GET['class_level'])) {
        $sql .= " AND j.class_level = ?";
        $params[] = trim($_GET['class_level']);
    }

    if (isset($_GET['tuition_type']) && !empty($_GET['tuition_type'])) {
        $sql .= " AND j.tuition_type = ?";
        $params[] = trim($_GET['tuition_type']);
    }

    if (isset($_GET['gender_preference']) && !empty($_GET['gender_preference'])) {
        $sql .= " AND j.gender_preference = ?";
        $params[] = trim($_GET['gender_preference']);
    }

    // Add ordering
    $sql .= " ORDER BY s.created_at DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the results
    $formatted_results = [];
    foreach ($results as $row) {
        $formatted_item = [
            'service_id' => $row['service_id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'type' => $row['type'],
            'price' => $row['price'],
            'location' => $row['location'],
            'created_at' => $row['created_at'],
            'status' => $row['status'],
            'poster_name' => $row['name'],
            'poster_phone' => $row['phone'],
            'user' => [
                'name' => $row['name'],
                'email' => $row['email'],
                'phone' => $row['phone']
            ]
        ];

        // Add type-specific fields based on service type
        if ($row['type'] === 'job') {
            $formatted_item['job_type'] = $row['job_type'];
            $formatted_item['company'] = $row['company'];
            $formatted_item['experience'] = $row['experience_level'];
            $formatted_item['work_type'] = $row['work_type'];
        } elseif ($row['type'] === 'tuition') {
            $formatted_item['subject'] = $row['subject'];
            $formatted_item['class_level'] = $row['class_level'];
            $formatted_item['tuition_type'] = $row['tuition_type'];
            $formatted_item['student_count'] = $row['student_count'];
            $formatted_item['schedule'] = $row['schedule'];
            $formatted_item['gender_preference'] = $row['gender_preference'];
        }

        $formatted_results[] = $formatted_item;
    }

    echo json_encode($formatted_results);

} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error fetching jobs: ' . $e->getMessage()
    ]);
}
?>
