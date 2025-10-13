<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/db.php';

// Get JSON
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received"]);
    exit;
}

// Extract fields
$full_name = trim($data['full_name'] ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$location = trim($data['location'] ?? '');
$gender = trim($data['gender'] ?? '');
$password_plain = $data['password'] ?? '';
$role = trim($data['role'] ?? 'student');

// Validation
if(strlen($full_name)<2 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($phone)<11 || strlen($password_plain)<6){
    echo json_encode(["success"=>false,"message"=>"Invalid input data"]);
    exit;
}

// Check if email exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
$stmt->execute([$email]);
if($stmt->rowCount()>0){
    echo json_encode(["success"=>false,"message"=>"Email already exists"]);
    exit;
}

// Hash password
$hashed = password_hash($password_plain, PASSWORD_DEFAULT);

try {
    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (name,email,password,phone,generalized_location,gender) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$full_name,$email,$hashed,$phone,$location,$gender]);
    $user_id = $pdo->lastInsertId();

    // Auto-login new user so capability setup can save with session
    $_SESSION['user_id'] = (int)$user_id;
    $_SESSION['user_name'] = $full_name;

    // Check capability count
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM user_capabilities WHERE user_id=?");
    $count_stmt->execute([$user_id]);
    $capabilityCount = $count_stmt->fetchColumn();

    // Redirect based on capabilities
    if ($capabilityCount == 0) {
        $redirect = "http://localhost:8080/Ekta-Tay/Capability%20Setup/setup.html";
    } else {
        $redirect = "http://localhost:8080/Ekta-Tay/Dashboard/dashboard.php";
    }

    echo json_encode(["success"=>true,"redirect_url"=>$redirect]);

} catch(PDOException $e){
    echo json_encode(["success"=>false,"message"=>"Registration failed: ".$e->getMessage()]);
}
?>
