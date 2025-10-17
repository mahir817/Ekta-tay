<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once "../backend/db.php";
@include __DIR__ . "/../backend/session.php";

// Guard: must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login Page/login.html");
    exit();
}

// Ensure schema exists for posting (defensive)
function ensurePostServiceSchema(PDO $pdo): void {
    try {
        // base services table
        $pdo->exec("CREATE TABLE IF NOT EXISTS services (
            service_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            title VARCHAR(150) NOT NULL,
            description TEXT NOT NULL,
            type ENUM('housing','job','tuition','food') NOT NULL,
            price DECIMAL(10,2) NULL,
            location VARCHAR(100) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (service_id),
            KEY idx_services_user_id (user_id),
            KEY idx_services_type_created (type, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // add columns defensively if old schema
        $col = $pdo->query("SHOW COLUMNS FROM services LIKE 'price'")->fetch(PDO::FETCH_ASSOC);
        if (!$col) { $pdo->exec("ALTER TABLE services ADD COLUMN price DECIMAL(10,2) NULL AFTER type"); }
        $col = $pdo->query("SHOW COLUMNS FROM services LIKE 'location'")->fetch(PDO::FETCH_ASSOC);
        if (!$col) { $pdo->exec("ALTER TABLE services ADD COLUMN location VARCHAR(100) NULL AFTER price"); }

        // housing table
        $pdo->exec("CREATE TABLE IF NOT EXISTS housing (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            service_id INT UNSIGNED NOT NULL,
            property_type ENUM('apartment','room','commercial','mixed') NOT NULL DEFAULT 'apartment',
            size_sqft INT NULL,
            floor_no VARCHAR(20) NULL,
            total_floors INT NULL,
            furnished_status ENUM('unfurnished','semi-furnished','furnished') NOT NULL DEFAULT 'unfurnished',
            parking_spaces INT NOT NULL DEFAULT 0,
            bedrooms INT NOT NULL DEFAULT 0,
            bathrooms INT NOT NULL DEFAULT 0,
            balconies INT NOT NULL DEFAULT 0,
            rent DECIMAL(10,2) NOT NULL DEFAULT 0,
            service_charge DECIMAL(10,2) NOT NULL DEFAULT 0,
            advance_deposit DECIMAL(10,2) NOT NULL DEFAULT 0,
            available_from DATE NULL,
            available_for ENUM('any','family','bachelor') NOT NULL DEFAULT 'any',
            negotiable TINYINT(1) NOT NULL DEFAULT 0,
            property_condition VARCHAR(100) NOT NULL DEFAULT 'N/A',
            status ENUM('available','unavailable') NOT NULL DEFAULT 'available',
            furnished ENUM('unfurnished','furnished') NOT NULL DEFAULT 'unfurnished',
            location VARCHAR(255) NULL,
            coordinates VARCHAR(100) NULL,
            generalized_location VARCHAR(50) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            UNIQUE KEY uniq_housing_service_id (service_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // defensive columns for housing
        $col = $pdo->query("SHOW COLUMNS FROM housing LIKE 'location'")->fetch(PDO::FETCH_ASSOC);
        if (!$col) { $pdo->exec("ALTER TABLE housing ADD COLUMN location VARCHAR(255) NULL"); }
        $col = $pdo->query("SHOW COLUMNS FROM housing LIKE 'coordinates'")->fetch(PDO::FETCH_ASSOC);
        if (!$col) { $pdo->exec("ALTER TABLE housing ADD COLUMN coordinates VARCHAR(100) NULL"); }
        $col = $pdo->query("SHOW COLUMNS FROM housing LIKE 'generalized_location'")->fetch(PDO::FETCH_ASSOC);
        if (!$col) { $pdo->exec("ALTER TABLE housing ADD COLUMN generalized_location VARCHAR(50) NOT NULL DEFAULT ''"); }

        // jobs table
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

        // tuitions table
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

        // food_services table
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
    } catch (Throwable $e) {
        // best-effort; do not block submission, errors will surface in insert try/catch
    }
}

$user_id = $_SESSION['user_id'];

// Get user information
$userStmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// Get user capabilities
$capStmt = $pdo->prepare("
    SELECT c.capability_name 
    FROM capabilities c 
    JOIN user_capabilities uc ON c.id = uc.capability_id 
    WHERE uc.user_id = ?
");
$capStmt->execute([$user_id]);
$capabilities = $capStmt->fetchAll(PDO::FETCH_COLUMN);

// Capability mapping for display
$capabilityMap = [
    'find_room' => 'Housing',
    'offer_room' => 'Housing',
    'find_job' => 'Jobs',
    'post_job' => 'Jobs',
    'find_tutor' => 'Tutors',
    'offer_tuition' => 'Tutors',
    'food_service' => 'Services',
    'expense_tracking' => 'Expenses'
];

// Get available capabilities for navigation
$availableCapabilities = array_unique(array_map(function($cap) use ($capabilityMap) {
    return $capabilityMap[$cap] ?? ucfirst(str_replace('_', ' ', $cap));
}, $capabilities));

// state for post success view
$posted = false;
$postedServiceId = null;
$postedSummary = [];
$errorMessage = '';

// Handle form submission using PDO and correct schema (services -> subtype)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceType = $_POST['service_type'] ?? '';
    $userId = (int)$_SESSION['user_id'];

    // Common fields for services table
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float)$_POST['price'] : null;
    $location = trim($_POST['location'] ?? '');

    if (!$serviceType) {
        $errorMessage = 'Please select a service type.';
    }

    if (!$errorMessage && ($title === '' || $description === '')) {
        $errorMessage = 'Title and Description are required.';
    }

    if (!$errorMessage && $serviceType === 'housing') {
        $rentValue = $_POST['rent'] ?? '';
        if ($rentValue === '' || (float)$rentValue <= 0) {
            $errorMessage = 'Rent is required and must be greater than 0 for housing posts.';
        }
    }

    if (!$errorMessage && $serviceType === 'food') {
        $providerName = trim($_POST['provider_name'] ?? '');
        if ($providerName === '' || $price === null || $location === '') {
            $errorMessage = 'Provider name, price, and location are required for food services.';
        }
    }

    if (!$errorMessage) {
        // best-effort ensure schema
        ensurePostServiceSchema($pdo);

        try {
            $pdo->beginTransaction();

            // Insert into services (title/description/price/location)
            $serviceStmt = $pdo->prepare(
                "INSERT INTO services (user_id, title, description, type, price, location) VALUES (:user_id, :title, :description, :type, :price, :location)"
            );
            $serviceStmt->execute([
                ':user_id' => $userId,
                ':title' => $title,
                ':description' => $description,
                ':type' => $serviceType,
                ':price' => $price,
                ':location' => $location !== '' ? $location : null,
            ]);
            $serviceId = (int)$pdo->lastInsertId();

            if ($serviceType === 'housing') {
                $housingStmt = $pdo->prepare(
                    "INSERT INTO housing (
                        service_id, property_type, size_sqft, floor_no, total_floors,
                        furnished_status, parking_spaces, bedrooms, bathrooms, balconies,
                        rent, service_charge, advance_deposit, available_from, available_for,
                        negotiable, property_condition, status, location, coordinates, generalized_location
                    ) VALUES (
                        :service_id, :property_type, :size_sqft, :floor_no, :total_floors,
                        :furnished_status, :parking_spaces, :bedrooms, :bathrooms, :balconies,
                        :rent, :service_charge, :advance_deposit, :available_from, :available_for,
                        :negotiable, :property_condition, 'available', :location, :coordinates, :generalized_location
                    )"
                );

                $housingData = [
                    ':service_id' => $serviceId,
                    ':property_type' => $_POST['property_type'] ?? 'apartment',
                    ':size_sqft' => $_POST['size_sqft'] !== '' ? (int)$_POST['size_sqft'] : null,
                    ':floor_no' => $_POST['floor_no'] ?? null,
                    ':total_floors' => $_POST['total_floors'] !== '' ? (int)$_POST['total_floors'] : null,
                    ':furnished_status' => $_POST['furnished_status'] ?? 'unfurnished',
                    ':parking_spaces' => $_POST['parking_spaces'] !== '' ? (int)$_POST['parking_spaces'] : 0,
                    ':bedrooms' => $_POST['bedrooms'] !== '' ? (int)$_POST['bedrooms'] : 0,
                    ':bathrooms' => $_POST['bathrooms'] !== '' ? (int)$_POST['bathrooms'] : 0,
                    ':balconies' => $_POST['balconies'] !== '' ? (int)$_POST['balconies'] : 0,
                    ':rent' => isset($_POST['rent']) ? (float)$_POST['rent'] : 0,
                    ':service_charge' => $_POST['service_charge'] !== '' ? (float)$_POST['service_charge'] : 0,
                    ':advance_deposit' => $_POST['advance_deposit'] !== '' ? (float)$_POST['advance_deposit'] : 0,
                    ':available_from' => $_POST['available_from'] ?? null,
                    ':available_for' => $_POST['available_for'] ?? 'any',
                    ':negotiable' => isset($_POST['negotiable']) && $_POST['negotiable'] == '1' ? 1 : 0,
                    ':property_condition' => $_POST['property_condition'] ?? 'N/A',
                    ':location' => $location !== '' ? $location : null,
                    ':coordinates' => trim($_POST['coordinates'] ?? ''),
                    ':generalized_location' => $_POST['generalized_location'] ?? ''
                ];
                $housingStmt->execute($housingData);
            } elseif ($serviceType === 'job') {
                $jobStmt = $pdo->prepare(
                    "INSERT INTO jobs (
                        service_id, job_type, company, experience_level, work_type,
                        salary_min, salary_max, requirements, benefits, application_deadline,
                        status
                    ) VALUES (
                        :service_id, :job_type, :company, :experience_level, :work_type,
                        :salary_min, :salary_max, :requirements, :benefits, :application_deadline,
                        'active'
                    )"
                );
                $jobStmt->execute([
                    ':service_id' => $serviceId,
                    ':job_type' => $_POST['job_type'] ?? null,
                    ':company' => $_POST['company'] ?? null,
                    ':experience_level' => $_POST['experience_level'] ?? null,
                    ':work_type' => $_POST['work_type'] ?? null,
                    ':salary_min' => $_POST['salary_min'] !== '' ? (float)$_POST['salary_min'] : null,
                    ':salary_max' => $_POST['salary_max'] !== '' ? (float)$_POST['salary_max'] : null,
                    ':requirements' => $_POST['requirements'] ?? null,
                    ':benefits' => $_POST['benefits'] ?? null,
                    ':application_deadline' => $_POST['application_deadline'] !== '' ? $_POST['application_deadline'] : null,
                ]);
            } elseif ($serviceType === 'tuition') {
                $tuitionStmt = $pdo->prepare(
                    "INSERT INTO tuitions (
                        service_id, subject, class_level, tuition_type, student_count, schedule,
                        gender_preference, experience_years, qualification, hourly_rate
                    ) VALUES (
                        :service_id, :subject, :class_level, :tuition_type, :student_count, :schedule,
                        :gender_preference, :experience_years, :qualification, :hourly_rate
                    )"
                );
                $tuitionStmt->execute([
                    ':service_id' => $serviceId,
                    ':subject' => $_POST['tuition_subject'] ?? '',
                    ':class_level' => $_POST['tuition_class_level'] ?? '',
                    ':tuition_type' => $_POST['tuition_type'] ?? '',
                    ':student_count' => $_POST['tuition_student_count'] ?? '1',
                    ':schedule' => $_POST['tuition_schedule'] ?? null,
                    ':gender_preference' => $_POST['tuition_gender_preference'] ?? null,
                    ':experience_years' => $_POST['tuition_experience_years'] !== '' ? (int)$_POST['tuition_experience_years'] : null,
                    ':qualification' => $_POST['tuition_qualification'] ?? null,
                    ':hourly_rate' => $_POST['tuition_hourly_rate'] !== '' ? (float)$_POST['tuition_hourly_rate'] : null,
                ]);
            } elseif ($serviceType === 'food') {
                $foodStmt = $pdo->prepare(
                    "INSERT INTO food_services (
                        service_id, food_type, provider_name, location, available_date, price, description
                    ) VALUES (
                        :service_id, :food_type, :provider_name, :location, :available_date, :price, :description
                    )"
                );
                $foodStmt->execute([
                    ':service_id' => $serviceId,
                    ':food_type' => $_POST['food_type'] ?? 'meal',
                    ':provider_name' => $_POST['provider_name'] ?? '',
                    ':location' => $location,
                    ':available_date' => $_POST['available_date'] ?? null,
                    ':price' => $price,
                    ':description' => $description,
                ]);
            }

            $pdo->commit();
            
            // Log activity
            require_once "../backend/log_activity.php";
            $activityTitle = "Posted new " . $serviceType;
            $activityDescription = "Posted: " . $title;
            logActivity($userId, $serviceType . '_post', $activityTitle, $activityDescription, $serviceId);
            
            $posted = true;
            $postedServiceId = $serviceId;
            $postedSummary = [
                'service_id' => $serviceId,
                'type' => $serviceType,
                'title' => $title,
                'description' => $description,
                'price' => $price,
                'location' => $location,
            ];
            if ($serviceType === 'housing') {
                $postedSummary['housing'] = [
                    'property_type' => $_POST['property_type'] ?? null,
                    'size_sqft' => $_POST['size_sqft'] ?? null,
                    'floor_no' => $_POST['floor_no'] ?? null,
                    'total_floors' => $_POST['total_floors'] ?? null,
                    'furnished_status' => $_POST['furnished_status'] ?? null,
                    'parking_spaces' => $_POST['parking_spaces'] ?? null,
                    'bedrooms' => $_POST['bedrooms'] ?? null,
                    'bathrooms' => $_POST['bathrooms'] ?? null,
                    'balconies' => $_POST['balconies'] ?? null,
                    'rent' => $_POST['rent'] ?? null,
                    'service_charge' => $_POST['service_charge'] ?? null,
                    'advance_deposit' => $_POST['advance_deposit'] ?? null,
                    'available_from' => $_POST['available_from'] ?? null,
                    'available_for' => $_POST['available_for'] ?? null,
                    'negotiable' => isset($_POST['negotiable']) ? 'Yes' : 'No',
                    'property_condition' => $_POST['property_condition'] ?? null,
                    'furnished' => $_POST['furnished'] ?? null,
                    'coordinates' => $_POST['coordinates'] ?? null,
                    'generalized_location' => $_POST['generalized_location'] ?? null,
                ];
            } elseif ($serviceType === 'job') {
                $postedSummary['job'] = [
                    'job_type' => $_POST['job_type'] ?? null,
                    'company' => $_POST['company'] ?? null,
                    'experience_level' => $_POST['experience_level'] ?? null,
                    'work_type' => $_POST['work_type'] ?? null,
                    'salary_min' => $_POST['salary_min'] ?? null,
                    'salary_max' => $_POST['salary_max'] ?? null,
                    'requirements' => $_POST['requirements'] ?? null,
                    'benefits' => $_POST['benefits'] ?? null,
                    'application_deadline' => $_POST['application_deadline'] ?? null,
                ];
            } elseif ($serviceType === 'tuition') {
                $postedSummary['tuition'] = [
                    'subject' => $_POST['tuition_subject'] ?? null,
                    'class_level' => $_POST['tuition_class_level'] ?? null,
                    'tuition_type' => $_POST['tuition_type'] ?? null,
                    'student_count' => $_POST['tuition_student_count'] ?? null,
                    'schedule' => $_POST['tuition_schedule'] ?? null,
                    'gender_preference' => $_POST['tuition_gender_preference'] ?? null,
                    'experience_years' => $_POST['tuition_experience_years'] ?? null,
                    'qualification' => $_POST['tuition_qualification'] ?? null,
                    'hourly_rate' => $_POST['tuition_hourly_rate'] ?? null,
                ];
            } elseif ($serviceType === 'food') {
                $postedSummary['food'] = [
                    'provider_name' => $_POST['provider_name'] ?? null,
                    'food_type' => $_POST['food_type'] ?? null,
                    'available_date' => $_POST['available_date'] ?? null,
                ];
            }

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorMessage = 'Failed to post service: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Service - Ekta Tay</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="post_service.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="../images/logo.png" alt="Ektate Logo" class="logo-img" />
                    <div class="logo-text">Ekta-tay</div>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="../Dashboard/dashboard.php" class="nav-link">
                        <i class="nav-icon fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                
                <?php if (in_array('Housing', $availableCapabilities)): ?>
                <li class="nav-item">
                    <a href="../Modules/Housing/housing.php" class="nav-link">
                        <i class="nav-icon fas fa-home"></i>
                        Housing
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (in_array('Jobs', $availableCapabilities)): ?>
                <li class="nav-item">
                    <a href="../Modules/Jobs/jobs_listings.php" class="nav-link">
                        <i class="nav-icon fas fa-briefcase"></i>
                        Jobs
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (in_array('Tutors', $availableCapabilities)): ?>
                <li class="nav-item">
                    <a href="../Modules/Tuitions/tuitions_listings.php" class="nav-link">
                        <i class="nav-icon fas fa-graduation-cap"></i>
                        Tuition
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (in_array('Services', $availableCapabilities)): ?>
                <li class="nav-item">
                    <a href="../Modules/Services/services_listings.php" class="nav-link">
                        <i class="nav-icon fas fa-wrench"></i>
                        Services
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a href="post_service.php" class="nav-link active">
                        <i class="nav-icon fas fa-plus-circle"></i>
                        Post Service
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="../Profile page/profile.php" class="nav-link">
                        <i class="nav-icon fas fa-cog"></i>
                        Manage
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-question-circle"></i>
                        Help
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Mobile Toggle Button -->
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Header -->
            <header class="dashboard-header">
                <h1 class="dashboard-title">Post a Service</h1>
                <div class="user-profile" onclick="toggleDropdown()">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name">
                        <?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?>
                        </span>
                        <div class="user-dropdown">
                            <div class="dropdown-menu" id="userDropdown">
                                <div class="dropdown-item" onclick="window.location.href='../Profile page/profile.php'">
                                    <i class="fas fa-user"></i>
                                    <span>Profile</span>
                                </div>
                                <div class="dropdown-item">
                                    <i class="fas fa-cog"></i>
                                    <span>Settings</span>
                                </div>
                                <div class="dropdown-divider"></div>
                                <div class="dropdown-item logout-item" onclick="logout()">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content-section">
                
                
<?php if (!empty($errorMessage)): ?>
                <div class="glass-card" style="padding:12px; background: rgba(255,0,0,0.1); border-left: 4px solid #f44336; color: #fff;">
                    <strong>Error:</strong> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
<?php endif; ?>
<?php if ($posted): ?>
                <div class="glass-card" style="padding: 20px;">
                    <h3 style="margin-top:0;">Service Posted Successfully</h3>
                    <p style="opacity:0.9;">Your service has been created with ID #<?php echo (int)$postedServiceId; ?>.</p>
                    <div class="glass-card" style="padding:10px; background: rgba(33,150,243,0.12); border-left: 4px solid #2196F3; color:#fff; margin: 10px 0 0 0;">
                        You can create another post now or post duplicates if you want.
                    </div>

                    <div class="summary-block" style="margin-top:15px;">
                        <h4>General</h4>
                        <ul style="list-style: none; padding: 0;">
                            <li><strong>Type:</strong> <?php echo htmlspecialchars(ucfirst($postedSummary['type'])); ?></li>
                            <li><strong>Title:</strong> <?php echo htmlspecialchars($postedSummary['title']); ?></li>
                            <li><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($postedSummary['description'])); ?></li>
                            <?php if (!is_null($postedSummary['price'])): ?>
                            <li><strong>Price:</strong> <?php echo number_format((float)$postedSummary['price'], 2); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($postedSummary['location'])): ?>
                            <li><strong>Location:</strong> <?php echo htmlspecialchars($postedSummary['location']); ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <?php if ($postedSummary['type'] === 'housing' && !empty($postedSummary['housing'])): ?>
                    <div class="summary-block" style="margin-top:15px;">
                        <h4>Housing Details</h4>
                        <ul style="list-style:none; padding:0;">
                            <li><strong>Property Type:</strong> <?php echo htmlspecialchars($postedSummary['housing']['property_type'] ?? ''); ?></li>
                            <li><strong>Size (sqft):</strong> <?php echo htmlspecialchars((string)($postedSummary['housing']['size_sqft'] ?? '')); ?></li>
                            <li><strong>Floor:</strong> <?php echo htmlspecialchars($postedSummary['housing']['floor_no'] ?? ''); ?></li>
                            <li><strong>Total Floors:</strong> <?php echo htmlspecialchars((string)($postedSummary['housing']['total_floors'] ?? '')); ?></li>
                            <li><strong>Furnished Status:</strong> <?php echo htmlspecialchars($postedSummary['housing']['furnished_status'] ?? ''); ?></li>
                            <li><strong>Parking:</strong> <?php echo htmlspecialchars((string)($postedSummary['housing']['parking_spaces'] ?? '')); ?></li>
                            <li><strong>Bedrooms:</strong> <?php echo htmlspecialchars((string)($postedSummary['housing']['bedrooms'] ?? '')); ?></li>
                            <li><strong>Bathrooms:</strong> <?php echo htmlspecialchars((string)($postedSummary['housing']['bathrooms'] ?? '')); ?></li>
                            <li><strong>Balconies:</strong> <?php echo htmlspecialchars((string)($postedSummary['housing']['balconies'] ?? '')); ?></li>
                            <li><strong>Rent:</strong> <?php echo htmlspecialchars((string)($postedSummary['housing']['rent'] ?? '')); ?></li>
                            <li><strong>Service Charge:</strong> <?php echo htmlspecialchars((string)($postedSummary['housing']['service_charge'] ?? '')); ?></li>
                            <li><strong>Advance Deposit:</strong> <?php echo htmlspecialchars((string)($postedSummary['housing']['advance_deposit'] ?? '')); ?></li>
                            <li><strong>Available From:</strong> <?php echo htmlspecialchars($postedSummary['housing']['available_from'] ?? ''); ?></li>
                            <li><strong>Available For:</strong> <?php echo htmlspecialchars($postedSummary['housing']['available_for'] ?? ''); ?></li>
                            <li><strong>Negotiable:</strong> <?php echo htmlspecialchars($postedSummary['housing']['negotiable'] ?? 'No'); ?></li>
                            <li><strong>Condition:</strong> <?php echo htmlspecialchars($postedSummary['housing']['property_condition'] ?? ''); ?></li>
                            <li><strong>Furnished:</strong> <?php echo htmlspecialchars($postedSummary['housing']['furnished'] ?? ''); ?></li>
                            <?php if (!empty($postedSummary['housing']['coordinates'])): ?>
                            <li><strong>Coordinates:</strong> <?php echo htmlspecialchars($postedSummary['housing']['coordinates']); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($postedSummary['housing']['generalized_location'])): ?>
                            <li><strong>Area:</strong> <?php echo htmlspecialchars($postedSummary['housing']['generalized_location']); ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if ($postedSummary['type'] === 'job' && !empty($postedSummary['job'])): ?>
                    <div class="summary-block" style="margin-top:15px;">
                        <h4>Job Details</h4>
                        <ul style="list-style:none; padding:0;">
                            <li><strong>Type:</strong> <?php echo htmlspecialchars($postedSummary['job']['job_type'] ?? ''); ?></li>
                            <li><strong>Company:</strong> <?php echo htmlspecialchars($postedSummary['job']['company'] ?? ''); ?></li>
                            <li><strong>Experience:</strong> <?php echo htmlspecialchars($postedSummary['job']['experience_level'] ?? ''); ?></li>
                            <li><strong>Work Type:</strong> <?php echo htmlspecialchars($postedSummary['job']['work_type'] ?? ''); ?></li>
                            <li><strong>Salary Range:</strong> <?php echo htmlspecialchars(($postedSummary['job']['salary_min'] ?? '') . ' - ' . ($postedSummary['job']['salary_max'] ?? '')); ?></li>
                            <?php if (!empty($postedSummary['job']['requirements'])): ?>
                            <li><strong>Requirements:</strong> <?php echo nl2br(htmlspecialchars($postedSummary['job']['requirements'])); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($postedSummary['job']['benefits'])): ?>
                            <li><strong>Benefits:</strong> <?php echo nl2br(htmlspecialchars($postedSummary['job']['benefits'])); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($postedSummary['job']['application_deadline'])): ?>
                            <li><strong>Deadline:</strong> <?php echo htmlspecialchars($postedSummary['job']['application_deadline']); ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if ($postedSummary['type'] === 'tuition' && !empty($postedSummary['tuition'])): ?>
                    <div class="summary-block" style="margin-top:15px;">
                        <h4>Tuition Details</h4>
                        <ul style="list-style:none; padding:0;">
                            <li><strong>Subject:</strong> <?php echo htmlspecialchars($postedSummary['tuition']['subject'] ?? ''); ?></li>
                            <li><strong>Class Level:</strong> <?php echo htmlspecialchars($postedSummary['tuition']['class_level'] ?? ''); ?></li>
                            <li><strong>Type:</strong> <?php echo htmlspecialchars($postedSummary['tuition']['tuition_type'] ?? ''); ?></li>
                            <li><strong>Students:</strong> <?php echo htmlspecialchars($postedSummary['tuition']['student_count'] ?? ''); ?></li>
                            <?php if (!empty($postedSummary['tuition']['schedule'])): ?>
                            <li><strong>Schedule:</strong> <?php echo htmlspecialchars($postedSummary['tuition']['schedule']); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($postedSummary['tuition']['gender_preference'])): ?>
                            <li><strong>Gender Preference:</strong> <?php echo htmlspecialchars($postedSummary['tuition']['gender_preference']); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($postedSummary['tuition']['experience_years'])): ?>
                            <li><strong>Experience (years):</strong> <?php echo htmlspecialchars((string)$postedSummary['tuition']['experience_years']); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($postedSummary['tuition']['qualification'])): ?>
                            <li><strong>Qualification:</strong> <?php echo htmlspecialchars($postedSummary['tuition']['qualification']); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($postedSummary['tuition']['hourly_rate'])): ?>
                            <li><strong>Hourly Rate:</strong> <?php echo htmlspecialchars((string)$postedSummary['tuition']['hourly_rate']); ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if ($postedSummary['type'] === 'food' && !empty($postedSummary['food'])): ?>
                    <div class="summary-block" style="margin-top:15px;">
                        <h4>Food Service Details</h4>
                        <ul style="list-style:none; padding:0;">
                            <li><strong>Provider:</strong> <?php echo htmlspecialchars($postedSummary['food']['provider_name'] ?? ''); ?></li>
                            <li><strong>Type:</strong> <?php echo htmlspecialchars($postedSummary['food']['food_type'] ?? ''); ?></li>
                            <?php if (!empty($postedSummary['food']['available_date'])): ?>
                            <li><strong>Available Date:</strong> <?php echo htmlspecialchars($postedSummary['food']['available_date']); ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <div style="margin-top: 20px; display:flex; gap:10px;">
                        <a href="../Dashboard/dashboard.php" class="btn" style="text-decoration:none; padding:10px 14px; background:#4CAF50; color:#fff; border-radius:8px;">Return to Dashboard</a>
                        <a href="post_service.php" class="btn" style="text-decoration:none; padding:10px 14px; background:#3f51b5; color:#fff; border-radius:8px;">Post Another</a>
                        <a href="../Modules/Services/service_details.php?id=<?php echo (int)$postedServiceId; ?>" class="btn" style="text-decoration:none; padding:10px 14px; background:#009688; color:#fff; border-radius:8px;">View Details</a>
                        <?php
                        $listUrl = '';
                        if ($postedSummary['type'] === 'housing') { $listUrl = '../Modules/Housing/housing.php'; }
                        if ($postedSummary['type'] === 'job') { $listUrl = '../Modules/Jobs/jobs_listings.php'; }
                        if ($postedSummary['type'] === 'food') { $listUrl = '../Modules/Services/services_listings.php'; }
                        if ($postedSummary['type'] === 'tuition') { $listUrl = '../Modules/Jobs/jobs_listings.php?tab=tuition'; }
                        if ($listUrl) {
                            echo '<a href="' . htmlspecialchars($listUrl) . '" class="btn" style="text-decoration:none; padding:10px 14px; background:#ff9800; color:#fff; border-radius:8px;">View in Listings</a>'; 
                        }
                        ?>
                    </div>
                </div>
