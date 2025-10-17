<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

try {
    $sql = "
        SELECT
            s.service_id, s.title, s.description, s.price, s.location, s.created_at,
            j.job_type, j.company, j.work_type, j.salary_min, j.salary_max
        FROM services s
        JOIN jobs j ON j.service_id = s.service_id
        WHERE s.type = 'job'
    ";
    $params = [];

    // Keyword across title/company
    if (!empty($_GET['keyword'])) {
        $sql .= " AND (s.title LIKE :kw OR j.company LIKE :kw)";
        $params[':kw'] = "%".$_GET['keyword']."%";
    }
    // Job type
    if (!empty($_GET['job_type'])) { $sql .= " AND j.job_type = :jt"; $params[':jt'] = $_GET['job_type']; }
    // Work type
    if (!empty($_GET['work_type'])) { $sql .= " AND j.work_type = :wt"; $params[':wt'] = $_GET['work_type']; }
    // Location (services.location)
    if (!empty($_GET['location'])) { $sql .= " AND (s.location LIKE :loc)"; $params[':loc'] = "%".$_GET['location']."%"; }
    // Salary range
    if (!empty($_GET['min_price'])) {
        $sql .= " AND ( (s.price IS NOT NULL AND s.price >= :minp) OR (j.salary_min IS NOT NULL AND j.salary_min >= :minp) )";
        $params[':minp'] = (float)$_GET['min_price'];
    }
    if (!empty($_GET['max_price'])) {
        $sql .= " AND ( (s.price IS NOT NULL AND s.price <= :maxp) OR (j.salary_max IS NOT NULL AND j.salary_max <= :maxp) )";
        $params[':maxp'] = (float)$_GET['max_price'];
    }

    $sql .= " ORDER BY s.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Error fetching jobs: '.$e->getMessage()]);
}
