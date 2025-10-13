<?php
// Script to add missing columns to users table
require_once "db.php";

try {
    // Check if profile_img column exists
    $checkProfileImg = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_img'");
    if ($checkProfileImg->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_img VARCHAR(255) DEFAULT NULL");
        echo "Added profile_img column to users table\n";
    } else {
        echo "profile_img column already exists\n";
    }

    // Check if tagline column exists
    $checkTagline = $pdo->query("SHOW COLUMNS FROM users LIKE 'tagline'");
    if ($checkTagline->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN tagline VARCHAR(255) DEFAULT NULL");
        echo "Added tagline column to users table\n";
    } else {
        echo "tagline column already exists\n";
    }

    echo "Database update completed successfully!\n";

} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?>
