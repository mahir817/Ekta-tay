<?php
header('Content-Type: application/json');
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database config
$host = "localhost";
$dbname = "ekta_tay";
$user = "root";
$pass = ""; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(["success"=>false,"message"=>"DB Connection failed: ".$e->getMessage()]);
    exit;
}

// Get JSON
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(["success"=>false,"message"=>"No data received"]);
    exit;
}

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(["success"=>false,"message"=>"Email and password are required"]);
    exit;
}

try {
    // Fetch user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["success"=>false,"message"=>"User not found"]);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        echo json_encode(["success"=>false,"message"=>"Incorrect password"]);
        exit;
    }

    // Fetch capabilities
    $stmt = $pdo->prepare("SELECT c.capability_name FROM capabilities c 
                           JOIN user_capabilities uc ON c.id = uc.capability_id 
                           WHERE uc.user_id=?");
    $stmt->execute([$user['id']]);
    $capabilities = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Store in session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['capabilities'] = $capabilities;

    echo json_encode([
        "success" => true,
        "redirect_url" => "../Dashboard/dashboard.php"
    ]);

} catch(PDOException $e) {
    echo json_encode(["success"=>false,"message"=>"Login failed: ".$e->getMessage()]);
}
?>
