<?php
/**
 * Debug Tenant Confirmation Issue
 */

require_once 'backend/db.php';

echo "<h1>Debug Tenant Confirmation</h1>\n";

try {
    // Check housing_applications table
    echo "<h2>Housing Applications Sample</h2>\n";
    $stmt = $pdo->query("
        SELECT ha.application_id, ha.housing_id, ha.owner_id, ha.applicant_id, ha.status,
               u1.name as applicant_name, u2.name as owner_name
        FROM housing_applications ha
        LEFT JOIN users u1 ON ha.applicant_id = u1.id
        LEFT JOIN users u2 ON ha.owner_id = u2.id
        WHERE ha.status = 'shortlisted'
        LIMIT 5
    ");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($applications) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>\n";
        echo "<tr><th>App ID</th><th>Housing ID</th><th>Owner ID</th><th>Applicant ID</th><th>Owner Name</th><th>Applicant Name</th><th>Status</th></tr>\n";
        
        foreach ($applications as $app) {
            echo "<tr>";
            echo "<td>{$app['application_id']}</td>";
            echo "<td>{$app['housing_id']}</td>";
            echo "<td>{$app['owner_id']}</td>";
            echo "<td>{$app['applicant_id']}</td>";
            echo "<td>" . ($app['owner_name'] ?? 'NULL') . "</td>";
            echo "<td>" . ($app['applicant_name'] ?? 'NULL') . "</td>";
            echo "<td>{$app['status']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // Test with first application
        $testApp = $applications[0];
        echo "<h2>Testing with Application ID: {$testApp['application_id']}</h2>\n";
        
        // Check if applicant exists in users table
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ?");
        $stmt->execute([$testApp['applicant_id']]);
        $applicant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($applicant) {
            echo "<p style='color: green;'>✅ Applicant exists: {$applicant['name']} (ID: {$applicant['id']})</p>\n";
        } else {
            echo "<p style='color: red;'>❌ Applicant not found in users table (ID: {$testApp['applicant_id']})</p>\n";
        }
        
        // Check if owner exists in users table
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ?");
        $stmt->execute([$testApp['owner_id']]);
        $owner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($owner) {
            echo "<p style='color: green;'>✅ Owner exists: {$owner['name']} (ID: {$owner['id']})</p>\n";
        } else {
            echo "<p style='color: red;'>❌ Owner not found in users table (ID: {$testApp['owner_id']})</p>\n";
        }
        
        // Check housing_tenants table structure
        echo "<h2>Housing Tenants Table Structure</h2>\n";
        $stmt = $pdo->query("DESCRIBE housing_tenants");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
        
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // Test insertion (dry run)
        echo "<h2>Test Tenant Insertion (Dry Run)</h2>\n";
        
        $pdo->beginTransaction();
        
        try {
            // Check if owner_id column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM housing_tenants LIKE 'owner_id'");
            if ($stmt->rowCount() > 0) {
                echo "<p>Using owner_id column...</p>\n";
                $stmt = $pdo->prepare("
                    INSERT INTO housing_tenants (housing_id, user_id, owner_id, start_date, status) 
                    VALUES (?, ?, ?, CURDATE(), 'active')
                ");
                $stmt->execute([$testApp['housing_id'], $testApp['applicant_id'], $testApp['owner_id']]);
            } else {
                echo "<p>owner_id column not found, using basic structure...</p>\n";
                $stmt = $pdo->prepare("
                    INSERT INTO housing_tenants (housing_id, user_id, start_date, active) 
                    VALUES (?, ?, CURDATE(), 1)
                ");
                $stmt->execute([$testApp['housing_id'], $testApp['applicant_id']]);
            }
            
            echo "<p style='color: green;'>✅ Test insertion successful!</p>\n";
            
            // Rollback the test
            $pdo->rollBack();
            echo "<p>Test rolled back (no actual data inserted)</p>\n";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p style='color: red;'>❌ Test insertion failed: " . $e->getMessage() . "</p>\n";
            
            // Show detailed error info
            echo "<p><strong>Error Details:</strong></p>\n";
            echo "<p>Error Code: " . $e->getCode() . "</p>\n";
            echo "<p>Error Message: " . $e->getMessage() . "</p>\n";
            
            // Check foreign key constraints
            echo "<h3>Foreign Key Constraints on housing_tenants</h3>\n";
            $stmt = $pdo->query("
                SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = 'ekta_tay' 
                AND TABLE_NAME = 'housing_tenants' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($constraints) > 0) {
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
                echo "<tr><th>Constraint</th><th>Column</th><th>References</th></tr>\n";
                foreach ($constraints as $constraint) {
                    echo "<tr>";
                    echo "<td>{$constraint['CONSTRAINT_NAME']}</td>";
                    echo "<td>{$constraint['COLUMN_NAME']}</td>";
                    echo "<td>{$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}</td>";
                    echo "</tr>\n";
                }
                echo "</table>\n";
            } else {
                echo "<p>No foreign key constraints found</p>\n";
            }
        }
        
    } else {
        echo "<p style='color: orange;'>⚠ No shortlisted applications found for testing</p>\n";
        
        // Show all applications
        echo "<h2>All Applications</h2>\n";
        $stmt = $pdo->query("
            SELECT ha.application_id, ha.housing_id, ha.owner_id, ha.applicant_id, ha.status
            FROM housing_applications ha
            LIMIT 10
        ");
        $allApps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($allApps) > 0) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
            echo "<tr><th>App ID</th><th>Housing ID</th><th>Owner ID</th><th>Applicant ID</th><th>Status</th></tr>\n";
            
            foreach ($allApps as $app) {
                echo "<tr>";
                echo "<td>{$app['application_id']}</td>";
                echo "<td>{$app['housing_id']}</td>";
                echo "<td>{$app['owner_id']}</td>";
                echo "<td>{$app['applicant_id']}</td>";
                echo "<td>{$app['status']}</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }
    }
    
    echo "<p><a href='fix_housing_tenants.php' style='background: #ff6b35; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Fix Housing Tenants Table</a></p>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Debug Failed</h2>\n";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    background: #f5f5f5;
}

h1, h2, h3 {
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
    margin: 10px 0;
}

th, td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background: #007cba;
    color: white;
}

tr:nth-child(even) {
    background: #f9f9f9;
}
</style>
