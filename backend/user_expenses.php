<?php
session_start();
require_once "db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Handle POST request for adding new expense
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $due_date = $_POST['due_date'] ?? null;
        $status = $_POST['status'] ?? 'unpaid';

        if (empty($name) || $amount <= 0) {
            echo json_encode(["success" => false, "message" => "Name and amount are required"]);
            exit();
        }

        $insertStmt = $pdo->prepare("
            INSERT INTO expenses (user_id, name, amount, due_date, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([$user_id, $name, $amount, $due_date, $status]);

        echo json_encode([
            "success" => true,
            "message" => "Expense added successfully",
            "expense_id" => $pdo->lastInsertId()
        ]);
        exit();
    }

    // Handle GET request for fetching expenses
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    $sql = "
        SELECT id, name, amount, due_date, status, created_at
        FROM expenses 
        WHERE user_id = ?
    ";
    
    $params = [$user_id];
    
    if ($status && in_array($status, ['paid', 'unpaid'])) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get expense summary
    $summaryStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_count,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
            SUM(CASE WHEN status = 'unpaid' THEN amount ELSE 0 END) as unpaid_amount,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
            COUNT(CASE WHEN status = 'unpaid' THEN 1 END) as unpaid_count
        FROM expenses 
        WHERE user_id = ?
    ");
    $summaryStmt->execute([$user_id]);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    // Get monthly breakdown for current year
    $monthlyStmt = $pdo->prepare("
        SELECT 
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            SUM(amount) as total_amount,
            COUNT(*) as count
        FROM expenses 
        WHERE user_id = ? AND YEAR(created_at) = YEAR(CURDATE())
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY YEAR(created_at), MONTH(created_at)
    ");
    $monthlyStmt->execute([$user_id]);
    $monthlyBreakdown = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get expenses by category/name for chart
    $categoryStmt = $pdo->prepare("
        SELECT 
            name as category,
            SUM(amount) as total_amount,
            COUNT(*) as count
        FROM expenses 
        WHERE user_id = ?
        GROUP BY name
        ORDER BY total_amount DESC
        LIMIT 10
    ");
    $categoryStmt->execute([$user_id]);
    $categoryBreakdown = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => [
            "expenses" => $expenses,
            "summary" => $summary,
            "monthly_breakdown" => $monthlyBreakdown,
            "category_breakdown" => $categoryBreakdown
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error processing expenses: " . $e->getMessage()
    ]);
}
?>
