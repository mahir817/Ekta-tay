<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Clear old capabilities if resubmitting
$pdo->prepare("DELETE FROM user_capabilities WHERE user_id = ?")->execute([$user_id]);

if (!empty($_POST['capabilities'])) {
    $stmt = $pdo->prepare("INSERT INTO user_capabilities (user_id, capability_name) VALUES (?, ?)");
    foreach ($_POST['capabilities'] as $capability) {
        $stmt->execute([$user_id, $capability]);
    }
    echo json_encode(["success" => true, "message" => "Capabilities saved!"]);
} else {
    echo json_encode(["success" => false, "message" => "Please select at least one option"]);
}
