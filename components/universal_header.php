<?php
// Universal header component for all pages
// Requires session to be started and user to be logged in

// Compute correct relative path prefix for assets (handles Modules/* depth)
$grandDir = basename(dirname(dirname($_SERVER['PHP_SELF'])));
$assetPrefix = ($grandDir === 'Modules') ? '../../' : '../';

// Get user information
$userStmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// Get user capabilities
$capStmt = $pdo->prepare("
    SELECT c.capability_name 
    FROM capabilities c 
    JOIN user_capabilities uc ON c.id = uc.capability_id 
    WHERE uc.user_id = ?
");
$capStmt->execute([$_SESSION['user_id']]);
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

// Get current page for active navigation
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>

<div class="dashboard-container">
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <img src="<?php echo $assetPrefix; ?>images/logo.png" alt="Ektate Logo" class="logo-img" />
                <div class="logo-text">Ekta-tay</div>
            </div>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="<?php echo $assetPrefix; ?>Dashboard/dashboard.php" class="nav-link <?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>">
                    <i class="nav-icon fas fa-home"></i>
                    Dashboard
                </a>
            </li>
            
            <?php if (in_array('Housing', $availableCapabilities)): ?>
            <li class="nav-item">
                <a href="<?php echo $assetPrefix; ?>Modules/Housing/housing.php" class="nav-link <?php echo ($currentPage == 'housing') ? 'active' : ''; ?>">
                    <i class="nav-icon fas fa-home"></i>
                    Housing
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array('Jobs', $availableCapabilities)): ?>
            <li class="nav-item">
                <a href="<?php echo $assetPrefix; ?>Modules/Jobs/jobs_listings.php" class="nav-link <?php echo ($currentPage == 'jobs_listings') ? 'active' : ''; ?>">
                    <i class="nav-icon fas fa-briefcase"></i>
                    Jobs
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array('Tutors', $availableCapabilities)): ?>
            <li class="nav-item">
                <a href="<?php echo $assetPrefix; ?>Modules/Tuitions/tuitions_listings.php" class="nav-link <?php echo ($currentPage == 'tuitions_listings') ? 'active' : ''; ?>">
                    <i class="nav-icon fas fa-graduation-cap"></i>
                    Tuition
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array('Services', $availableCapabilities)): ?>
            <li class="nav-item">
                <a href="<?php echo $assetPrefix; ?>Modules/Services/services_listings.php" class="nav-link <?php echo ($currentPage == 'services_listings') ? 'active' : ''; ?>">
                    <i class="nav-icon fas fa-wrench"></i>
                    Services
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a href="<?php echo $assetPrefix; ?>Expenses Page/expenses.php" class="nav-link <?php echo ($currentDir == 'Expenses Page' && $currentPage == 'expenses') ? 'active' : ''; ?>">
                    <i class="nav-icon fas fa-wallet"></i>
                    Expenses
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo $assetPrefix; ?>Post Service Page/post_service.php" class="nav-link <?php echo ($currentDir == 'Post Service Page' && $currentPage == 'post_service') ? 'active' : ''; ?>">
                    <i class="nav-icon fas fa-plus-circle"></i>
                    Post Service
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
            <h1 class="dashboard-title"><?php echo $pageTitle ?? 'Ekta Tay'; ?></h1>
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
                            <div class="dropdown-item" onclick="window.location.href='<?php echo $assetPrefix; ?>Profile page/profile.php'">
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
