<?php
require_once "db.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $serviceId = intval($data['id'] ?? 0);
    $userId = $_SESSION['user_id'];

    if (!$serviceId) {
        echo json_encode(['success' => false, 'message' => 'Service ID missing']);
        exit;
    }

    try {
        // Check ownership
        $checkStmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ? AND user_id = ?");
        $checkStmt->execute([$serviceId, $userId]);
        $post = $checkStmt->fetch();

        if (!$post) {
            echo json_encode(['success' => false, 'message' => 'You do not own this post']);
            exit;
        }

        // Delete housing related data
        $deleteHousing = $pdo->prepare("DELETE FROM housing WHERE service_id = ?");
        $deleteHousing->execute([$serviceId]);

        // Delete service
        $deleteService = $pdo->prepare("DELETE FROM services WHERE service_id = ?");
        $deleteService->execute([$serviceId]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error deleting post: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
