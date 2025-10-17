<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

try {
    $sql = "
        SELECT
            s.service_id, s.title, s.description, s.price, s.location, s.created_at,
            t.subject, t.class_level, t.tuition_type
        FROM services s
        JOIN tuitions t ON t.service_id = s.service_id
        WHERE s.type = 'tuition'
    ";
    $params = [];

    if (!empty($_GET['subject'])) { $sql .= " AND t.subject = :subject"; $params[':subject'] = $_GET['subject']; }
    if (!empty($_GET['class_level'])) { $sql .= " AND t.class_level = :class_level"; $params[':class_level'] = $_GET['class_level']; }
    if (!empty($_GET['tuition_type'])) { $sql .= " AND t.tuition_type = :tuition_type"; $params[':tuition_type'] = $_GET['tuition_type']; }
    if (!empty($_GET['location'])) { $sql .= " AND (s.location LIKE :loc)"; $params[':loc'] = "%".$_GET['location']."%"; }
    if (!empty($_GET['min_price'])) { $sql .= " AND (s.price IS NULL OR s.price >= :minp)"; $params[':minp'] = (float)$_GET['min_price']; }
    if (!empty($_GET['max_price'])) { $sql .= " AND (s.price IS NULL OR s.price <= :maxp)"; $params[':maxp'] = (float)$_GET['max_price']; }

    $sql .= " ORDER BY s.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Error fetching tuitions: '.$e->getMessage()]);
}
?>
