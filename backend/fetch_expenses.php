<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    $userId = (int)$_SESSION['user_id'];
    $category = trim($_GET['category'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $from = trim($_GET['from'] ?? '');
    $to = trim($_GET['to'] ?? '');
    $sort = $_GET['sort'] ?? 'date_desc';

    $where = ["user_id = :uid"];
    $params = [':uid' => $userId];

    if ($category !== '') { $where[] = 'category = :cat'; $params[':cat'] = $category; }
    if ($status !== '') { $where[] = 'status = :st'; $params[':st'] = $status; }
    if ($from !== '') { $where[] = 'date >= :from'; $params[':from'] = $from; }
    if ($to !== '') { $where[] = 'date <= :to'; $params[':to'] = $to; }

    $orderBy = match($sort) {
        'date_asc' => 'date ASC',
        'amount_desc' => 'amount DESC',
        'amount_asc' => 'amount ASC',
        default => 'date DESC'
    };

    // Personal expenses
    $stmt = $pdo->prepare("
        SELECT id, title, category, amount, date, type, status
        FROM expenses
        WHERE " . implode(' AND ', $where) . " ORDER BY $orderBy
    ");
    $stmt->execute($params);
    $personal = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Shared expenses (optional)
    $shared = [];
    try {
        $stmt2 = $pdo->prepare("
            SELECT s.id AS share_id, e.title, e.date, s.share_amount AS amount, s.status
            FROM shared_expense_shares s
            JOIN shared_expenses e ON e.id = s.shared_expense_id
            WHERE s.user_id = :uid
        ");
        $stmt2->execute([':uid' => $userId]);
        foreach ($stmt2 as $r) {
            $shared[] = [
                'id' => 's' . $r['share_id'],
                'title' => $r['title'],
                'category' => 'Shared',
                'amount' => $r['amount'],
                'date' => $r['date'],
                'type' => 'shared',
                'status' => $r['status']
            ];
        }
    } catch (Throwable $e) {
        // ignore if shared tables missing
    }

    $data = array_merge($personal, $shared);
    echo json_encode(['success' => true, 'expenses' => $data]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
