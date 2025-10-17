<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit(); }

try {
    $userId = (int)$_SESSION['user_id'];

    $sql = "SELECT se.id, se.title, se.amount, se.date, se.description,
                   ses.user_id, ses.share_amount, ses.status
            FROM shared_expenses se
            JOIN shared_expense_shares ses ON se.id = ses.shared_expense_id
            WHERE se.owner_id = :uid OR ses.user_id = :uid
            ORDER BY se.date DESC, se.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid'=>$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success'=>true,'shared'=>$rows]);
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}



$user_id = $_GET['user_id'];
$sql = "SELECT se.*, e.title, e.category, e.date FROM shared_expenses se
        JOIN expenses e ON se.expense_id = e.id
        WHERE se.user_id = $user_id";
$result = $conn->query($sql);
$shared = [];
while ($row = $result->fetch_assoc()) $shared[] = $row;

echo json_encode($shared);
