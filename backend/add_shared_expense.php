<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Not authenticated']);
    exit();
}

try {
    // Shared tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS shared_expenses (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_id INT UNSIGNED NOT NULL,
        title VARCHAR(150) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        date DATE NOT NULL,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS shared_expense_shares (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        shared_expense_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        share_amount DECIMAL(10,2) NOT NULL,
        status ENUM('paid','unpaid','pending') NOT NULL DEFAULT 'unpaid'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $ownerId = (int)$_SESSION['user_id'];
    $title = trim($_POST['title'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $date = $_POST['date'] ?? date('Y-m-d');
    $description = trim($_POST['description'] ?? '');
    $participants = $_POST['participants'] ?? '';

    if ($title === '' || $amount <= 0 || $participants === '') {
        echo json_encode(['success'=>false,'message'=>'Missing fields']);
        exit();
    }

    $userIds = array_filter(array_map('intval', explode(',', $participants)));
    if (empty($userIds)) {
        echo json_encode(['success'=>false,'message'=>'No participants']);
        exit();
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO shared_expenses (owner_id, title, amount, date, description)
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$ownerId, $title, $amount, $date, $description ?: null]);
    $sharedId = (int)$pdo->lastInsertId();

    $share = round($amount / count($userIds), 2);
    $ins = $pdo->prepare("INSERT INTO shared_expense_shares (shared_expense_id, user_id, share_amount) VALUES (?, ?, ?)");
    foreach ($userIds as $uid) $ins->execute([$sharedId, $uid, $share]);

    $pdo->commit();
    echo json_encode(['success'=>true,'message'=>'Shared expense added']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
