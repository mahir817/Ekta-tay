<?php
session_start();
require_once "../backend/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login Page/login.html");
    exit();
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

// Get statistics based on capabilities
$stats = [];

// Housing stats
if (in_array('find_room', $capabilities) || in_array('offer_room', $capabilities)) {
    $housingStmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE type = 'housing'");
    $housingStmt->execute();
    $stats['housing'] = $housingStmt->fetchColumn();
} else {
    $stats['housing'] = 0;
}

// Job stats
if (in_array('find_job', $capabilities) || in_array('post_job', $capabilities)) {
    $jobStmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE type = 'job'");
    $jobStmt->execute();
    $stats['jobs'] = $jobStmt->fetchColumn();
} else {
    $stats['jobs'] = 0;
}

// Tutor stats
if (in_array('find_tutor', $capabilities) || in_array('offer_tuition', $capabilities)) {
    $tutorStmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE type = 'tuition'");
    $tutorStmt->execute();
    $stats['tutors'] = $tutorStmt->fetchColumn();
} else {
    $stats['tutors'] = 0;
}

// Services stats
if (in_array('food_service', $capabilities)) {
    $serviceStmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE type = 'food'");
    $serviceStmt->execute();
    $stats['services'] = $serviceStmt->fetchColumn();
} else {
    $stats['services'] = 0;
}

// Get recent activities from database
require_once "../backend/log_activity.php";
$activities = getRecentActivities($user_id, 5);

// Debug: Check if we have activities for this user
$debugActivitiesCount = 0;
try {
    $debugStmt = $pdo->prepare("SELECT COUNT(*) FROM user_activities WHERE user_id = ?");
    $debugStmt->execute([$user_id]);
    $debugActivitiesCount = $debugStmt->fetchColumn();
} catch (Exception $e) {
    // Table might not exist
}

