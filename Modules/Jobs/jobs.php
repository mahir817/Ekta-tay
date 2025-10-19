<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in, if not redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../Login Page/login.html");
    exit();
}

require_once "../../backend/db.php";

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
    'offer_job' => 'Jobs',
    'find_tutor' => 'Tutors',
    'offer_tutoring' => 'Tutors',
    'find_service' => 'Services',
    'offer_service' => 'Services',
    'expense_tracking' => 'Expenses'
];

$availableCapabilities = [];
foreach ($capabilities as $cap) {
    if (isset($capabilityMap[$cap])) {
        $availableCapabilities[] = $capabilityMap[$cap];
    }
}
$availableCapabilities = array_unique($availableCapabilities);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Jobs | Ekta-tay</title>
  <link rel="stylesheet" href="jobs.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="jobs.js" defer></script>
</head>
<body>

<div class="dashboard-container">
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
            
            <?php if (in_array('Housing', $availableCapabilities)): ?>
            <li class="nav-item">
                <a href="../Housing/housing.php" class="nav-link">
                    <i class="nav-icon fas fa-home"></i>
                    Housing
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array('Jobs', $availableCapabilities)): ?>
            <li class="nav-item">
                <a href="#" class="nav-link active">
                    <i class="nav-icon fas fa-briefcase"></i>
                    Jobs
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array('Tutors', $availableCapabilities)): ?>
            <li class="nav-item">
                <a href="jobs.php?tab=tuition" class="nav-link">
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
                <a href="../../Expenses Page/expenses.php" class="nav-link">
                    <i class="nav-icon fas fa-wallet"></i>
                    Expenses
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a href="../../Payment Page/payment.php" class="nav-link">
                    <i class="nav-icon fas fa-credit-card"></i>
                    Payment
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../../Profile page/profile.php" class="nav-link">
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
            <h1 class="dashboard-title">Jobs</h1>
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
                            <div class="dropdown-item" onclick="window.location.href='../../Profile page/profile.php'">
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

        <div class="jobs-container">

  <!-- Top Navigation Tabs -->
  <div class="tabs glass-card">
    <button class="tab-btn active" onclick="showSection('find')">Find Jobs</button>
    <button class="tab-btn" onclick="showSection('tuition')">Tuition</button>
    <button class="tab-btn" onclick="showSection('my')">My Posts</button>
  </div>

  <!-- Find Jobs Section -->
  <div id="find" class="tab-section active glass-card">
    <div class="section-header">
      <h2>Find Jobs</h2>
      <div class="mini-actions">
        <button class="add-btn" onclick="refreshJobs()">Refresh</button>
        <button class="add-btn" onclick="openPostForm()">+ Post Job</button>
      </div>
    </div>

    <!-- Overview Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Applied</span>
        </div>
        <p class="stat-value" id="statApplied">0</p>
        <p class="stat-sub">Applications sent</p>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">In Review</span>
        </div>
        <p class="stat-value" id="statReview">0</p>
        <p class="stat-sub">Under consideration</p>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Interviews</span>
        </div>
        <p class="stat-value" id="statInterviews">0</p>
        <p class="stat-sub">Scheduled interviews</p>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Offers</span>
        </div>
        <p class="stat-value" id="statOffers">0</p>
        <p class="stat-sub">Job offers received</p>
      </div>
      <div class="stat-card wide">
        <div class="stat-header">
          <span class="stat-title">Available Jobs</span>
        </div>
        <p class="stat-value" id="statAvailable">0</p>
        <p class="stat-sub">Jobs matching your profile</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters">
      <input type="text" id="searchKeyword" placeholder="Search by keyword...">
      <select id="jobType">
        <option value="">Job Type</option>
        <option value="part-time">Part-time</option>
        <option value="full-time">Full-time</option>
        <option value="freelance">Freelance</option>
        <option value="internship">Internship</option>
      </select>
      <select id="salaryRange">
        <option value="">Salary Range</option>
        <option value="0-20000">0-20k BDT</option>
        <option value="20000-50000">20k-50k BDT</option>
        <option value="50000-100000">50k-100k BDT</option>
        <option value="100000+">100k+ BDT</option>
      </select>
      <input type="text" id="searchLocation" placeholder="Location...">
      <button onclick="fetchJobs()" class="add-btn">Search</button>
    </div>

    <div id="jobsList" class="card-grid">
      <!-- Job posts loaded via AJAX -->
    </div>
  </div>

  <!-- Tuition Section -->
  <div id="tuition" class="tab-section hidden glass-card">
    <div class="section-header">
      <h2>Tuition Opportunities</h2>
      <div class="mini-actions">
        <button class="add-btn" onclick="refreshTuition()">Refresh</button>
        <button class="add-btn" onclick="openTuitionPostForm()">+ Post Tuition</button>
      </div>
    </div>

    <!-- Tuition Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Active Students</span>
        </div>
        <p class="stat-value" id="statStudents">0</p>
        <p class="stat-sub">Students you're teaching</p>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Monthly Earnings</span>
        </div>
        <p class="stat-value" id="statEarnings">৳0</p>
        <p class="stat-sub">This month's income</p>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Subjects</span>
        </div>
        <p class="stat-value" id="statSubjects">0</p>
        <p class="stat-sub">Subjects you teach</p>
      </div>
      <div class="stat-card wide">
        <div class="stat-header">
          <span class="stat-title">Available Requests</span>
        </div>
        <p class="stat-value" id="statTuitionRequests">0</p>
        <p class="stat-sub">Students looking for tutors</p>
      </div>
    </div>

    <!-- Tuition Filters -->
    <div class="filters">
      <select id="subjectFilter">
        <option value="">Subject</option>
        <option value="mathematics">Mathematics</option>
        <option value="physics">Physics</option>
        <option value="chemistry">Chemistry</option>
        <option value="biology">Biology</option>
        <option value="english">English</option>
        <option value="bangla">Bangla</option>
        <option value="ict">ICT</option>
        <option value="accounting">Accounting</option>
      </select>
      <select id="classLevel">
        <option value="">Class Level</option>
        <option value="class-6-8">Class 6-8</option>
        <option value="class-9-10">Class 9-10</option>
        <option value="class-11-12">Class 11-12</option>
        <option value="university">University</option>
      </select>
      <select id="tuitionType">
        <option value="">Tuition Type</option>
        <option value="home">Home Tuition</option>
        <option value="online">Online</option>
        <option value="center">Coaching Center</option>
      </select>
      <input type="text" id="tuitionLocation" placeholder="Location...">
      <button onclick="fetchTuition()" class="add-btn">Search</button>
    </div>

    <div id="tuitionList" class="card-grid">
      <!-- Tuition posts loaded via AJAX -->
    </div>
  </div>

  <!-- My Posts Section -->
  <div id="my" class="tab-section hidden glass-card">
    <div class="section-header">
      <h2>My Job Posts</h2>
      <div class="mini-actions">
        <button class="add-btn" onclick="refreshMyPosts()">Refresh</button>
      </div>
    </div>

    <!-- My Posts Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Active Posts</span>
        </div>
        <p class="stat-value" id="statActivePosts">0</p>
        <p class="stat-sub">Currently live</p>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Applications</span>
        </div>
        <p class="stat-value" id="statApplicationsReceived">0</p>
        <p class="stat-sub">Total received</p>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Views</span>
        </div>
        <p class="stat-value" id="statViews">0</p>
        <p class="stat-sub">Post impressions</p>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Response Rate</span>
        </div>
        <p class="stat-value" id="statResponseRate">0%</p>
        <p class="stat-sub">Application response</p>
      </div>
    </div>

    <div class="subsection">
      <h3 style="margin-bottom:10px;">Posted Jobs & Tuition</h3>
      <div id="myPostsList" class="card-grid">
        <!-- User's posts loaded via AJAX -->
      </div>
    </div>
  </div>

