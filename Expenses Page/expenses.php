<?php
session_start();
require_once "../backend/db.php";

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - Ekta-tay</title>
    <link rel="stylesheet" href="../Dashboard/dashboard.css">
    <link rel="stylesheet" href="../Modules/Housing/housing.css">
    <link rel="stylesheet" href="expenses.css">
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
                    <a href="../Modules/Jobs/jobs.php" class="nav-link">
                        <i class="nav-icon fas fa-briefcase"></i>
                        Jobs
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (in_array('Tutors', $availableCapabilities)): ?>
                <li class="nav-item">
                    <a href="../Modules/Jobs/jobs.php?tab=tuition" class="nav-link">
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
                    <a href="#" class="nav-link active">
                        <i class="nav-icon fas fa-wallet"></i>
                        Expenses
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a href="../Payment Page/payment.php" class="nav-link">
                        <i class="nav-icon fas fa-credit-card"></i>
                        Payment
                    </a>
                </li>
                
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
                <h1 class="dashboard-title">Expenses</h1>
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
      <div class="glass-card" style="padding:12px; margin-bottom:12px; display:flex; gap:10px; align-items:center;">
        <button id="toggleCalc" class="btn" style="padding:8px 12px; background:#009688; color:#fff; border-radius:8px;">Toggle Calculator</button>
        <div style="flex:1"></div>
        <a href="../Post Service Page/post_service.php" class="btn" style="text-decoration:none; padding:8px 12px; background:#3f51b5; color:#fff; border-radius:8px;">Post Service</a>
      </div>

      <div style="display:grid; grid-template-columns: 2fr 1fr; gap:12px; align-items:start;">
        <div>
        <div class="glass-card" style="padding:12px; margin-bottom:12px;">
  <div style="display:flex; justify-content:space-between; align-items:center;">
    <h3 style="margin:0;">Add Expense</h3>
    <button id="toggleAddExpense" class="btn" style="padding:8px 12px; background:#009688; color:#fff; border-radius:8px;">
      Add New Expense
    </button>
  </div>

  <!-- Collapsible Add Expense Section -->
  <div id="addExpenseSection" style="margin-top:10px; display:none;">
    <form id="expenseForm">
      <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:10px;">
        <input type="text" name="title" placeholder="Title" required>
        <select name="category" required>
          <option value="">Category</option>
          <option>Food</option>
          <option>Rent</option>
          <option>Utility</option>
          <option>Transport</option>
          <option>Others</option>
        </select>
        <input type="number" name="amount" placeholder="Amount" required>
        <input type="date" name="date" required>
        <select name="type" required>
          <option value="personal">Personal</option>
          <option value="shared">Shared</option>
        </select>
        <select name="status" required>
          <option value="paid">Paid</option>
          <option value="unpaid">Unpaid</option>
          <option value="pending">Pending</option>
        </select>
      </div>
      <textarea name="description" placeholder="Description (optional)" style="margin-top:10px;"></textarea>
      <div style="margin-top:10px;">
        <button class="btn" type="submit" style="padding:8px 12px; background:#4CAF50; color:#fff; border-radius:8px;">Save</button>
      </div>
    </form>
  </div>
</div>


          <div class="glass-card" style="padding:12px;">
            <div class="glass-card" style="padding:12px;">
  <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
    <h3 style="margin:0;">Expenses</h3>
    <button id="toggleFilters" class="btn" style="padding:8px 12px; background:#009688; color:#fff; border-radius:8px;">
      Filter Searches
    </button>
  </div>

  <!-- Collapsible Filter Section -->
  <div id="filterSection" style="margin-top:10px; display:none;">
    <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(150px,1fr)); gap:8px; align-items:end;">
      <select id="filterCategory">
        <option value="">All Categories</option>
        <option>Food</option>
        <option>Rent</option>
        <option>Utility</option>
        <option>Transport</option>
        <option>Others</option>
      </select>
      <select id="filterStatus">
        <option value="">Any Status</option>
        <option value="paid">Paid</option>
        <option value="unpaid">Unpaid</option>
        <option value="pending">Pending</option>
      </select>
      <input id="filterFrom" type="date">
      <input id="filterTo" type="date">
      <select id="sortBy">
        <option value="date_desc">Newest</option>
        <option value="date_asc">Oldest</option>
        <option value="amount_desc">Amount ↓</option>
        <option value="amount_asc">Amount ↑</option>
      </select>
      <button id="applyFilters" class="btn" style="padding:8px 12px; background:#3f51b5; color:#fff; border-radius:8px;">Apply</button>
    </div>
  </div>

 

  <div id="expensesList" class="card-grid" style="margin-top:12px;"></div>
</div>

            <!-- Expense Summary Cards -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(160px,1fr)); gap:8px; margin-top:10px;">
              <div class="glass-card" id="sumThisMonth" style="padding:10px;">This Month: ৳0</div>
              <div class="glass-card" id="sumPending" style="padding:10px;">Pending: ৳0</div>
              <div class="glass-card" id="sumPaid" style="padding:10px;">Paid: ৳0</div>
              <div class="glass-card" id="sumSavings" style="padding:10px;">Savings: ৳0</div>
            </div>

            <!-- Enhanced Expense Chart -->
            <div class="glass-card" style="padding:16px; margin-top:12px;">
              <h3 style="margin-top:0; margin-bottom:16px; color: white;">Expense Breakdown</h3>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: center;">
                <!-- Pie Chart Container -->
                <div class="expense-chart-container">
                  <div class="pie-chart-wrapper">
                    <canvas id="expensePieChart" width="200" height="200"></canvas>
                    <div class="chart-center-info">
                      <div class="chart-total" id="chartTotal">৳0</div>
                      <div class="chart-label">Total</div>
                    </div>
                  </div>
                </div>
                
                <!-- Legend -->
                <div class="chart-legend" id="chartLegend">
                  <div class="legend-item">
                    <div class="legend-dot" style="background: #667eea;"></div>
                    <span class="legend-text">No data yet</span>
                    <span class="legend-amount">৳0</span>
                  </div>
                </div>
              </div>
            </div>
            <div id="expensesList" class="card-grid" style="margin-top:12px;"></div>
          </div>
        </div>

        <aside>
          <div class="glass-card" id="calculator" style="padding:12px; display:none;">
            <h3 style="margin-top:0;">Calculator</h3>
            <input type="text" id="calcDisplay" readonly style="width:100%; padding:10px; margin-bottom:10px;">
            <div style="display:grid; grid-template-columns: repeat(4,1fr); gap:8px;">
              <?php
                $buttons = ['7','8','9','/','4','5','6','*','1','2','3','-','0','.','=','+','C'];
                foreach ($buttons as $b) {
                  echo '<button class="btn" data-key="' . $b . '" style="padding:10px;">' . $b . '</button>';
                }
              ?>
            </div>
          </div>
        </aside>
      </div>
    </div>
        </main>
    </div>

    <script src="../Dashboard/dashboard.js"></script>
    <script src="expenses.js"></script>
</body>
</html>
