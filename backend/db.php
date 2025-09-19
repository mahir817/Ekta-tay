<?php
// Shared database connection
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = "localhost";
$dbname = "ekta_tay";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "DB Connection failed: " . $e->getMessage()]);
    exit;
}
?>


