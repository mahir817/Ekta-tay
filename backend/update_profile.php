<?php
session_start();
require_once "db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $updateFields = [];
    $updateValues = [];

    // Handle regular form data
    if (isset($_POST['name']) && !empty(trim($_POST['name']))) {
        $updateFields[] = "name = ?";
        $updateValues[] = trim($_POST['name']);
    }

    if (isset($_POST['phone'])) {
        $updateFields[] = "phone = ?";
        $updateValues[] = trim($_POST['phone']);
    }

    if (isset($_POST['location'])) {
        $updateFields[] = "location = ?";
        $updateValues[] = trim($_POST['location']);
    }

    if (isset($_POST['generalized_location']) && in_array($_POST['generalized_location'], ['Dhaka North', 'Dhaka South', 'Dhaka East', 'Dhaka West'])) {
        $updateFields[] = "generalized_location = ?";
        $updateValues[] = $_POST['generalized_location'];
    }

    if (isset($_POST['gender']) && in_array($_POST['gender'], ['male', 'female'])) {
        $updateFields[] = "gender = ?";
        $updateValues[] = $_POST['gender'];
    }

    // Profile image and tagline features removed - not supported in current database

    if (empty($updateFields)) {
        echo json_encode(["success" => false, "message" => "No valid fields to update"]);
        exit();
    }

    // Add user ID to values array
    $updateValues[] = $user_id;

    // Build and execute update query
    $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($updateValues);

    if ($stmt->rowCount() > 0) {
        // Fetch updated user data
        $userStmt = $pdo->prepare("
            SELECT id, name, email, phone, location, generalized_location, 
                   gender, created_at
            FROM users 
            WHERE id = ?
        ");
        $userStmt->execute([$user_id]);
        $updatedUser = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        // Add default values for missing columns
        $updatedUser['profile_img'] = null;
        $updatedUser['tagline'] = null;

        echo json_encode([
            "success" => true, 
            "message" => "Profile updated successfully",
            "data" => $updatedUser
        ]);
    } else {
        echo json_encode([
            "success" => true, 
            "message" => "No changes made",
            "data" => null
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Error updating profile: " . $e->getMessage()
    ]);
}
?>
