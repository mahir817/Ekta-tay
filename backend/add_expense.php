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

// Using existing expenses table schema

try {
    $userId = (int)$_SESSION['user_id'];
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'Others');
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $date = $_POST['date'] ?? '';
    $type = $_POST['type'] ?? 'personal';
    $status = $_POST['status'] ?? 'unpaid';
    $description = trim($_POST['description'] ?? '');

    if ($title === '' || $amount <= 0 || $date === '') {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }

    // Use existing database schema: name, amount, due_date, status
    $stmt = $pdo->prepare("INSERT INTO expenses (user_id, name, amount, due_date, status) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $title, $amount, $date, $status]);
    $expenseId = $pdo->lastInsertId();

    // Log activity
    require_once __DIR__ . '/log_activity.php';
    $activityTitle = "Added new expense";
    $activityDescription = "Added: " . $title . " - ৳" . number_format($amount, 0);
    logActivity($userId, 'expense_added', $activityTitle, $activityDescription, $expenseId);

    echo json_encode(['success' => true, 'message' => 'Expense added successfully']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