<?php else: ?>
                <form method="POST" action="" class="glass-card">
                    <label>Select Service Type:</label>
                    <select name="service_type" id="service_type" required>
                        <option value="">-- Choose --</option>
                        <option value="housing">Housing</option>
                        <option value="job">Job</option>
                        <option value="tuition">Tuition</option>
                        <option value="food">Food Service</option>
                    </select>

                    <h3>General Details</h3>
                    Title: <input type="text" name="title" placeholder="Enter a clear title" required>
                    Description: <textarea name="description" placeholder="Describe your service" required></textarea>
                    Price (optional): <input type="number" step="0.01" name="price" placeholder="e.g., 32000">
                    Location: <input type="text" name="location" placeholder="e.g., Mirpur 10, Dhaka">

                    <!-- Housing Form -->
                    <div id="housingForm" style="display:none;">
                        <h3>Housing Details</h3>
                        Property Type:
                        <select name="property_type">
                            <option value="apartment">Apartment</option>
                            <option value="room">Room</option>
                            <option value="commercial">Commercial</option>
                            <option value="mixed">Mixed</option>
                        </select><br>
                        Size (sqft): <input type="number" name="size_sqft"><br>
                        Floor No: <input type="text" name="floor_no"><br>
                        Total Floors: <input type="number" name="total_floors"><br>
                        Furnished Status:
                        <select name="furnished_status">
                            <option value="unfurnished">Unfurnished</option>
                            <option value="semi-furnished">Semi-furnished</option>
                            <option value="furnished">Furnished</option>
                        </select><br>
                        Parking Spaces: <input type="number" name="parking_spaces" value="0"><br>
                        Bedrooms: <input type="number" name="bedrooms" value="0"><br>
                        Bathrooms: <input type="number" name="bathrooms" value="0"><br>
                        Balconies: <input type="number" name="balconies" value="0"><br>
                        Rent: <input type="number" name="rent" step="0.01"><br>
                        Service Charge: <input type="number" name="service_charge" value="0"><br>
                        Advance Deposit: <input type="number" name="advance_deposit" value="0"><br>
                        Available From: <input type="date" name="available_from"><br>
                        Available For:
                        <select name="available_for">
                            <option value="any">Any</option>
                            <option value="family">Family</option>
                            <option value="bachelor">Bachelor</option>
                        </select><br>
                        Negotiable: <input type="checkbox" name="negotiable" value="1"><br>
                        Condition: <input type="text" name="property_condition"><br>
                        Furnished:
                        <select name="furnished">
                            <option value="unfurnished">Unfurnished</option>
                            <option value="furnished">Furnished</option>
                        </select><br>
                        Coordinates (lat,lng): <input type="text" name="coordinates" placeholder="e.g., 23.8060,90.3683"><br>
                        Generalized Location:
                        <select name="generalized_location">
                            <option value="">-- Select Area --</option>
                            <option value="Dhaka North">Dhaka North</option>
                            <option value="Dhaka South">Dhaka South</option>
                            <option value="Dhaka East">Dhaka East</option>
                            <option value="Dhaka West">Dhaka West</option>
                        </select><br>
                    </div>

                    <!-- Job Form -->
                    <div id="jobForm" style="display:none;">
                        <h3>Job Details</h3>
                        Type: <select name="job_type">
                            <option value="part-time">Part-time</option>
                            <option value="full-time">Full-time</option>
                            <option value="freelance">Freelance</option>
                            <option value="internship">Internship</option>
                        </select><br>
                        Company: <input type="text" name="company"><br>
                        Experience Level:
                        <select name="experience_level">
                            <option value="entry">Entry</option>
                            <option value="junior">Junior</option>
                            <option value="mid">Mid</option>
                            <option value="senior">Senior</option>
                        </select><br>
                        Work Type:
                        <select name="work_type">
                            <option value="onsite">Onsite</option>
                            <option value="remote">Remote</option>
                            <option value="hybrid">Hybrid</option>
                        </select><br>
                        Salary Range (BDT):
                        <div style="display: flex; gap: 10px; align-items: center; margin: 8px 0 15px 0;">
                            <input type="number" name="salary_min" placeholder="Min salary" style="flex: 1; padding: 10px; border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 8px; background: rgba(255, 255, 255, 0.9); color: #333;">
                            <span style="color: white;">to</span>
                            <input type="number" name="salary_max" placeholder="Max salary" style="flex: 1; padding: 10px; border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 8px; background: rgba(255, 255, 255, 0.9); color: #333;">
                        </div>
                        Job Requirements:
                        <textarea name="requirements" placeholder="List the required skills, qualifications, and experience"></textarea><br>
                        Benefits:
                        <textarea name="benefits" placeholder="List benefits like health insurance, flexible hours, etc."></textarea><br>
                        Application Deadline:
                        <input type="date" name="application_deadline"><br>
                    </div>

                    <!-- Tuition Form -->
                    <div id="tuitionForm" style="display:none;">
                        <h3>Tuition Details</h3>
                        Subject: <select name="tuition_subject">
                            <option value="">-- Select Subject --</option>
                            <option value="mathematics">Mathematics</option>
                            <option value="physics">Physics</option>
                            <option value="chemistry">Chemistry</option>
                            <option value="biology">Biology</option>
                            <option value="english">English</option>
                            <option value="bangla">Bangla</option>
                            <option value="ict">ICT</option>
                            <option value="accounting">Accounting</option>
                            <option value="economics">Economics</option>
                            <option value="business_studies">Business Studies</option>
                        </select><br>
                        Class Level: <select name="tuition_class_level">
                            <option value="">-- Select Level --</option>
                            <option value="class-1-5">Class 1-5</option>
                            <option value="class-6-8">Class 6-8</option>
                            <option value="class-9-10">Class 9-10</option>
                            <option value="class-11-12">Class 11-12</option>
                            <option value="university">University</option>
                        </select><br>
                        Tuition Type: <select name="tuition_type">
                            <option value="">-- Select Type --</option>
                            <option value="home">Home</option>
                            <option value="online">Online</option>
                            <option value="center">Center</option>
                            <option value="group">Group</option>
                        </select><br>
                        Student Count: <select name="tuition_student_count">
                            <option value="1">1 Student</option>
                            <option value="2-3">2-3 Students</option>
                            <option value="4-6">4-6 Students</option>
                            <option value="6+">6+ Students</option>
                        </select><br>
                        Schedule: <input type="text" name="tuition_schedule" placeholder="e.g., 3 days/week, evening"><br>
                        Gender Preference: <select name="tuition_gender_preference">
                            <option value="">No Preference</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select><br>
                        Experience (Years): <input type="number" name="tuition_experience_years" placeholder="e.g., 3"><br>
                        Qualification: <input type="text" name="tuition_qualification" placeholder="e.g., BSc in Mathematics"><br>
                        Hourly Rate: <input type="number" step="0.01" name="tuition_hourly_rate" placeholder="e.g., 500"><br>
                    </div>

                    <!-- Food Form -->
                    <div id="foodForm" style="display:none;">
                        <h3>Food Service Details</h3>
                        Provider Name: <input type="text" name="provider_name"><br>
                        Type: <select name="food_type">
                            <option value="meal">Meal</option>
                            <option value="tiffin">Tiffin</option>
                            <option value="laundry">Laundry</option>
                            <option value="others">Others</option>
                        </select><br>
                        Available Date: <input type="date" name="available_date"><br>
                        <!-- Price, Description, Location taken from General Details -->
                    </div>

                    <button type="submit">Post Service</button>
                </form>
