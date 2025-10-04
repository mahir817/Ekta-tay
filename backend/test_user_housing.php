<?php
require_once "db.php";

header('Content-Type: text/plain');

echo "Testing User Housing Posts for User ID 2:\n\n";

try {
    // Test the exact query used in housing.php
    $stmt = $pdo->prepare("
        SELECT s.service_id, s.title, s.description, s.location, s.price, s.created_at,
               h.rent, h.property_type, h.bedrooms, h.bathrooms, h.furnished_status
        FROM services s
        INNER JOIN housing h ON s.service_id = h.service_id
        WHERE s.user_id = ? AND s.type = 'housing'
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([2]);
    $userHousing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Query executed successfully.\n";
    echo "Number of housing posts found: " . count($userHousing) . "\n\n";
    
    if (count($userHousing) > 0) {
        echo "Housing posts:\n";
        foreach ($userHousing as $post) {
            echo "- Title: " . $post['title'] . "\n";
            echo "  Location: " . $post['location'] . "\n";
            echo "  Rent: " . $post['rent'] . "\n";
            echo "  Service ID: " . $post['service_id'] . "\n";
            echo "  Created: " . $post['created_at'] . "\n\n";
        }
    } else {
        echo "No housing posts found for user ID 2.\n\n";
        
        // Let's check what services exist for user 2
        $servicesStmt = $pdo->prepare("SELECT service_id, title, type FROM services WHERE user_id = 2");
        $servicesStmt->execute();
        $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "All services for user 2:\n";
        foreach ($services as $service) {
            echo "- Service ID: {$service['service_id']}, Title: {$service['title']}, Type: {$service['type']}\n";
        }
        
        // Check all housing records
        $housingStmt = $pdo->query("SELECT service_id FROM housing");
        $housing = $housingStmt->fetchAll(PDO::FETCH_COLUMN);
        echo "\nAll housing service IDs in database: " . implode(', ', $housing) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