// Get expense data if user has expense tracking capability
$expenseData = null;
if (in_array('expense_tracking', $capabilities)) {
    try {
        $expenseStmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE user_id = ? GROUP BY category");
        $expenseStmt->execute([$user_id]);
        $expenseData = $expenseStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        try {
            $expenseStmt = $pdo->prepare("SELECT name AS category, SUM(amount) as total FROM expenses WHERE user_id = ? GROUP BY name");
            $expenseStmt->execute([$user_id]);
            $expenseData = $expenseStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {
            $expenseData = null;
        }
    }
}

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

// Check if user can post a service
$canPostService = false;
$postingCaps = ['post_job', 'offer_room', 'offer_tuition', 'food_service'];
foreach ($postingCaps as $cap) {
    if (in_array($cap, $capabilities)) {
        $canPostService = true;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Ekta Tay</title>
    <link rel="stylesheet" href="dashboard.css">
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
                    <a href="#" class="nav-link active">
                        <i class="nav-icon fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                
                <?php if (in_array('Housing', $availableCapabilities)): ?>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-home"></i>
                        Housing
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (in_array('Jobs', $availableCapabilities)): ?>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-briefcase"></i>
                        Jobs
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (in_array('Tutors', $availableCapabilities)): ?>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-graduation-cap"></i>
                        Tuition
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (in_array('Services', $availableCapabilities)): ?>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-wrench"></i>
                        Services
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (in_array('Expenses', $availableCapabilities)): ?>
                <li class="nav-item">
                    <a href="../Expenses Page/expenses.php" class="nav-link">
                        <i class="nav-icon fas fa-wallet"></i>
                        Expenses
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a href="../Profile page/profile.php" class="nav-link">
                        <i class="nav-icon fas fa-cog"></i>
                        Manage
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="dashboard-header">
                <h1 class="dashboard-title">Dashboard</h1>
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

            <!-- Stats Grid -->
            <div class="stats-grid">
                <?php if (in_array('Housing', $availableCapabilities)): ?>
                <div class="stat-card fade-in-up" id="housingCard" style="cursor: pointer;">
                    <div class="stat-header">
                        <span class="stat-title">Housing</span>
                        <div class="stat-icon">
                            <i class="fas fa-home"></i>
                        </div>
                    </div>
                    <h3 class="stat-value"><?php echo $stats['housing']; ?></h3>
                </div>
                <?php endif; ?>

                <?php if (in_array('Jobs', $availableCapabilities)): ?>
<div class="stat-card fade-in-up" id="jobCard" style="cursor: pointer;"
     onclick="window.location.href='../Modules/Jobs/jobs.php'">
    <div class="stat-header">
        <span class="stat-title">Jobs</span>
        <div class="stat-icon">
            <i class="fas fa-briefcase"></i>
        </div>
    </div>
    <h3 class="stat-value"><?php echo $stats['jobs']; ?></h3>
</div>
<?php endif; ?>


                <?php if (in_array('Tutors', $availableCapabilities)): ?>
                <div class="stat-card fade-in-up">
                    <div class="stat-header">
                        <span class="stat-title">Tutors</span>
                        <div class="stat-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                    <h3 class="stat-value"><?php echo $stats['tutors']; ?></h3>
                </div>
                <?php endif; ?>

                <?php if (in_array('Services', $availableCapabilities)): ?>
                <div class="stat-card fade-in-up">
                    <div class="stat-header">
                        <span class="stat-title">Food services</span>
                        <div class="stat-icon">
                            <i class="fas fa-wrench"></i>
                        </div>
                    </div>
                    <h3 class="stat-value"><?php echo $stats['services']; ?></h3>
                </div>
                <?php endif; ?>

                <!-- Post Service Card -->
                <?php if ($canPostService): ?>
                <div class="stat-card fade-in-up" id="postServiceCard" style="cursor: pointer;" 
                     onclick="window.location.href='../Post Service Page/post_service.php">
                    <div class="stat-header">
                        <span class="stat-title">Post Service</span>
                        <div class="stat-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                    </div>
                    <h3 class="stat-value">Create</h3>
                </div>
                <?php endif; ?>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Recent Activities -->
                <div class="glass-card fade-in-up">
                    <div class="card-header">
                        <h2 class="card-title">Recent Activities</h2>
                        <a href="#" class="card-action" onclick="showAllActivities()">View All</a>
                    </div>
                    <ul class="activity-list">
                        <?php if (empty($activities)): ?>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">No recent activities</div>
                                <div class="activity-time">
                                    <?php if ($debugActivitiesCount > 0): ?>
                                        Found <?php echo $debugActivitiesCount; ?> activities in database for user <?php echo $user_id; ?>, but getRecentActivities() returned empty.
                                        <a href="../fix_activities_user.php" style="color: #4CAF50;">Debug Activities</a>
                                    <?php else: ?>
                                        Start using your capabilities to see activities here. 
                                        <a href="../fix_activities_user.php" style="color: #4CAF50;">Add Sample Activities</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                        <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                        <li class="activity-item">
                            <div class="activity-icon"><?php echo htmlspecialchars($activity['icon']); ?></div>
                            <div class="activity-content">
                                <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                                <?php if (!empty($activity['description'])): ?>
                                <div class="activity-description" style="font-size: 0.85em; color: rgba(255,255,255,0.7); margin-top: 2px;">
                                    <?php echo htmlspecialchars($activity['description']); ?>
                                </div>
                                <?php endif; ?>
                                <div class="activity-time"><?php echo htmlspecialchars($activity['time']); ?></div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Community Chart -->
                <div class="glass-card fade-in-up">
                    <div class="card-header">
                        <h2 class="card-title">Community</h2>
                        <select class="card-action" style="background: transparent; border: none; color: white;">
                            <option>All</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: rgba(255,255,255,0.6);">
                            <i class="fas fa-chart-line" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Grid -->
            <div class="content-grid">
                <!-- Expense Tracking -->
                <?php if (in_array('expense_tracking', $capabilities)): ?>
                <div class="glass-card fade-in-up" id="expenseTrackingCard" style="cursor: pointer;" onclick="window.location.href='../Expenses Page/expenses.php'">
                    <div class="card-header">
                        <h2 class="card-title">Expense Tracking</h2>
                        <a href="../Expenses Page/expenses.php" class="card-action">View Details</a>
                    </div>
                    <div class="expense-chart">
                        <div class="chart-circle">
                            <div class="chart-center">
                                <?php 
                                $totalExpenses = 0;
                                if ($expenseData) {
                                    foreach ($expenseData as $expense) {
                                        $totalExpenses += $expense['total'];
                                    }
                                }
                                ?>
                                <div class="chart-total">৳<?php echo number_format($totalExpenses, 0); ?></div>
                                <div class="chart-label">Total Expenses</div>
                            </div>
                        </div>
                        <div class="expense-legend">
                            <?php if ($expenseData): ?>
                                <?php foreach ($expenseData as $expense): ?>
                                <div class="legend-item">
                                    <div class="legend-dot" style="background: #667eea;"></div>
                                    <span class="legend-text"><?php echo $expense['category']; ?></span>
                                    <span class="legend-amount">৳<?php echo number_format($expense['total'], 0); ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="legend-item">
                                    <div class="legend-dot" style="background: #667eea;"></div>
                                    <span class="legend-text">No expenses yet</span>
                                    <span class="legend-amount">৳0</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Payment -->
                <?php if (in_array('expense_tracking', $capabilities)): ?>
                <div class="glass-card fade-in-up">
                    <div class="card-header">
                        <h2 class="card-title">Quick Payment</h2>
                    </div>
                    <form class="payment-form">
                        <div class="form-group">
                            <label class="form-label">Amount</label>
                            <input type="text" class="form-input" placeholder="Enter amount">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Option</label>
                            <div class="radio-group">
                                <div class="radio-item">
                                    <div class="radio-input checked"></div>
                                    <span class="radio-label">Wallet</span>
                                </div>
                                <div class="radio-item">
                                    <div class="radio-input"></div>
                                    <span class="radio-label">Bank</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Payment and notes</label>
                            <input type="text" class="form-input" placeholder="Payment and notes">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Proceed</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Activities Modal -->
    <div id="activitiesModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>All Activities</h2>
                <button class="modal-close" onclick="closeActivitiesModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="activitiesLoading" style="text-align: center; padding: 20px;">
                    <i class="fas fa-spinner fa-spin"></i> Loading activities...
                </div>
                <div id="activitiesList" style="display: none;">
                    <!-- Activities will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="dashboard.js"></script>
    <script>
    // Activities Modal Functions
    function showAllActivities() {
        const modal = document.getElementById('activitiesModal');
        const loading = document.getElementById('activitiesLoading');
        const list = document.getElementById('activitiesList');
        
        modal.style.display = 'flex';
        loading.style.display = 'block';
        list.style.display = 'none';
        
        // Fetch all activities
        fetch('../backend/log_activity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get&limit=50'
        })
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            
            if (data.success && data.activities.length > 0) {
                let html = '<ul class="activity-list" style="max-height: 400px; overflow-y: auto;">';
                data.activities.forEach(activity => {
                    html += `
                        <li class="activity-item" style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                            <div class="activity-icon">${activity.icon}</div>
                            <div class="activity-content">
                                <div class="activity-title">${activity.title}</div>
                                ${activity.description ? `<div class="activity-description" style="font-size: 0.85em; color: rgba(255,255,255,0.7); margin-top: 2px;">${activity.description}</div>` : ''}
                                <div class="activity-time">${activity.time}</div>
                            </div>
                        </li>
                    `;
                });
                html += '</ul>';
                list.innerHTML = html;
            } else {
                list.innerHTML = '<p style="text-align: center; color: rgba(255,255,255,0.7); padding: 20px;">No activities found</p>';
            }
            
            list.style.display = 'block';
        })
        .catch(error => {
            loading.style.display = 'none';
            list.innerHTML = '<p style="text-align: center; color: #ff6b6b; padding: 20px;">Error loading activities</p>';
            list.style.display = 'block';
            console.error('Error:', error);
        });
    }
    
    function closeActivitiesModal() {
        document.getElementById('activitiesModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    document.getElementById('activitiesModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeActivitiesModal();
        }
    });
    </script>

    <style>
    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
    
    .modal-content {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 15px;
        width: 90%;
        max-width: 600px;
        max-height: 80vh;
        overflow: hidden;
        color: white;
    }
    
    .modal-header {
        padding: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h2 {
        margin: 0;
        font-size: 1.5rem;
    }
    
    .modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background 0.3s ease;
    }
    
    .modal-close:hover {
        background: rgba(255, 255, 255, 0.1);
    }
    
    .modal-body {
        padding: 20px;
        max-height: 500px;
        overflow-y: auto;
    }
    
    .modal-body .activity-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .modal-body .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 0;
    }
    
    .modal-body .activity-icon {
        font-size: 1.2rem;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        flex-shrink: 0;
    }
    
    .modal-body .activity-content {
        flex: 1;
    }
    
    .modal-body .activity-title {
        font-weight: 500;
        margin-bottom: 4px;
    }
    
    .modal-body .activity-time {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.6);
    }
    </style>
</body>
</html>
