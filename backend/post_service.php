<?php
session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

function ensurePostServiceSchema(PDO $pdo): void {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS services (
            service_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            title VARCHAR(150) NOT NULL,
            description TEXT NOT NULL,
            type ENUM('housing','job','tuition','food') NOT NULL,
            price DECIMAL(10,2) NULL,
            location VARCHAR(100) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (service_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $col = $pdo->query("SHOW COLUMNS FROM services LIKE 'price'")->fetch(PDO::FETCH_ASSOC);
        if (!$col) { $pdo->exec("ALTER TABLE services ADD COLUMN price DECIMAL(10,2) NULL AFTER type"); }
        $col = $pdo->query("SHOW COLUMNS FROM services LIKE 'location'")->fetch(PDO::FETCH_ASSOC);
        if (!$col) { $pdo->exec("ALTER TABLE services ADD COLUMN location VARCHAR(100) NULL AFTER price"); }

        $pdo->exec("CREATE TABLE IF NOT EXISTS jobs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            service_id INT UNSIGNED NOT NULL,
            job_type ENUM('part-time','full-time','freelance','internship') NULL,
            company VARCHAR(150) NULL,
            experience_level ENUM('entry','junior','mid','senior') NULL,
            work_type ENUM('onsite','remote','hybrid') NULL,
            salary_min DECIMAL(10,2) NULL,
            salary_max DECIMAL(10,2) NULL,
            requirements TEXT NULL,
            benefits TEXT NULL,
            application_deadline DATE NULL,
            status ENUM('active','closed') NOT NULL DEFAULT 'active',
            PRIMARY KEY (id),
            UNIQUE KEY uniq_jobs_service_id (service_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Ensure critical columns exist on older schemas
        foreach ([
            ['experience_level', "ADD COLUMN experience_level ENUM('entry','junior','mid','senior') NULL AFTER company"],
            ['work_type', "ADD COLUMN work_type ENUM('onsite','remote','hybrid') NULL AFTER experience_level"],
            ['salary_min', "ADD COLUMN salary_min DECIMAL(10,2) NULL AFTER work_type"],
            ['salary_max', "ADD COLUMN salary_max DECIMAL(10,2) NULL AFTER salary_min"],
            ['requirements', "ADD COLUMN requirements TEXT NULL AFTER salary_max"],
            ['benefits', "ADD COLUMN benefits TEXT NULL AFTER requirements"],
            ['application_deadline', "ADD COLUMN application_deadline DATE NULL AFTER benefits"],
            ['status', "ADD COLUMN status ENUM('active','closed') NOT NULL DEFAULT 'active' AFTER application_deadline"]
        ] as $colDef) {
            $col = $pdo->query("SHOW COLUMNS FROM jobs LIKE '".$colDef[0]."'")->fetch(PDO::FETCH_ASSOC);
            if (!$col) { $pdo->exec("ALTER TABLE jobs ".$colDef[1]); }
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS tuitions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            service_id INT UNSIGNED NOT NULL,
            subject VARCHAR(60) NOT NULL,
            class_level VARCHAR(60) NOT NULL,
            tuition_type ENUM('home','online','center','group') NOT NULL,
            student_count VARCHAR(20) NOT NULL DEFAULT '1',
            schedule VARCHAR(120) NULL,
            gender_preference ENUM('male','female') NULL,
            experience_years INT NULL,
            qualification VARCHAR(150) NULL,
            hourly_rate DECIMAL(10,2) NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_tuitions_service_id (service_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS food_services (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            service_id INT UNSIGNED NOT NULL,
            food_type ENUM('meal','tiffin','laundry','others') NOT NULL DEFAULT 'meal',
            provider_name VARCHAR(100) NOT NULL,
            location VARCHAR(100) NULL,
            available_date DATE NULL,
            price DECIMAL(10,2) NULL,
            description TEXT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_food_service_id (service_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Housing table - match the existing database structure
        $pdo->exec("CREATE TABLE IF NOT EXISTS housing (
            housing_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            service_id INT UNSIGNED NOT NULL,
            property_type ENUM('apartment','room','commercial','mixed') NOT NULL DEFAULT 'apartment',
            size_sqft INT NULL,
            floor_no VARCHAR(20) NULL,
            total_floors INT NULL,
            furnished_status ENUM('furnished','semi-furnished','unfurnished') NOT NULL DEFAULT 'unfurnished',
            parking_spaces INT NOT NULL DEFAULT 0,
            bedrooms INT NOT NULL DEFAULT 0,
            bathrooms INT NOT NULL DEFAULT 0,
            balconies INT NOT NULL DEFAULT 0,
            rent DECIMAL(12,2) NOT NULL DEFAULT 0,
            service_charge DECIMAL(12,2) NOT NULL DEFAULT 0,
            advance_deposit DECIMAL(12,2) NOT NULL DEFAULT 0,
            available_from DATE NULL,
            available_for ENUM('family','bachelor','any') NOT NULL DEFAULT 'any',
            negotiable TINYINT(1) NOT NULL DEFAULT 0,
            property_condition VARCHAR(255) NOT NULL DEFAULT 'N/A',
            verification_doc VARCHAR(100) NULL,
            verification_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
            khotiyan VARCHAR(100) NULL,
            status ENUM('available','pending','occupied') NOT NULL DEFAULT 'available',
            furnished ENUM('furnished','unfurnished') NOT NULL DEFAULT 'unfurnished',
            availability ENUM('available','pending','occupied') NOT NULL DEFAULT 'available',
            location VARCHAR(255) NULL,
            coordinates VARCHAR(100) NULL,
            generalized_location VARCHAR(50) NOT NULL DEFAULT '',
            PRIMARY KEY (housing_id),
            KEY service_id (service_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
        // noop
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid method']);
        exit();
    }

    ensurePostServiceSchema($pdo);

    $userId = (int)$_SESSION['user_id'];
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = $_POST['type'] ?? ($_POST['service_type'] ?? '');
    $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float)$_POST['price'] : null;
    $location = trim($_POST['location'] ?? '');

    if ($title === '' || $description === '' || $type === '') {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO services (user_id, title, description, type, price, location) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$userId, $title, $description, $type, $price, $location ?: null]);
    $serviceId = (int)$pdo->lastInsertId();

    if ($type === 'job') {
        $jobStmt = $pdo->prepare("INSERT INTO jobs (service_id, job_type, company, experience_level, work_type, salary_min, salary_max, requirements, benefits, application_deadline, status) VALUES (?,?,?,?,?,?,?,?,? ,?, 'active')");
        $jobStmt->execute([
            $serviceId,
            $_POST['job_type'] ?? null,
            $_POST['company'] ?? null,
            $_POST['experience_level'] ?? null,
            $_POST['work_type'] ?? null,
            isset($_POST['salary_min']) && $_POST['salary_min'] !== '' ? (float)$_POST['salary_min'] : null,
            isset($_POST['salary_max']) && $_POST['salary_max'] !== '' ? (float)$_POST['salary_max'] : null,
            $_POST['requirements'] ?? null,
            $_POST['benefits'] ?? null,
            $_POST['application_deadline'] ?? null,
        ]);
    } elseif ($type === 'tuition') {
        $tStmt = $pdo->prepare("INSERT INTO tuitions (service_id, subject, class_level, tuition_type, student_count, schedule, gender_preference, experience_years, qualification, hourly_rate) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $tStmt->execute([
            $serviceId,
            $_POST['subject'] ?? ($_POST['tuition_subject'] ?? ''),
            $_POST['class_level'] ?? ($_POST['tuition_class_level'] ?? ''),
            $_POST['tuition_type'] ?? ($_POST['tuition_type'] ?? ''),
            $_POST['student_count'] ?? ($_POST['tuition_student_count'] ?? '1'),
            $_POST['schedule'] ?? ($_POST['tuition_schedule'] ?? null),
            $_POST['gender_preference'] ?? ($_POST['tuition_gender_preference'] ?? null),
            isset($_POST['experience_years']) ? (int)$_POST['experience_years'] : (isset($_POST['tuition_experience_years']) ? (int)$_POST['tuition_experience_years'] : null),
            $_POST['qualification'] ?? ($_POST['tuition_qualification'] ?? null),
            isset($_POST['hourly_rate']) ? (float)$_POST['hourly_rate'] : (isset($_POST['tuition_hourly_rate']) ? (float)$_POST['tuition_hourly_rate'] : null),
        ]);
    } elseif ($type === 'food') {
        $fStmt = $pdo->prepare("INSERT INTO food_services (service_id, food_type, provider_name, location, available_date, price, description) VALUES (?,?,?,?,?,?,?)");
        $fStmt->execute([
            $serviceId,
            $_POST['food_type'] ?? 'meal',
            $_POST['provider_name'] ?? '',
            $location ?: null,
            $_POST['available_date'] ?? null,
            $price,
            $description,
        ]);
    } elseif ($type === 'housing') {
        $hStmt = $pdo->prepare("INSERT INTO housing (
            service_id, property_type, size_sqft, floor_no, total_floors,
            furnished_status, parking_spaces, bedrooms, bathrooms, balconies,
            rent, service_charge, advance_deposit, available_from, available_for,
            negotiable, property_condition, status, furnished, availability,
            location, coordinates, generalized_location
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $hStmt->execute([
            $serviceId,
            $_POST['property_type'] ?? 'apartment',
            isset($_POST['size_sqft']) && $_POST['size_sqft'] !== '' ? (int)$_POST['size_sqft'] : null,
            $_POST['floor_no'] ?? null,
            isset($_POST['total_floors']) && $_POST['total_floors'] !== '' ? (int)$_POST['total_floors'] : null,
            $_POST['furnished_status'] ?? 'unfurnished',
            isset($_POST['parking_spaces']) && $_POST['parking_spaces'] !== '' ? (int)$_POST['parking_spaces'] : 0,
            isset($_POST['bedrooms']) && $_POST['bedrooms'] !== '' ? (int)$_POST['bedrooms'] : 0,
            isset($_POST['bathrooms']) && $_POST['bathrooms'] !== '' ? (int)$_POST['bathrooms'] : 0,
            isset($_POST['balconies']) && $_POST['balconies'] !== '' ? (int)$_POST['balconies'] : 0,
            isset($_POST['rent']) && $_POST['rent'] !== '' ? (float)$_POST['rent'] : 0,
            isset($_POST['service_charge']) && $_POST['service_charge'] !== '' ? (float)$_POST['service_charge'] : 0,
            isset($_POST['advance_deposit']) && $_POST['advance_deposit'] !== '' ? (float)$_POST['advance_deposit'] : 0,
            $_POST['available_from'] ?? null,
            $_POST['available_for'] ?? 'any',
            isset($_POST['negotiable']) && $_POST['negotiable'] == '1' ? 1 : 0,
            $_POST['property_condition'] ?? 'N/A',
            'available',
            $_POST['furnished_status'] ?? 'unfurnished',
            'available',
            $location ?: null,
            $_POST['coordinates'] ?? null,
            $_POST['generalized_location'] ?? ''
        ]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'service_id' => $serviceId]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
