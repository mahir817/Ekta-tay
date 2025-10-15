<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

// Get housing ID from request
$housingId = $_GET['housing_id'] ?? null;

if (!$housingId) {
    echo json_encode(["success" => false, "message" => "Housing ID is required"]);
    exit;
}

try {
    // Check if housing_images table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'housing_images'");
    if ($stmt->rowCount() == 0) {
        // Table doesn't exist, return empty array
        echo json_encode(["success" => true, "images" => []]);
        exit;
    }
    
    // Get images for the housing
    $stmt = $pdo->prepare("
        SELECT image_id, image_path, image_name, upload_date, is_primary 
        FROM housing_images 
        WHERE housing_id = ? 
        ORDER BY is_primary DESC, upload_date ASC
    ");
    $stmt->execute([$housingId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process image paths to ensure they're accessible
    foreach ($images as &$image) {
        // Ensure the image path is relative to the web root
        if (!str_starts_with($image['image_path'], 'http') && !str_starts_with($image['image_path'], '/')) {
            $image['image_path'] = '/' . ltrim($image['image_path'], '/');
        }
    }
    
    echo json_encode(["success" => true, "images" => $images]);
    
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error fetching images: " . $e->getMessage()]);
}
?>
