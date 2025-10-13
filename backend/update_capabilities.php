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
    // Get the capabilities data from POST
    $capabilities = isset($_POST['capabilities']) ? $_POST['capabilities'] : [];
    
    // Get all available capabilities from database
    $allCapabilitiesStmt = $pdo->prepare("SELECT id, capability_name FROM capabilities");
    $allCapabilitiesStmt->execute();
    $allCapabilities = $allCapabilitiesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create mapping of capability names to IDs
    $capabilityMap = [];
    foreach ($allCapabilities as $cap) {
        $capabilityMap[$cap['capability_name']] = $cap['id'];
    }

    // Start transaction
    $pdo->beginTransaction();

    // Delete all existing capabilities for this user
    $deleteStmt = $pdo->prepare("DELETE FROM user_capabilities WHERE user_id = ?");
    $deleteStmt->execute([$user_id]);

    // Insert new capabilities
    if (!empty($capabilities)) {
        $insertStmt = $pdo->prepare("INSERT INTO user_capabilities (user_id, capability_id) VALUES (?, ?)");
        
        foreach ($capabilities as $capabilityName) {
            if (isset($capabilityMap[$capabilityName])) {
                $insertStmt->execute([$user_id, $capabilityMap[$capabilityName]]);
            }
        }
    }

    // Commit transaction
    $pdo->commit();

    // Fetch updated capabilities
    $updatedCapStmt = $pdo->prepare("
        SELECT c.capability_name 
        FROM capabilities c 
        JOIN user_capabilities uc ON c.id = uc.capability_id 
        WHERE uc.user_id = ?
    ");
    $updatedCapStmt->execute([$user_id]);
    $updatedCapabilities = $updatedCapStmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        "success" => true,
        "message" => "Capabilities updated successfully",
        "data" => $updatedCapabilities
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error updating capabilities: " . $e->getMessage()
    ]);
}
?>
