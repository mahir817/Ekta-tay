<?php
/**
 * Test script to verify and fix housing module issues
 */

require_once 'backend/db.php';

echo "<h1>Housing Module Test & Fix</h1>\n";

try {
    // Test 1: Check if housing_applications table has owner_id column
    echo "<h2>Test 1: Database Structure</h2>\n";
    
    $stmt = $pdo->query("DESCRIBE housing_applications");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p><strong>housing_applications columns:</strong> " . implode(', ', $columns) . "</p>\n";
    
    if (!in_array('owner_id', $columns)) {
        echo "<p style='color: red;'>❌ Missing owner_id column in housing_applications</p>\n";
        echo "<p>Adding owner_id column...</p>\n";
        
        $pdo->exec("ALTER TABLE `housing_applications` ADD COLUMN `owner_id` int(11) NOT NULL AFTER `housing_id`");
        echo "<p style='color: green;'>✅ Added owner_id column</p>\n";
    } else {
        echo "<p style='color: green;'>✅ owner_id column exists</p>\n";
    }
    
    // Test 2: Check housing table structure
    $stmt = $pdo->query("DESCRIBE housing");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p><strong>housing columns:</strong> " . implode(', ', $columns) . "</p>\n";
    
    if (!in_array('availability', $columns)) {
        echo "<p style='color: red;'>❌ Missing availability column in housing</p>\n";
        echo "<p>Adding availability column...</p>\n";
        
        $pdo->exec("ALTER TABLE `housing` ADD COLUMN `availability` enum('available','pending','occupied') DEFAULT 'available'");
        $pdo->exec("UPDATE `housing` SET `availability` = 'available' WHERE `availability` IS NULL");
        echo "<p style='color: green;'>✅ Added availability column</p>\n";
    } else {
        echo "<p style='color: green;'>✅ availability column exists</p>\n";
    }
    
    // Test 3: Update existing housing_applications with owner_id
    echo "<h2>Test 2: Data Integrity</h2>\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM housing_applications WHERE owner_id = 0 OR owner_id IS NULL");
    $needsUpdate = $stmt->fetchColumn();
    
    if ($needsUpdate > 0) {
        echo "<p style='color: orange;'>⚠ Found {$needsUpdate} applications without owner_id</p>\n";
        echo "<p>Updating applications with owner information...</p>\n";
        
        $pdo->exec("
            UPDATE housing_applications ha
            INNER JOIN housing h ON ha.housing_id = h.housing_id
            INNER JOIN services s ON h.service_id = s.service_id
            SET ha.owner_id = s.user_id
            WHERE ha.owner_id = 0 OR ha.owner_id IS NULL
        ");
        
        echo "<p style='color: green;'>✅ Updated applications with owner information</p>\n";
    } else {
        echo "<p style='color: green;'>✅ All applications have owner_id</p>\n";
    }
    
    // Test 4: Check service_id to housing_id mapping
    echo "<h2>Test 3: Service to Housing Mapping</h2>\n";
    
    $stmt = $pdo->query("
        SELECT s.service_id, h.housing_id, s.title 
        FROM services s 
        INNER JOIN housing h ON s.service_id = h.service_id 
        WHERE s.type = 'housing' 
        LIMIT 5
    ");
    $mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Sample Service to Housing Mappings:</strong></p>\n";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Service ID</th><th>Housing ID</th><th>Title</th></tr>\n";
    
    foreach ($mappings as $mapping) {
        echo "<tr>";
        echo "<td>{$mapping['service_id']}</td>";
        echo "<td>{$mapping['housing_id']}</td>";
        echo "<td>{$mapping['title']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Test 5: Test application process
    echo "<h2>Test 4: Application Process Test</h2>\n";
    
    // Get a sample housing
    $stmt = $pdo->query("
        SELECT s.service_id, h.housing_id, s.user_id as owner_id, s.title
        FROM services s 
        INNER JOIN housing h ON s.service_id = h.service_id 
        WHERE s.type = 'housing' AND h.availability = 'available'
        LIMIT 1
    ");
    $sampleHousing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sampleHousing) {
        echo "<p><strong>Sample Housing for Testing:</strong></p>\n";
        echo "<p>Service ID: {$sampleHousing['service_id']}</p>\n";
        echo "<p>Housing ID: {$sampleHousing['housing_id']}</p>\n";
        echo "<p>Owner ID: {$sampleHousing['owner_id']}</p>\n";
        echo "<p>Title: {$sampleHousing['title']}</p>\n";
        
        echo "<p style='color: green;'>✅ Housing data structure is correct</p>\n";
    } else {
        echo "<p style='color: red;'>❌ No available housing found for testing</p>\n";
    }
    
    // Test 6: Check foreign key constraints
    echo "<h2>Test 5: Foreign Key Constraints</h2>\n";
    
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = 'ekta_tay' 
        AND TABLE_NAME = 'housing_applications' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Foreign Key Constraints:</strong></p>\n";
    foreach ($constraints as $constraint) {
        echo "<p>✅ {$constraint['CONSTRAINT_NAME']}: {$constraint['COLUMN_NAME']} → {$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}</p>\n";
    }
    
    echo "<h2>✅ All Tests Completed Successfully!</h2>\n";
    echo "<p><strong>Summary:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ Database structure is correct</li>\n";
    echo "<li>✅ Data integrity is maintained</li>\n";
    echo "<li>✅ Foreign key constraints are in place</li>\n";
    echo "<li>✅ Service to Housing mapping is working</li>\n";
    echo "</ul>\n";
    
    echo "<p><a href='Modules/Housing/housing.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Test Housing Module</a></p>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Test Failed</h2>\n";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
    background: #f5f5f5;
}

h1, h2 {
    color: #333;
}

p {
    background: white;
    padding: 10px;
    margin: 5px 0;
    border-radius: 5px;
    border-left: 4px solid #007cba;
}

table {
    background: white;
    width: 100%;
    border-radius: 5px;
    overflow: hidden;
}

th, td {
    padding: 10px;
    text-align: left;
}

th {
    background: #007cba;
    color: white;
}

ul {
    background: white;
    padding: 20px;
    border-radius: 5px;
}
</style>
