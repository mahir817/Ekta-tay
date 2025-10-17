<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    // For local testing:
    // $_SESSION['user_id'] = 1;
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    $uid = (int)$_SESSION['user_id'];

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing expense id']);
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = :id AND user_id = :uid");
    $stmt->execute([':id' => $id, ':uid' => $uid]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
