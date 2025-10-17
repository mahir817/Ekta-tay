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

// Ensure schema exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        title VARCHAR(150) NOT NULL,
        category VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        date DATE NOT NULL,
        type ENUM('personal','shared') NOT NULL DEFAULT 'personal',
        status ENUM('paid','unpaid','pending') NOT NULL DEFAULT 'unpaid',
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'DB Schema Error: '.$e->getMessage()]);
    exit();
}

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

    $stmt = $pdo->prepare("INSERT INTO expenses (user_id, title, category, amount, date, type, status, description) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $title, $category, $amount, $date, $type, $status, $description ?: null]);

    echo json_encode(['success' => true, 'message' => 'Expense added successfully']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
