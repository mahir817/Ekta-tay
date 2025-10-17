<?php
session_start();
require_once __DIR__ . '/db.php';

// Set content type for proper response handling
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid method']);
        exit();
    }

    $userId = (int)$_SESSION['user_id'];
    
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $rent = isset($_POST['rent']) && $_POST['rent'] !== '' ? (float)$_POST['rent'] : 0;

    // Validate required fields
    if ($title === '' || $description === '' || $rent <= 0) {
        echo json_encode(['success' => false, 'message' => 'Title, description, and rent are required']);
        exit();
    }

    $pdo->beginTransaction();

    // Insert into services table
    $serviceStmt = $pdo->prepare("INSERT INTO services (user_id, title, description, type, price, location) VALUES (?, ?, ?, 'housing', ?, ?)");
    $serviceStmt->execute([$userId, $title, $description, $rent, $location ?: null]);
    $serviceId = (int)$pdo->lastInsertId();

    // Insert into housing table
    $housingStmt = $pdo->prepare("INSERT INTO housing (
        service_id, property_type, size_sqft, floor_no, total_floors,
        furnished_status, parking_spaces, bedrooms, bathrooms, balconies,
        rent, service_charge, advance_deposit, available_from, available_for,
        negotiable, property_condition, status, furnished, availability,
        location, coordinates, generalized_location
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    
    $housingStmt->execute([
        $serviceId,
        $_POST['property_type'] ?? 'apartment',
        isset($_POST['size_sqft']) && $_POST['size_sqft'] !== '' ? (int)$_POST['size_sqft'] : null,
        $_POST['floor_no'] ?? null,
        isset($_POST['total_floors']) && $_POST['total_floors'] !== '' ? (int)$_POST['total_floors'] : null,
        $_POST['furnished_status'] ?? 'unfurnished',
        isset($_POST['parking_spaces']) && $_POST['parking_spaces'] !== '' ? (int)$_POST['parking_spaces'] : 0,
        isset($_POST['bedrooms']) && $_POST['bedrooms'] !== '' ? (int)$_POST['bedrooms'] : 0,
        isset($_POST['bathrooms']) && $_POST['bathrooms'] !== '' ? (int)$_POST['bathrooms'] : 0,
        isset($_POST['balconies']) && $_POST['balconies'] !== '' ? (int)$_POST['balconies'] : 0,
        $rent,
        isset($_POST['service_charge']) && $_POST['service_charge'] !== '' ? (float)$_POST['service_charge'] : 0,
        isset($_POST['advance_deposit']) && $_POST['advance_deposit'] !== '' ? (float)$_POST['advance_deposit'] : 0,
        $_POST['available_from'] ?? null,
        $_POST['available_for'] ?? 'any',
        isset($_POST['negotiable']) && $_POST['negotiable'] == '1' ? 1 : 0,
        $_POST['property_condition'] ?? 'N/A',
        'available',
        $_POST['furnished_status'] ?? 'unfurnished',
        'available',
        $location ?: null,
        $_POST['coordinates'] ?? null,
        $_POST['generalized_location'] ?? ''
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'service_id' => $serviceId,
        'message' => 'Housing post created successfully!'
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
