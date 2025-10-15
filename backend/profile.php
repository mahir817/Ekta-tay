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
    // Fetch user data (only existing columns)
    $userStmt = $pdo->prepare("
        SELECT id, name, email, phone, location, generalized_location, 
               gender, created_at
        FROM users 
        WHERE id = ?
    ");
    $userStmt->execute([$user_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    // Add default values for missing columns
    $user['profile_img'] = null;
    $user['tagline'] = null;

    if (!$user) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit();
    }

    // Fetch user capabilities
    $capStmt = $pdo->prepare("
        SELECT c.capability_name 
        FROM capabilities c 
        JOIN user_capabilities uc ON c.id = uc.capability_id 
        WHERE uc.user_id = ?
    ");
    $capStmt->execute([$user_id]);
    $capabilities = $capStmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch user posts from services table (simplified)
    $postsStmt = $pdo->prepare("
        SELECT s.service_id, s.title, s.description, s.type, s.location, 
               s.price, s.created_at
        FROM services s
        WHERE s.user_id = ?
        ORDER BY s.created_at DESC
    ");
    $postsStmt->execute([$user_id]);
    $posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);

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
        $post['application_count'] = 0; // Default value
        
        $type = $post['type'];
        if (isset($postsByType[$type])) {
            $postsByType[$type][] = $post;
        }
    }

    // Fetch user applications from both job_applications and housing_applications
    $applications = [];
    
    // Fetch job applications
    $jobAppsStmt = $pdo->prepare("
        SELECT ja.application_id, ja.service_id, ja.status as app_status, 
               ja.created_at as applied_at, ja.cover_letter,
               s.title, s.type, s.location, s.price,
               u.name as poster_name
        FROM job_applications ja
        JOIN services s ON ja.service_id = s.service_id
        JOIN users u ON s.user_id = u.id
        WHERE ja.applicant_id = ?
        ORDER BY ja.created_at DESC
    ");
    $jobAppsStmt->execute([$user_id]);
    $jobApplications = $jobAppsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch housing applications
    $housingAppsStmt = $pdo->prepare("
        SELECT ha.application_id, h.housing_id as service_id, ha.status as app_status, 
               ha.created_at as applied_at, ha.message as cover_letter,
               s.title, s.type, s.location, h.rent as price,
               u.name as poster_name
        FROM housing_applications ha
        JOIN housing h ON ha.housing_id = h.housing_id
        JOIN services s ON h.service_id = s.service_id
        JOIN users u ON ha.owner_id = u.id
        WHERE ha.applicant_id = ?
        ORDER BY ha.created_at DESC
    ");
    $housingAppsStmt->execute([$user_id]);
    $housingApplications = $housingAppsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine all applications
    $applications = array_merge($jobApplications, $housingApplications);
    
    // Sort by applied_at date (most recent first)
    usort($applications, function($a, $b) {
        return strtotime($b['applied_at']) - strtotime($a['applied_at']);
    });

    // Fetch expenses
    $expensesStmt = $pdo->prepare("
        SELECT id, name, amount, due_date, status, created_at
        FROM expenses 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $expensesStmt->execute([$user_id]);
    $expenses = $expensesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate expense summary
    $expenseSummaryStmt = $pdo->prepare("
        SELECT 
            SUM(amount) as total_amount,
            COUNT(*) as total_count,
            SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
            SUM(CASE WHEN status = 'unpaid' THEN amount ELSE 0 END) as unpaid_amount
        FROM expenses 
        WHERE user_id = ?
    ");
    $expenseSummaryStmt->execute([$user_id]);
    $expenseSummary = $expenseSummaryStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch mentorship data if applicable
    $mentorships = [];
    if (in_array('find_mentor', $capabilities) || in_array('offer_mentor', $capabilities)) {
        $mentorshipStmt = $pdo->prepare("
            SELECT mr.request_id, mr.status, mr.created_at,
                   u.name as mentor_name, m.expertise
            FROM mentorship_requests mr
            JOIN mentors m ON mr.mentor_id = m.mentor_id
            JOIN users u ON m.user_id = u.id
            WHERE mr.mentee_id = ?
            ORDER BY mr.created_at DESC
            LIMIT 5
        ");
        $mentorshipStmt->execute([$user_id]);
        $mentorships = $mentorshipStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Prepare response
    $response = [
        "success" => true,
        "data" => [
            "user" => $user,
            "capabilities" => $capabilities,
            "posts" => $postsByType,
            "applications" => $applications,
            "expenses" => $expenses,
            "expense_summary" => $expenseSummary,
            "mentorships" => $mentorships,
            "stats" => [
                "total_posts" => count($posts),
                "total_applications" => count($applications),
                "total_expenses" => $expenseSummary['total_count'] ?? 0,
                "pending_applications" => count(array_filter($applications, function($app) {
                    return $app['app_status'] === 'pending' || $app['app_status'] === 'shortlisted';
                }))
            ]
        ]
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Error fetching profile data: " . $e->getMessage()
    ]);
}
?>