</div>

<!-- Modal for Posting Jobs -->
<div id="postModal" class="modal hidden">
  <div class="modal-content glass-card">
    <h3>Post New Job</h3>
    <form id="postJobForm">
      <input type="text" name="title" placeholder="Job Title" required>
      <select name="type" required>
        <option value="">Select Job Type</option>
        <option value="job">Part-time Job</option>
        <option value="tuition">Tuition</option>
      </select>
      <input type="text" name="location" placeholder="Location" required>
      <input type="number" name="price" placeholder="Salary/Payment (BDT)" required>
      <textarea name="description" placeholder="Job Description" rows="4" required></textarea>
      
      <!-- Job-specific fields -->
      <div id="jobFields" class="conditional-fields">
        <select name="job_type">
          <option value="">Job Category</option>
          <option value="part-time">Part-time</option>
          <option value="full-time">Full-time</option>
          <option value="freelance">Freelance</option>
          <option value="internship">Internship</option>
        </select>
        <input type="text" name="company" placeholder="Company/Organization">
      </div>
      
      <!-- Tuition-specific fields -->
      <div id="tuitionFields" class="conditional-fields hidden">
        <select name="subject">
          <option value="">Subject</option>
          <option value="mathematics">Mathematics</option>
          <option value="physics">Physics</option>
          <option value="chemistry">Chemistry</option>
          <option value="biology">Biology</option>
          <option value="english">English</option>
          <option value="bangla">Bangla</option>
          <option value="ict">ICT</option>
          <option value="accounting">Accounting</option>
        </select>
        <select name="class_level">
          <option value="">Class Level</option>
          <option value="class-6-8">Class 6-8</option>
          <option value="class-9-10">Class 9-10</option>
          <option value="class-11-12">Class 11-12</option>
          <option value="university">University</option>
        </select>
        <select name="tuition_type">
          <option value="">Tuition Type</option>
          <option value="home">Home Tuition</option>
          <option value="online">Online</option>
          <option value="center">Coaching Center</option>
        </select>
      </div>
      
      <button type="submit" class="add-btn">Post Job</button>
      <button type="button" onclick="closePostForm()" class="add-btn cancel-btn">Cancel</button>
    </form>
  </div>
