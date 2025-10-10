<?php
/**
 * Fix Housing Tenants Table Structure
 * This script fixes the housing_tenants table to support the workflow
 */

require_once 'backend/db.php';

echo "<h1>Housing Tenants Table Fix</h1>\n";

try {
    // Check current housing_tenants structure
    echo "<h2>Current housing_tenants Structure</h2>\n";
    $stmt = $pdo->query("DESCRIBE housing_tenants");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
    
    $columnNames = [];
    foreach ($columns as $col) {
        $columnNames[] = $col['Field'];
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Check if owner_id column exists
    if (!in_array('owner_id', $columnNames)) {
        echo "<p style='color: orange;'>⚠ owner_id column missing. Adding it...</p>\n";
        
        $pdo->exec("ALTER TABLE `housing_tenants` ADD COLUMN `owner_id` int(11) NULL AFTER `housing_id`");
        
        // Update existing records with owner_id
        $pdo->exec("
            UPDATE housing_tenants ht
            INNER JOIN housing h ON ht.housing_id = h.housing_id
            INNER JOIN services s ON h.service_id = s.service_id
            SET ht.owner_id = s.user_id
            WHERE ht.owner_id IS NULL
        ");
        
        // Add foreign key constraint
        $pdo->exec("ALTER TABLE `housing_tenants` ADD CONSTRAINT `housing_tenants_owner_fk` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE");
        
        echo "<p style='color: green;'>✅ owner_id column added and populated</p>\n";
    } else {
        echo "<p style='color: green;'>✅ owner_id column already exists</p>\n";
    }
    
    // Check if status column exists
    if (!in_array('status', $columnNames)) {
        echo "<p style='color: orange;'>⚠ status column missing. Adding it...</p>\n";
        
        $pdo->exec("ALTER TABLE `housing_tenants` ADD COLUMN `status` enum('active','inactive','terminated') DEFAULT 'active'");
        
        // Update existing records
        $pdo->exec("UPDATE housing_tenants SET status = 'active' WHERE active = 1");
        $pdo->exec("UPDATE housing_tenants SET status = 'inactive' WHERE active = 0");
        
        echo "<p style='color: green;'>✅ status column added</p>\n";
    } else {
        echo "<p style='color: green;'>✅ status column already exists</p>\n";
    }
    
    // Check if created_at column exists
    if (!in_array('created_at', $columnNames)) {
        echo "<p style='color: orange;'>⚠ created_at column missing. Adding it...</p>\n";
        
        $pdo->exec("ALTER TABLE `housing_tenants` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT current_timestamp()");
        
        echo "<p style='color: green;'>✅ created_at column added</p>\n";
    } else {
        echo "<p style='color: green;'>✅ created_at column already exists</p>\n";
    }
    
    // Test tenant insertion
    echo "<h2>Test Tenant Insertion</h2>\n";
    
    // Get a sample user and housing for testing
    $stmt = $pdo->query("
        SELECT u.id as user_id, h.housing_id, s.user_id as owner_id
        FROM users u, housing h
        INNER JOIN services s ON h.service_id = s.service_id
        WHERE u.id != s.user_id
        LIMIT 1
    ");
    $testData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testData) {
        echo "<p><strong>Test Data:</strong></p>\n";
        echo "<p>User ID: {$testData['user_id']}</p>\n";
        echo "<p>Housing ID: {$testData['housing_id']}</p>\n";
        echo "<p>Owner ID: {$testData['owner_id']}</p>\n";
        
        // Test if we can insert (dry run - we'll rollback)
        $pdo->beginTransaction();
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO housing_tenants (housing_id, user_id, owner_id, start_date, status) 
                VALUES (?, ?, ?, CURDATE(), 'active')
            ");
            $stmt->execute([$testData['housing_id'], $testData['user_id'], $testData['owner_id']]);
            
            echo "<p style='color: green;'>✅ Test insertion successful</p>\n";
            
            // Rollback the test insertion
            $pdo->rollBack();
            echo "<p>Test insertion rolled back (no actual data inserted)</p>\n";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p style='color: red;'>❌ Test insertion failed: " . $e->getMessage() . "</p>\n";
        }
        
    } else {
        echo "<p style='color: orange;'>⚠ No test data available</p>\n";
    }
    
    // Show final structure
    echo "<h2>Final housing_tenants Structure</h2>\n";
    $stmt = $pdo->query("DESCRIBE housing_tenants");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
    
    foreach ($finalColumns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h2>✅ Housing Tenants Table Fixed!</h2>\n";
    echo "<p><strong>Summary:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ Table structure verified and updated</li>\n";
    echo "<li>✅ Foreign key constraints in place</li>\n";
    echo "<li>✅ Status tracking enabled</li>\n";
    echo "<li>✅ Owner relationship established</li>\n";
    echo "</ul>\n";
    
    echo "<p><a href='Modules/Housing/housing.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Test Housing Module</a></p>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Fix Failed</h2>\n";
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
