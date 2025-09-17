<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database config
$host = "localhost";
$dbname = "ekta_tay";
$user = "root";
$pass = ""; // your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "DB Connection failed: ".$e->getMessage()]);
    exit;
}

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
    $stmt = $pdo->prepare("INSERT INTO users (name,email,password,phone,location,gender) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$full_name,$email,$hashed,$phone,$location,$gender]);
    $user_id = $pdo->lastInsertId();

    // Assign capabilities
    $role_caps = $role === 'student'
        ? ['find_job','find_tutor','find_room']
        : ['post_job','offer_tuition','offer_room'];

    $cap_stmt = $pdo->prepare("SELECT id FROM capabilities WHERE capability_name=?");
    $user_cap_stmt = $pdo->prepare("INSERT INTO user_capabilities (user_id, capability_id) VALUES (?,?)");

    foreach($role_caps as $cap){
        $cap_stmt->execute([$cap]);
        $cap_id = $cap_stmt->fetchColumn();
        if($cap_id) $user_cap_stmt->execute([$user_id,$cap_id]);
    }

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