</div>

<!-- Modal for Posting Tuition separately -->
<div id="tuitionPostModal" class="modal hidden">
  <div class="modal-content glass-card">
    <h3>Post Tuition Opportunity</h3>
    <form id="postTuitionForm">
      <input type="text" name="title" placeholder="Tuition Title" required>
      <input type="hidden" name="type" value="tuition">
      <select name="subject" required>
        <option value="">Subject</option>
        <option value="mathematics">Mathematics</option>
        <option value="physics">Physics</option>
        <option value="chemistry">Chemistry</option>
        <option value="biology">Biology</option>
        <option value="english">English</option>
        <option value="bangla">Bangla</option>
        <option value="ict">ICT</option>
        <option value="accounting">Accounting</option>
      </select>
      <select name="class_level" required>
        <option value="">Class Level</option>
        <option value="class-6-8">Class 6-8</option>
        <option value="class-9-10">Class 9-10</option>
        <option value="class-11-12">Class 11-12</option>
        <option value="university">University</option>
      </select>
      <select name="tuition_type" required>
        <option value="">Tuition Type</option>
        <option value="home">Home Tuition</option>
        <option value="online">Online</option>
        <option value="center">Coaching Center</option>
      </select>
      <input type="text" name="location" placeholder="Location" required>
      <input type="number" name="price" placeholder="Monthly Fee (BDT)" required>
      <textarea name="description" placeholder="Requirements & Details" rows="4" required></textarea>
      
      <button type="submit" class="add-btn">Post Tuition</button>
      <button type="button" onclick="closeTuitionPostForm()" class="add-btn cancel-btn">Cancel</button>
    </form>
  </div>
</div>

<!-- Application Modal -->
<div id="applicationModal" class="modal hidden">
  <div class="modal-content glass-card">
    <h3>Apply for Job</h3>
    <form id="applicationForm">
      <input type="hidden" name="service_id" id="applyServiceId">
      <textarea name="cover_letter" placeholder="Write a brief cover letter..." rows="4" required></textarea>
      <input type="text" name="phone" placeholder="Your contact number" required>
      <input type="email" name="email" placeholder="Your email" required>
      
      <button type="submit" class="add-btn">Submit Application</button>
      <button type="button" onclick="closeApplicationModal()" class="add-btn cancel-btn">Cancel</button>
    </form>
  </div>
</div>

        </div>
    </main>
</div>

<script>
// Dropdown functionality
function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('show');
}

function closeDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.remove('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const userProfile = document.querySelector('.user-profile');
    const dropdown = document.getElementById('userDropdown');
    
    if (!userProfile.contains(event.target)) {
        closeDropdown();
    }
});

// Logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../../backend/logout.php';
    }
}
</script>

</body>
</html>
