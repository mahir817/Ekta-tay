<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

$response = [];

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in", "debug" => $_SESSION]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Clear old capabilities
$pdo->prepare("DELETE FROM user_capabilities WHERE user_id = ?")->execute([$user_id]);

if (!empty($_POST['capabilities'])) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_capabilities (user_id, capability_id) 
            SELECT :user_id, id FROM capabilities WHERE capability_name = :capability
        ");

        $inserted = 0;
        foreach ($_POST['capabilities'] as $capability) {
            $stmt->execute([
                ":user_id" => $user_id,
                ":capability" => $capability
            ]);
            $inserted += $stmt->rowCount();
        }

        // Refresh capabilities in session
        $capsStmt = $pdo->prepare("SELECT c.capability_name FROM capabilities c JOIN user_capabilities uc ON c.id = uc.capability_id WHERE uc.user_id = ?");
        $capsStmt->execute([$user_id]);
        $_SESSION['capabilities'] = $capsStmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            "success" => true,
            "message" => "Capabilities saved!",
            "redirect_url" => "../Dashboard/dashboard.php",
            "debug" => [
                "user_id" => $user_id,
                "selected" => $_POST['capabilities'],
                "inserted_rows" => $inserted,
                "session_caps" => $_SESSION['capabilities']
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            "success" => false,
            "message" => "DB Error: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Please select at least one option",
        "debug" => $_POST
    ]);
}
