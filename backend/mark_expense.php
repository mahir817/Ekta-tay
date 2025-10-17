<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('session.cookie_path', '/');
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
        exit();
    }

    $rawId = isset($input['id']) ? (string)$input['id'] : '';
    $status = isset($input['status']) ? trim($input['status']) : '';
    $source = isset($input['source']) ? trim($input['source']) : '';

    if ($rawId === '' || $status === '') {
        echo json_encode(['success' => false, 'message' => 'Missing id or status']);
        exit();
    }

    $uid = (int)$_SESSION['user_id'];
    $isShared = strlen($rawId) > 0 && ($rawId[0] === 's' || strtolower($source) === 'shared');
    $id = (int) ltrim($rawId, 'sS');

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid expense id']);
        exit();
    }

    if ($isShared) {
        $stmt = $pdo->prepare('UPDATE shared_expense_shares SET status = :st WHERE id = :id AND user_id = :uid');
        $stmt->execute([':st' => $status, ':id' => $id, ':uid' => $uid]);
        $affected = $stmt->rowCount();
        if ($affected === 0) {
            echo json_encode(['success' => false, 'message' => 'Nothing updated — check permission or id (shared)']);
            exit();
        }
    } else {
        $stmt = $pdo->prepare('UPDATE expenses SET status = :st WHERE id = :id AND user_id = :uid');
        $stmt->execute([':st' => $status, ':id' => $id, ':uid' => $uid]);
        $affected = $stmt->rowCount();
        if ($affected === 0) {
            echo json_encode(['success' => false, 'message' => 'Nothing updated — check permission or id (personal)']);
            exit();
        }
    }

    echo json_encode(['success' => true, 'message' => 'Status updated']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
