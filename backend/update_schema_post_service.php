<?php
// One-off schema updater to add columns required by post service & profile flows
session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

try {
    $changes = [];

    // users.tagline (optional profile field)
    $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'tagline'")->fetch(PDO::FETCH_ASSOC);
    if (!$col) {
        $pdo->exec("ALTER TABLE users ADD COLUMN tagline VARCHAR(255) DEFAULT NULL AFTER gender");
        $changes[] = "Added users.tagline";
    }

    // services.location (used across app in selects/inserts)
    $col = $pdo->query("SHOW COLUMNS FROM services LIKE 'location'")->fetch(PDO::FETCH_ASSOC);
    if (!$col) {
        $pdo->exec("ALTER TABLE services ADD COLUMN location VARCHAR(100) DEFAULT NULL AFTER price");
        $changes[] = "Added services.location";
    }

    // services.price (defensive on older schemas)
    $col = $pdo->query("SHOW COLUMNS FROM services LIKE 'price'")->fetch(PDO::FETCH_ASSOC);
    if (!$col) {
        $pdo->exec("ALTER TABLE services ADD COLUMN price DECIMAL(10,2) DEFAULT NULL AFTER type");
        $changes[] = "Added services.price";
    }

    // housing.location (defensive); coordinates and generalized_location used by app
    $col = $pdo->query("SHOW COLUMNS FROM housing LIKE 'location'")->fetch(PDO::FETCH_ASSOC);
    if (!$col) {
        $pdo->exec("ALTER TABLE housing ADD COLUMN location VARCHAR(255) DEFAULT NULL AFTER availability");
        $changes[] = "Added housing.location";
    }
    $col = $pdo->query("SHOW COLUMNS FROM housing LIKE 'coordinates'")->fetch(PDO::FETCH_ASSOC);
    if (!$col) {
        $pdo->exec("ALTER TABLE housing ADD COLUMN coordinates VARCHAR(100) DEFAULT NULL AFTER location");
        $changes[] = "Added housing.coordinates";
    }
    $col = $pdo->query("SHOW COLUMNS FROM housing LIKE 'generalized_location'")->fetch(PDO::FETCH_ASSOC);
    if (!$col) {
        $pdo->exec("ALTER TABLE housing ADD COLUMN generalized_location VARCHAR(50) DEFAULT '' AFTER coordinates");
        $changes[] = "Added housing.generalized_location";
    }

    echo json_encode([
        'success' => true,
        'message' => 'Schema update completed',
        'changes' => $changes
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Schema update failed: ' . $e->getMessage()
    ]);
}
?>


