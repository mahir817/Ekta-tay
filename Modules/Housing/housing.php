<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in, if not redirect to login
if (!isset($_SESSION['user_id'])) {
    // For testing purposes, let's set a default user ID
    // Remove this line in production
    $_SESSION['user_id'] = 2; // Set to your user ID for testing
}

// Get user's housing posts if logged in
$userHousing = [];
$user = null;
if (isset($_SESSION['user_id'])) {
    require_once "../../backend/db.php";
    try {
        // Get user information
        $userStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $userStmt->execute([$_SESSION['user_id']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug: Check what we're getting
        error_log("User ID from session: " . $_SESSION['user_id']);
        error_log("User found: " . ($user ? $user['name'] : 'NOT FOUND'));
        
        // Get user's housing posts
        $stmt = $pdo->prepare("
            SELECT s.service_id, s.title, s.description, s.location, s.price, s.created_at,
                   h.rent, h.property_type, h.bedrooms, h.bathrooms, h.furnished_status
            FROM services s
            INNER JOIN housing h ON s.service_id = h.service_id
            WHERE s.user_id = ? AND s.type = 'housing'
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $userHousing = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log the results
        error_log("User housing posts found: " . count($userHousing));
        foreach ($userHousing as $post) {
            error_log("Post: " . $post['title']);
        }
    } catch (Exception $e) {
        error_log("Error fetching user housing: " . $e->getMessage());
        $userHousing = [];
        $user = null;
    }
} else {
    error_log("No user session found");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Housing | Ekta-tay</title>
  <link rel="stylesheet" href="housing.css">
  <link rel="stylesheet" href="housing_workflow.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="housing.js" defer></script>
</head>
<body>

<div class="housing-container">
  <!-- Sidebar -->
  <nav class="sidebar">
    <div class="sidebar-header">
      <div class="logo">
        <img src="../../images/logo.png" alt="Ektate Logo" class="logo-img" />
        <div class="logo-text">Ekta-tay</div>
      </div>
    </div>
    
    <ul class="nav-menu">
      <li class="nav-item">
        <a href="../../Dashboard/dashboard.php" class="nav-link">
          <i class="nav-icon fas fa-home"></i>
          Dashboard
        </a>
      </li>
      
      <li class="nav-item">
        <a href="#" class="nav-link active">
          <i class="nav-icon fas fa-home"></i>
          Housing
        </a>
      </li>
      
      <li class="nav-item">
        <a href="#" class="nav-link">
          <i class="nav-icon fas fa-briefcase"></i>
          Jobs
        </a>
      </li>
      
      <li class="nav-item">
        <a href="#" class="nav-link">
          <i class="nav-icon fas fa-graduation-cap"></i>
          Tuition
        </a>
      </li>
      
      <li class="nav-item">
        <a href="#" class="nav-link">
          <i class="nav-icon fas fa-wrench"></i>
          Services
        </a>
      </li>
      
      <li class="nav-item">
        <a href="../../Profile page/profile.php" class="nav-link">
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
    
    <!-- User Profile at bottom of sidebar -->
    <div class="sidebar-profile">
      <div class="user-profile" onclick="toggleDropdown()">
        <div class="user-avatar">
          <?php 
          // Get user info if session exists
          if (isset($_SESSION['user_id'])) {
            require_once "../../backend/db.php";
            $userStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $userStmt->execute([$_SESSION['user_id']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            echo strtoupper(substr($user['name'], 0, 1));
          } else {
            echo 'U';
          }
          ?>
        </div>
        <div class="user-info">
          <span class="user-name">
            <?php 
            if (isset($user)) {
              echo htmlspecialchars(explode(' ', $user['name'])[0]);
            } else {
              echo 'User';
            }
            ?>
          </span>
          <div class="user-dropdown">
            <div class="dropdown-menu" id="userDropdown">
              <div class="dropdown-item" onclick="window.location.href='../../Profile page/profile.php'">
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
    </div>
  </nav>

  <!-- Main Content -->
  <main class="main-content">

  <!-- Logo Header -->
  <div class="logo-header">
    <a href="../../Dashboard/dashboard.php" class="logo-link">
      <img src="../../images/logo.png" alt="Ekta-tay Logo" class="logo-img" />
      <span class="logo-text">Ekta-tay</span>
    </a>
  </div>

  <!-- Top Navigation Tabs -->
  <div class="tabs glass-card">
    <button class="tab-btn active" onclick="showSection('find')">Find House</button>
    <button class="tab-btn" onclick="showSection('my')">My House</button>
    <button class="tab-btn" onclick="showSection('status')">Status</button>
  </div>

  <!-- Sections -->
  <div id="find" class="tab-section active glass-card">
    <div class="section-header">
      <h2>Find House</h2>
      <div class="mini-actions">
        <button class="add-btn" onclick="fetchHousing()">Refresh</button>
      </div>
    </div>

    <!-- Overview Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Pending</span>
        </div>
        <p class="stat-value" id="statPending">0</p>
        <p class="stat-sub">Applications pending</p>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Applied Requests</span>
        </div>
        <p class="stat-value" id="statApplied">0</p>
        <p class="stat-sub">Total submitted</p>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Confirmed</span>
        </div>
        <p class="stat-value" id="statConfirmed">0</p>
        <p class="stat-sub">Approved requests</p>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Cancelled</span>
        </div>
        <p class="stat-value" id="statCancelled">0</p>
        <p class="stat-sub">Closed requests</p>
      </div>
      <div class="stat-card wide">
        <div class="stat-header">
          <span class="stat-title">Nearby Houses</span>
        </div>
        <p class="stat-value" id="statNearby">24</p>
        <p class="stat-sub">Houses available near you</p>
      </div>
    </div>

    <div class="filters">
      <input type="text" id="searchLocation" placeholder="Search by location..." onkeyup="handleSearch()">
      <select id="rentRange" onchange="handleSearch()">
        <option value="">Rent Range</option>
        <option value="0-10000">0-10k</option>
        <option value="10000-30000">10k-30k</option>
        <option value="30000-50000">30k-50k</option>
        <option value="50000-100000">50k-100k</option>
        <option value="100000+">100k+</option>
      </select>
      <select id="propertyType" onchange="handleSearch()">
        <option value="">Property Type</option>
        <option value="apartment">Apartment</option>
        <option value="room">Room</option>
        <option value="commercial">Commercial</option>
        <option value="mixed">Mixed</option>
      </select>
      <select id="furnishedStatus" onchange="handleSearch()">
        <option value="">Furnished</option>
        <option value="furnished">Furnished</option>
        <option value="semi-furnished">Semi-furnished</option>
        <option value="unfurnished">Unfurnished</option>
      </select>
      <select id="bedrooms" onchange="handleSearch()">
        <option value="">Bedrooms</option>
        <option value="1">1 Bedroom</option>
        <option value="2">2 Bedrooms</option>
        <option value="3">3 Bedrooms</option>
        <option value="4+">4+ Bedrooms</option>
      </select>
      <button onclick="clearFilters()" class="add-btn cancel-btn">Clear</button>
      <button onclick="fetchHousing()" class="add-btn">Refresh</button>
    </div>
    <div id="housingList" class="card-grid">
      <!-- Housing posts loaded via AJAX -->
    </div>
  </div>

  <div id="my" class="tab-section hidden glass-card">
    <div class="section-header">
      <h2>My House</h2>
      <div class="mini-actions">
      </div>
    </div>

    <div class="info-grid">
      <!-- My House Info -->
      <div class="card">
        <div class="section-header" style="margin:0 0 8px 0;">
          <h3>House Details</h3>
          <button class="add-btn" onclick="openEditHouse()">Edit</button>
        </div>
        <div id="myHouseInfo">
          <p>No house linked yet.</p>
        </div>
      </div>

      <!-- Split Rent -->
      <div class="card split-card">
        <h3>Split Rent</h3>
        <div class="split-form">
          <input type="number" id="totalRent" placeholder="Total monthly rent (BDT)">
          <input type="number" id="numRoommates" placeholder="Number of roommates">
          <button class="add-btn split-calc-btn" onclick="calculateSplit()">Calculate</button>
        
        </div>
          
        <div id="roommatesContainer"></div>
        <div style="margin-top:10px;">
          
          <button class="add-btn" id="addToExpensesBtn" onclick="addRoommatesToExpenses()">Add to Expenses</button>
        </div>
        <div id="splitResult" class="split-result"></div>
      </div>

      <!-- Expenses Analysis -->
      <div class="card">
        <div class="section-header" style="margin:0 0 8px 0;">
          <h3>Expenses</h3>
          <button class="add-btn" onclick="openExpenseForm()">Add Expense</button>
        </div>
        <div class="expense-chart">
          <div class="chart-circle" id="expenseDonut">
            <div class="chart-center">
              <p class="chart-total" id="expenseTotal">৳0</p>
              <span class="chart-label">This month</span>
            </div>
          </div>
          <div class="expense-legend" id="expenseLegend"></div>
        </div>
        <div id="expensesTable" style="margin-top:12px;"></div>
      </div>
    </div>

    <div class="subsection">
      <h3 style="margin-bottom:10px;">My Housing Posts</h3>
      <!-- Debug info -->
      <div style="background: rgba(255,255,255,0.1); padding: 10px; margin-bottom: 10px; border-radius: 8px; font-size: 12px;">
        <strong>Debug Info:</strong><br>
        Session User ID: <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET'; ?><br>
        User Housing Count: <?php echo count($userHousing); ?><br>
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="../../backend/debug_housing_posts.php" target="_blank" style="color: #4ade80;">View Detailed Debug</a>
        <?php endif; ?>
      </div>
      <div id="myHousingList" class="card-grid">
        <?php
if(isset($userHousing) && count($userHousing) > 0){
  foreach($userHousing as $post){
    echo "<div class='card glass-card' id='post-" . $post['service_id'] . "'>
            <h3>" . htmlspecialchars($post['title']) . "</h3>
            <p>Location: " . htmlspecialchars($post['location']) . "</p>
            <p>Rent: ৳" . htmlspecialchars($post['rent']) . "</p>
            <p>" . htmlspecialchars($post['description']) . "</p>
            <button class='add-btn cancel-btn' onclick='deletePost(" . $post['service_id'] . ")'>Delete</button>
          </div>";
  }
}
 else {
          echo "<div class='glass-card no-content'>No posts yet.</div>";
          echo "<div style='margin-top:10px;'><button class='add-btn' onclick='openPostForm()'>+ Post Housing</button></div>";
        }
        ?>
      </div>
    </div>
  </div>

  <!-- Status Section -->
  <div id="status" class="tab-section hidden glass-card">
    <div class="section-header">
      <h2>Application Status</h2>
      <div class="mini-actions">
        <button class="add-btn" onclick="refreshStatus()">Refresh</button>
      </div>
    </div>

    <!-- Status Tabs -->
    <div class="status-tabs">
      <button class="status-tab-btn active" onclick="showStatusTab('pending')">
        Pending <span class="status-count" id="pendingCount">0</span>
      </button>
      <button class="status-tab-btn" onclick="showStatusTab('confirmed')">
        Confirmed <span class="status-count" id="confirmedCount">0</span>
      </button>
      <button class="status-tab-btn" onclick="showStatusTab('cancelled')">
        Cancelled <span class="status-count" id="cancelledCount">0</span>
      </button>
      <button class="status-tab-btn" onclick="showStatusTab('rejected')">
        Rejected <span class="status-count" id="rejectedCount">0</span>
      </button>
    </div>

    <!-- Status Content -->
    <div id="statusContent" class="status-content">
      <!-- Pending Applications -->
      <div id="pending" class="status-tab-section active">
        <h3>Pending Applications</h3>
        <div id="pendingList" class="status-list">
          <!-- Pending applications will be loaded here -->
        </div>
      </div>

      <!-- Confirmed Applications -->
      <div id="confirmed" class="status-tab-section hidden">
        <h3>Confirmed Applications</h3>
        <div id="confirmedList" class="status-list">
          <!-- Confirmed applications will be loaded here -->
        </div>
      </div>

      <!-- Cancelled Applications -->
      <div id="cancelled" class="status-tab-section hidden">
        <h3>Cancelled Applications</h3>
        <div id="cancelledList" class="status-list">
          <!-- Cancelled applications will be loaded here -->
        </div>
      </div>

      <!-- Rejected Applications -->
      <div id="rejected" class="status-tab-section hidden">
        <h3>Rejected Applications</h3>
        <div id="rejectedList" class="status-list">
          <!-- Rejected applications will be loaded here -->
        </div>
      </div>
    </div>
  </div>

  <!-- Hidden modal trigger in My House for expenses -->
  <div style="display:none">
    <button class="add-btn" onclick="openExpenseForm()" id="hiddenExpenseBtn">+ Add Expense</button>
  </div>

  </main>
</div>

<!-- Modal for Posting Housing -->
<div id="postModal" class="modal hidden">
  <div class="modal-content glass-card">
    <h3>Post New Housing</h3>
    <div style="text-align:center; margin: 24px 0;">
      <a href="/Post Service Page/post_service.html" class="add-btn" style="font-size:1.1em; padding:10px 24px; text-decoration:none;">Go to Post Service Page</a>
    </div>
    <button type="button" onclick="closePostForm()" class="add-btn cancel-btn">Cancel</button>
  </div>
</div>

<!-- Modal: Edit My House -->
<div id="editHouseModal" class="modal hidden">
  <div class="modal-content glass-card">
    <h3>Edit My House</h3>
    <form id="editHouseForm">
      <input type="text" name="address" placeholder="Address">
      <input type="number" name="rent" placeholder="Monthly Rent (BDT)">
      <input type="number" name="bedrooms" placeholder="Bedrooms">
      <input type="number" name="bathrooms" placeholder="Bathrooms">
      <input type="text" name="notes" placeholder="Notes (optional)">
      <button type="submit" class="add-btn">Save</button>
      <button type="button" onclick="closeEditHouse()" class="add-btn cancel-btn">Cancel</button>
    </form>
  </div>
  </div>

<!-- Modal for Adding Expense -->
<div id="expenseModal" class="modal hidden">
  <div class="modal-content glass-card">
    <h3>Add Expense</h3>
    <form id="expenseForm">
      <input type="text" name="name" placeholder="Expense Name" required>
      <input type="number" name="amount" placeholder="Amount" required>
      <input type="date" name="due_date" required>
      <button type="submit" class="add-btn">Save</button>
      <button type="button" onclick="closeExpenseForm()" class="add-btn cancel-btn">Cancel</button>
    </form>
  </div>
</div>

</body>
</html>
