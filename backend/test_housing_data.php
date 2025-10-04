<?php
require_once "db.php";

header('Content-Type: application/json');

try {
    // Test database connection
    echo "Testing database connection...\n";
    
    // Check if services table has housing data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM services WHERE type = 'housing'");
    $servicesCount = $stmt->fetch()['count'];
    echo "Services with type 'housing': $servicesCount\n";
    
    // Check if housing table has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM housing");
    $housingCount = $stmt->fetch()['count'];
    echo "Housing records: $housingCount\n";
    
    // Test the actual query used in fetch_housing.php
    $stmt = $pdo->prepare("
        SELECT s.service_id, s.title, s.description, s.location, s.price, s.created_at,
               h.property_type, h.size_sqft, h.floor_no, h.total_floors, h.furnished_status,
               h.bedrooms, h.bathrooms, h.balconies, h.rent, h.service_charge, h.advance_deposit,
               h.available_from, h.available_for, h.negotiable, h.khotiyan, h.verification_status,
               h.status as housing_status
        FROM services s
        INNER JOIN housing h ON s.service_id = h.service_id
        WHERE s.type = 'housing'
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Joined query results: " . count($posts) . " records\n";
    
    if (count($posts) > 0) {
        echo "Sample record:\n";
        print_r($posts[0]);
    } else {
        echo "No housing data found. You may need to insert some sample data.\n";
        echo "To insert sample data, you can use the Post Service Page to create housing posts.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