<?php endif; ?>
            </div>
        </main>
    </div>

    <script src="post_service.js"></script>
    <script>
    // Additional debugging
    document.addEventListener('DOMContentLoaded', function() {
        console.log("=== POST SERVICE DEBUG ===");
        
        const form = document.querySelector('form');
        const serviceType = document.getElementById('service_type');
        const submitBtn = document.querySelector('button[type="submit"]');
        
        console.log("Form found:", !!form);
        console.log("Service type found:", !!serviceType);
        console.log("Submit button found:", !!submitBtn);
        
        if (form) {
            console.log("Form method:", form.method);
            console.log("Form action:", form.action);
            
            form.addEventListener('submit', function(e) {
                console.log("FORM SUBMIT EVENT FIRED!");
                console.log("Service type value:", serviceType ? serviceType.value : 'NOT FOUND');
                
                // Check if housing form is visible
                const housingForm = document.getElementById('housingForm');
                if (housingForm) {
                    console.log("Housing form display:", housingForm.style.display);
                }
                
                // Log all form data
                const formData = new FormData(this);
                console.log("Form data being submitted:");
                for (let [key, value] of formData.entries()) {
                    console.log(`  ${key}: ${value}`);
                }
            });
        }
        
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                console.log("SUBMIT BUTTON CLICKED!");
            });
        }
    });
    </script>
    <script>
    // Toggle user dropdown
    function toggleDropdown() {
        const dropdown = document.getElementById('userDropdown');
        dropdown.classList.toggle('show');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('userDropdown');
        const userProfile = document.querySelector('.user-profile');
        
        if (!userProfile.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });

    // Logout function
    function logout() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = '../backend/logout.php';
        }
    }

    // Mobile sidebar toggle
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('mobile-open');
    }

    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.querySelector('.mobile-toggle');
        
        if (window.innerWidth <= 768 && 
            !sidebar.contains(event.target) && 
            !toggleBtn?.contains(event.target)) {
            sidebar.classList.remove('mobile-open');
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        const sidebar = document.querySelector('.sidebar');
        if (window.innerWidth > 768) {
            sidebar.classList.remove('mobile-open');
        }
    });
    </script>
</body>
</html>