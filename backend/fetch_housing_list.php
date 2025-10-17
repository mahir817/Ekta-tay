<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

try {
    $sql = "
        SELECT s.service_id, s.title, s.description, s.location, s.price, s.created_at, s.user_id,
               h.id AS housing_id, h.property_type, h.size_sqft, h.floor_no, h.total_floors, h.furnished_status,
               h.bedrooms, h.bathrooms, h.balconies, h.rent, h.service_charge, h.advance_deposit,
               h.available_from, h.available_for, h.negotiable, h.khotiyan, h.verification_status,
               h.status AS availability, h.location as housing_location, h.coordinates, h.generalized_location
        FROM services s
        JOIN housing h ON s.service_id = h.service_id
        WHERE s.type = 'housing' AND (h.status = 'available' OR h.status IS NULL)
    ";
    $params = [];

    // Filters
    if (!empty($_GET['location'])) { $sql .= " AND (s.location LIKE :loc OR h.location LIKE :loc)"; $params[':loc'] = "%".$_GET['location']."%"; }
    if (!empty($_GET['property_type'])) { $sql .= " AND h.property_type = :ptype"; $params[':ptype'] = $_GET['property_type']; }
    if (!empty($_GET['furnished_status'])) { $sql .= " AND h.furnished_status = :fstat"; $params[':fstat'] = $_GET['furnished_status']; }
    if (!empty($_GET['bedrooms'])) {
        if ($_GET['bedrooms'] === '4+') { $sql .= " AND h.bedrooms >= 4"; }
        else { $sql .= " AND h.bedrooms = :beds"; $params[':beds'] = (int)$_GET['bedrooms']; }
    }
    if (!empty($_GET['rent_min'])) { $sql .= " AND h.rent >= :rmin"; $params[':rmin'] = (float)$_GET['rent_min']; }
    if (!empty($_GET['rent_max'])) { $sql .= " AND h.rent <= :rmax"; $params[':rmax'] = (float)$_GET['rent_max']; }
    if (!empty($_GET['available_from'])) { $sql .= " AND (h.available_from IS NULL OR h.available_from <= :afrom)"; $params[':afrom'] = $_GET['available_from']; }

    $sql .= " ORDER BY s.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Transform to match frontend expectations
    $currentUserId = $_SESSION['user_id'] ?? null;
    $out = [];
    foreach ($rows as $post) {
        $out[] = [
            'id' => $post['service_id'],
            'housing_id' => $post['housing_id'],
            'title' => $post['title'],
            'location' => $post['location'],
            'rent' => number_format($post['rent']),
            'description' => $post['description'],
            'property_type' => $post['property_type'],
            'bedrooms' => (int)$post['bedrooms'],
            'bathrooms' => (int)$post['bathrooms'],
            'balconies' => (int)$post['balconies'],
            'size_sqft' => $post['size_sqft'],
            'furnished_status' => $post['furnished_status'],
            'available_from' => $post['available_from'],
            'available_for' => $post['available_for'],
            'negotiable' => (int)$post['negotiable'],
            'khotiyan' => $post['khotiyan'],
            'verification_status' => $post['verification_status'],
            'availability' => $post['availability'] ?: 'available',
            'housing_location' => $post['housing_location'],
            'coordinates' => $post['coordinates'],
            'generalized_location' => $post['generalized_location'],
            'is_owner' => ($currentUserId && $post['user_id'] == $currentUserId),
            'created_at' => $post['created_at']
        ];
    }

    echo json_encode($out);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
